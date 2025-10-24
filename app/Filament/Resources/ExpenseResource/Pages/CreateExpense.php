<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Models\ExpenseCategory;
use App\Models\Provider;
use App\Models\CostCenter;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = ExpenseResource::class;

    protected function getSteps(): array
    {
        return [
            // Step 1: Basic Information
            Wizard\Step::make('Información del Gasto')
                ->icon('heroicon-o-currency-dollar')
                ->description('Detalles básicos del gasto')
                ->schema([
                    Forms\Components\DatePicker::make('date')
                        ->label('Fecha')
                        ->required()
                        ->default(now())
                        ->maxDate(now())
                        ->native(false)
                        ->helperText('Fecha en que se realizó el gasto')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('amount')
                        ->label('Monto')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01)
                        ->minValue(0.01)
                        ->helperText('Monto total del gasto')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->required()
                        ->rows(4)
                        ->autosize()
                        ->helperText('Describa el concepto del gasto')
                        ->columnSpanFull(),
                ])
                ->columns(1),

            // Step 2: Classification
            Wizard\Step::make('Clasificación')
                ->icon('heroicon-o-tag')
                ->description('Categoría, proveedor y centro de costo')
                ->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Categoría')
                        ->required()
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->label('Descripción')
                                ->rows(3),
                        ])
                        ->helperText('Seleccione la categoría del gasto')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('provider_id')
                        ->label('Proveedor')
                        ->required()
                        ->relationship('provider', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('contact_name')
                                ->label('Nombre de Contacto')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('phone')
                                ->label('Teléfono')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('service_type')
                                ->label('Tipo de Servicio')
                                ->maxLength(255),
                            Forms\Components\Textarea::make('address')
                                ->label('Dirección')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->helperText('Seleccione el proveedor del servicio/producto')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('cost_center_id')
                        ->label('Centro de Costo')
                        ->required()
                        ->relationship('costCenter', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->label('Descripción')
                                ->rows(3),
                            Forms\Components\TextInput::make('budget')
                                ->label('Presupuesto')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->minValue(0),
                        ])
                        ->helperText('Seleccione el centro de costo asociado')
                        ->columnSpanFull(),
                ])
                ->columns(1),

            // Step 3: Attachments
            Wizard\Step::make('Comprobantes')
                ->icon('heroicon-o-paper-clip')
                ->description('Adjunte facturas y recibos')
                ->schema([
                    Forms\Components\FileUpload::make('new_attachments')
                        ->label('Archivos Adjuntos')
                        ->multiple()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                        ->maxSize(10240)
                        ->directory('expenses-temp')
                        ->disk('local')
                        ->visibility('private')
                        ->downloadable()
                        ->previewable()
                        ->reorderable()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            null,
                            '16:9',
                            '4:3',
                            '1:1',
                        ])
                        ->orientImagesFromExif()
                        ->maxFiles(10)
                        ->helperText('Suba facturas, recibos o comprobantes. Formatos: PDF, JPG, PNG. Máximo 10MB por archivo.')
                        ->columnSpanFull()
                        ->dehydrated(false),
                ])
                ->columns(1),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Guardar attachments para procesarlos después
        $this->newAttachments = $data['new_attachments'] ?? [];

        // Remover new_attachments del data para que no intente guardarlo en expenses
        unset($data['new_attachments']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $expense = $this->record;
        $attachmentCount = 0;

        // Procesar attachments
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

                        $minioPath = 'expenses/' . $expense->id . '/' . time() . '_' . $fileName;
                        $minioDisk->put($minioPath, $fileContent, 'private');

                        $mimeType = match ($extension) {
                            'pdf' => 'application/pdf',
                            'jpg', 'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            default => 'application/octet-stream',
                        };

                        $expense->attachments()->create([
                            'file_name' => $fileName,
                            'file_path' => $minioPath,
                            'file_size' => $fileSize,
                            'mime_type' => $mimeType,
                            'uploaded_by' => auth()->id(),
                        ]);

                        $localDisk->delete($localFilePath);
                        $attachmentCount++;

                    } catch (\Exception $e) {
                        \Log::error('Failed to move file to MinIO', [
                            'local_path' => $localFilePath,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Mostrar notificación de éxito con resumen
        $body = "💰 Monto: $" . number_format($expense->amount, 2) . " MXN\n";
        $body .= "📂 Categoría: {$expense->category->name}\n";
        $body .= "📎 Archivos adjuntos: {$attachmentCount}";

        Notification::make()
            ->title('Gasto registrado exitosamente')
            ->body($body)
            ->success()
            ->duration(5000)
            ->send();
    }

    protected ?array $newAttachments = null;
}
