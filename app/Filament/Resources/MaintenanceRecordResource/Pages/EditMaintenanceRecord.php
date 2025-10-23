<?php

namespace App\Filament\Resources\MaintenanceRecordResource\Pages;

use App\Filament\Resources\MaintenanceRecordResource;
use App\Models\ProductUsage;
use App\Models\SparePart;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceRecord extends EditRecord
{
    protected static string $resource = MaintenanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los productos usados existentes al abrir el formulario
        $productUsages = $this->record->productUsages()
            ->with('sparePart')
            ->get()
            ->map(function ($usage) {
                return [
                    'id' => $usage->id,
                    'spare_part_id' => $usage->spare_part_id,
                    'quantity_used' => $usage->quantity_used,
                    'notes' => $usage->notes,
                    'available_stock' => $usage->sparePart->stock_quantity + $usage->quantity_used, // Stock actual + lo que se usó
                    'unit_cost' => $usage->sparePart->unit_cost,
                    'item_total' => (float)$usage->quantity_used * (float)$usage->sparePart->unit_cost,
                ];
            })
            ->toArray();

        $data['products_used'] = $productUsages;

        // Calcular el costo total
        $totalCost = 0;
        foreach ($productUsages as $product) {
            $totalCost += (float)$product['item_total'];
        }
        $data['calculated_total'] = $totalCost;

        // Cargar attachments existentes
        // No necesitamos hacer nada aquí porque Filament lo maneja automáticamente
        // con loadStateFromRelationshipsUsing en el componente FileUpload

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Guardar los productos para procesarlos después
        $this->newProductsUsed = $data['products_used'] ?? [];
        $this->newAttachments = $data['new_attachments'] ?? [];

        // Remover products_used y attachments del data para que no intente guardarlos en maintenance_records
        unset($data['products_used']);
        unset($data['calculated_total']);
        unset($data['new_attachments']);

        return $data;
    }

    protected function afterSave(): void
    {
        $maintenanceRecord = $this->record;

        // Obtener productos existentes antes del update
        $existingProducts = $maintenanceRecord->productUsages()
            ->get()
            ->keyBy('id');

        $newProductIds = collect($this->newProductsUsed)->pluck('id')->filter();

        // Eliminar productos que ya no están en la lista
        foreach ($existingProducts as $existingProduct) {
            if (!$newProductIds->contains($existingProduct->id)) {
                // Devolver al stock la cantidad que se había usado
                $sparePart = SparePart::find($existingProduct->spare_part_id);
                if ($sparePart) {
                    $sparePart->increment('stock_quantity', $existingProduct->quantity_used);
                }
                $existingProduct->delete();
            }
        }

        // Actualizar o crear productos
        foreach ($this->newProductsUsed as $productData) {
            if (isset($productData['spare_part_id']) && isset($productData['quantity_used'])) {
                $sparePart = SparePart::find($productData['spare_part_id']);

                if (isset($productData['id']) && $existingProducts->has($productData['id'])) {
                    // Actualizar producto existente
                    $existing = $existingProducts->get($productData['id']);
                    $oldQuantity = $existing->quantity_used;
                    $newQuantity = $productData['quantity_used'];
                    $difference = $newQuantity - $oldQuantity;

                    $existing->update([
                        'quantity_used' => $newQuantity,
                        'notes' => $productData['notes'] ?? null,
                    ]);

                    // Ajustar stock según la diferencia
                    if ($difference != 0 && $sparePart) {
                        $sparePart->decrement('stock_quantity', $difference);
                    }
                } else {
                    // Crear nuevo producto
                    ProductUsage::create([
                        'maintenance_record_id' => $maintenanceRecord->id,
                        'spare_part_id' => $productData['spare_part_id'],
                        'quantity_used' => $productData['quantity_used'],
                        'date_used' => $maintenanceRecord->date,
                        'notes' => $productData['notes'] ?? null,
                        'used_by' => auth()->id(),
                    ]);

                    // Decrementar stock
                    if ($sparePart) {
                        $sparePart->decrement('stock_quantity', $productData['quantity_used']);
                    }
                }
            }
        }

        // Procesar nuevos attachments
        if (!empty($this->newAttachments)) {
            foreach ($this->newAttachments as $localFilePath) {
                if (is_string($localFilePath)) {
                    try {
                        $localDisk = \Storage::disk('local');
                        $minioDisk = \Storage::disk('minio');

                        if (!$localDisk->exists($localFilePath)) {
                            continue;
                        }

                        $fileName = basename($localFilePath);
                        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $fileContent = $localDisk->get($localFilePath);
                        $fileSize = $localDisk->size($localFilePath);

                        $minioPath = 'maintenance-records/' . $maintenanceRecord->id . '/' . time() . '_' . $fileName;
                        $minioDisk->put($minioPath, $fileContent, 'private');

                        $mimeType = match ($extension) {
                            'pdf' => 'application/pdf',
                            'jpg', 'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            default => 'application/octet-stream',
                        };

                        $maintenanceRecord->attachments()->create([
                            'file_name' => $fileName,
                            'file_path' => $minioPath,
                            'file_size' => $fileSize,
                            'mime_type' => $mimeType,
                            'uploaded_by' => auth()->id(),
                        ]);

                        $localDisk->delete($localFilePath);

                    } catch (\Exception $e) {
                        \Log::error('Failed to move file to MinIO', [
                            'local_path' => $localFilePath,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        $totalProducts = count($this->newProductsUsed);
        $totalCost = $maintenanceRecord->fresh()->calculated_cost;
        $totalAttachments = count($this->newAttachments);

        Notification::make()
            ->title('Mantenimiento actualizado exitosamente')
            ->body("Productos: {$totalProducts} | Archivos: {$totalAttachments} | Costo: $" . number_format($totalCost, 2) . " MXN")
            ->success()
            ->send();
    }

    public function deleteAttachment($attachmentId)
    {
        $attachment = \App\Models\Attachment::find($attachmentId);

        if ($attachment && $attachment->attachable_id === $this->record->id && $attachment->attachable_type === \App\Models\MaintenanceRecord::class) {
            try {
                // Delete database record (file cleanup is automatic via model's boot method)
                $attachment->delete();

                Notification::make()
                    ->title('Archivo eliminado exitosamente')
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                \Log::error('Failed to delete attachment', [
                    'attachment_id' => $attachmentId,
                    'error' => $e->getMessage()
                ]);

                Notification::make()
                    ->title('Error al eliminar archivo')
                    ->body('No se pudo eliminar el archivo. Intenta nuevamente.')
                    ->danger()
                    ->send();
            }
        }
    }

    protected ?array $newProductsUsed = null;
    protected ?array $newAttachments = null;
}
