<?php

namespace App\Filament\Resources\MaintenanceRecordResource\Pages;

use App\Filament\Resources\MaintenanceRecordResource;
use App\Models\MaintenanceRecord;
use App\Models\ProductUsage;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Services\Validation\StockValidationService;
use App\Contracts\FormFieldResolverInterface;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMaintenanceRecord extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = MaintenanceRecordResource::class;

    protected function getSteps(): array
    {
        $user = Auth::user();
        $formFieldResolver = app(FormFieldResolverInterface::class);
        $stockValidationService = app(StockValidationService::class);

        return [
            // Step 1: Vehicle Identification
            Wizard\Step::make('Identificación del Vehículo')
                ->icon('heroicon-o-truck')
                ->description('Seleccione el vehículo y tipo de mantenimiento')
                ->schema([
                    Forms\Components\Select::make('vehicle_type')
                        ->label('Tipo de Vehículo')
                        ->options([
                            'App\\Models\\Vehicle' => 'Tractocamión',
                            'App\\Models\\Trailer' => 'Trailer',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('vehicle_id', null))
                        ->helperText('Seleccione si el mantenimiento es para un tractocamión o trailer'),

                    Forms\Components\Select::make('vehicle_id')
                        ->label('Vehículo')
                        ->options(function (Forms\Get $get) {
                            $vehicleType = $get('vehicle_type');
                            if ($vehicleType === 'App\\Models\\Vehicle') {
                                return Vehicle::pluck('name', 'id');
                            } elseif ($vehicleType === 'App\\Models\\Trailer') {
                                return Trailer::pluck('name', 'id');
                            }
                            return [];
                        })
                        ->required()
                        ->searchable()
                        ->helperText('Busque y seleccione el vehículo específico')
                        ->disabled(fn (Forms\Get $get) => !$get('vehicle_type')),

                    Forms\Components\Select::make('maintenance_type')
                        ->label('Tipo de Mantenimiento')
                        ->options([
                            'preventivo' => 'Preventivo - Mantenimiento programado regular',
                            'correctivo' => 'Correctivo - Reparación de fallas detectadas',
                            'emergencia' => 'Emergencia - Reparación urgente no programada',
                            'inspeccion' => 'Inspección - Revisión general del vehículo',
                        ])
                        ->required()
                        ->helperText('Seleccione el tipo de mantenimiento realizado'),

                    Forms\Components\DatePicker::make('date')
                        ->label('Fecha del Mantenimiento')
                        ->required()
                        ->default(now())
                        ->maxDate(now())
                        ->helperText('Fecha en que se realizó el mantenimiento'),
                ])
                ->columns(2),

            // Step 2: Work Description
            Wizard\Step::make('Descripción del Trabajo')
                ->icon('heroicon-o-document-text')
                ->description('Describa el trabajo realizado')
                ->schema([
                    Forms\Components\MarkdownEditor::make('description')
                        ->label('Descripción Detallada')
                        ->required()
                        ->helperText('Describa el trabajo realizado, problemas encontrados y soluciones aplicadas')
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'bulletList',
                            'orderedList',
                            'heading',
                        ]),

                    Forms\Components\Select::make('mechanic_id')
                        ->label('Mecánico Responsable')
                        ->relationship('mechanic', 'name', fn ($query) => $query->workshopUsers())
                        ->searchable(['name', 'email'])
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->name} - {$record->role}")
                        ->helperText('Seleccione el mecánico que realizó el trabajo')
                        ->visible(fn () => $formFieldResolver->isFieldVisible($user, MaintenanceRecord::class, 'mechanic_id'))
                        ->disabled(fn () => !$formFieldResolver->isFieldEditable($user, MaintenanceRecord::class, 'mechanic_id')),
                ])
                ->columns(1),

            // Step 3: Parts and Inventory
            Wizard\Step::make('Refacciones e Inventario')
                ->icon('heroicon-o-wrench-screwdriver')
                ->description('Registre las refacciones utilizadas')
                ->schema([
                    Forms\Components\Repeater::make('products_used')
                        ->label('Productos Utilizados')
                        ->schema([
                            Forms\Components\Select::make('spare_part_id')
                                ->label('Refacción')
                                ->options(SparePart::all()->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) use ($stockValidationService) {
                                    if ($state) {
                                        $sparePart = SparePart::find($state);
                                        if ($sparePart) {
                                            $set('available_stock', $sparePart->stock_quantity);
                                            $set('unit_cost', $sparePart->unit_cost);

                                            $quantity = $get('quantity_used') ?: 1;
                                            $set('quantity_used', $quantity);

                                            // Validate stock
                                            $validation = $stockValidationService->validatePartAvailability($state, $quantity);
                                            if (!$validation->isValid) {
                                                $set('stock_warning', $validation->errors[0] ?? 'Stock insuficiente');
                                            } else {
                                                $set('stock_warning', null);
                                            }

                                            $set('item_total', (float)$quantity * (float)$sparePart->unit_cost);
                                        }
                                    }
                                })
                                ->columnSpan(3),

                            Forms\Components\Placeholder::make('available_stock')
                                ->label('Stock Disponible')
                                ->content(fn (Forms\Get $get) => 
                                    $get('available_stock') !== null
                                        ? $get('available_stock') . ' unidades'
                                        : 'N/A'
                                )
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('quantity_used')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->default(1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) use ($stockValidationService) {
                                    if (empty($state) || $state === null || $state === '') {
                                        $state = 1;
                                        $set('quantity_used', 1);
                                    }
                                    $unitCost = $get('unit_cost') ?? 0;
                                    $set('item_total', (float)$state * (float)$unitCost);

                                    // Real-time stock validation
                                    $sparePartId = $get('spare_part_id');
                                    if ($sparePartId) {
                                        $validation = $stockValidationService->validatePartAvailability($sparePartId, $state);
                                        if (!$validation->isValid) {
                                            $set('stock_warning', $validation->errors[0] ?? 'Stock insuficiente');
                                        } else {
                                            $set('stock_warning', null);
                                        }
                                    }
                                })
                                ->rules([
                                    'required',
                                    'numeric',
                                    'min:0.01',
                                    fn (Forms\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if (empty($value) || $value === null) {
                                            $fail("La cantidad es requerida.");
                                            return;
                                        }
                                        $stock = $get('available_stock');
                                        if ($stock !== null && $value > $stock) {
                                            $fail("La cantidad ({$value}) excede el stock disponible ({$stock}).");
                                        }
                                    },
                                ])
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('unit_cost')
                                ->label('Costo Unit.')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('item_total')
                                ->label('Total')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),

                            Forms\Components\Hidden::make('available_stock')
                                ->dehydrated(false),

                            Forms\Components\Hidden::make('stock_warning')
                                ->dehydrated(false),

                            Forms\Components\Placeholder::make('stock_warning_display')
                                ->label('')
                                ->content(fn (Forms\Get $get) => $get('stock_warning'))
                                ->visible(fn (Forms\Get $get) => $get('stock_warning') !== null)
                                ->extraAttributes(['class' => 'text-danger-600 font-semibold'])
                                ->columnSpan(7),

                            Forms\Components\Textarea::make('notes')
                                ->label('Notas')
                                ->rows(2)
                                ->columnSpan(7),
                        ])
                        ->columns(7)
                        ->defaultItems(0)
                        ->addActionLabel('Agregar Refacción')
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string =>
                            $state['spare_part_id']
                                ? SparePart::find($state['spare_part_id'])?->name
                                : 'Nueva Refacción'
                        )
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $total = 0;
                            if (is_array($state)) {
                                foreach ($state as $item) {
                                    if (isset($item['spare_part_id']) && isset($item['quantity_used'])) {
                                        $sparePart = SparePart::find($item['spare_part_id']);
                                        if ($sparePart) {
                                            $total += (float)$item['quantity_used'] * (float)$sparePart->unit_cost;
                                        }
                                    }
                                }
                            }
                            $set('../../calculated_total', $total);
                        })
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('calculated_total')
                        ->label('Costo Total del Mantenimiento')
                        ->content(function (Forms\Get $get) {
                            $total = $get('calculated_total') ?? 0;
                            return '$' . number_format($total, 2) . ' MXN';
                        })
                        ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),
                ])
                ->columns(1),

            // Step 4: Evidence and Documentation
            Wizard\Step::make('Evidencias y Documentación')
                ->icon('heroicon-o-camera')
                ->description('Adjunte fotos y documentos del trabajo')
                ->schema([
                    Forms\Components\FileUpload::make('new_attachments')
                        ->label('Archivos Adjuntos')
                        ->multiple()
                        ->image()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                        ->maxSize(10240)
                        ->directory('maintenance-records-temp')
                        ->disk('local')
                        ->visibility('private')
                        ->downloadable()
                        ->previewable()
                        ->reorderable()
                        ->imageEditor()
                        ->helperText('Suba fotos del trabajo realizado, facturas o comprobantes. Formatos: PDF, JPG, PNG. Máximo 10MB por archivo.')
                        ->columnSpanFull()
                        ->dehydrated(false),
                ])
                ->columns(1),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Guardar los productos para procesarlos después
        $this->productsUsed = $data['products_used'] ?? [];
        $this->newAttachments = $data['new_attachments'] ?? [];

        // Remover products_used y attachments del data para que no intente guardarlos en maintenance_records
        unset($data['products_used']);
        unset($data['calculated_total']);
        unset($data['new_attachments']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $maintenanceRecord = $this->record;
        $stockValidationService = app(StockValidationService::class);
        $errors = [];
        $warnings = [];

        // Procesar productos usados con validación
        if (!empty($this->productsUsed)) {
            foreach ($this->productsUsed as $productData) {
                if (isset($productData['spare_part_id']) && isset($productData['quantity_used'])) {
                    try {
                        // Validar disponibilidad antes de crear
                        $validation = $stockValidationService->validatePartAvailability(
                            $productData['spare_part_id'],
                            $productData['quantity_used']
                        );

                        if (!$validation->isValid) {
                            $errors[] = $validation->errors[0] ?? 'Error de validación de stock';
                            continue;
                        }

                        // Crear el registro de uso de producto
                        ProductUsage::create([
                            'maintenance_record_id' => $maintenanceRecord->id,
                            'spare_part_id' => $productData['spare_part_id'],
                            'quantity_used' => $productData['quantity_used'],
                            'date_used' => $maintenanceRecord->date,
                            'notes' => $productData['notes'] ?? null,
                            'used_by' => auth()->id(),
                        ]);

                        // Actualizar stock de la refacción
                        $sparePart = SparePart::find($productData['spare_part_id']);
                        if ($sparePart) {
                            $sparePart->decrement('stock_quantity', $productData['quantity_used']);

                            // Check for low stock warning
                            if ($sparePart->stock_quantity <= 10) {
                                $warnings[] = "⚠️ Stock bajo para {$sparePart->name}: {$sparePart->stock_quantity} unidades restantes";
                            }
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Error procesando refacción: {$e->getMessage()}";
                        \Log::error('Error processing product usage', [
                            'product_data' => $productData,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Procesar attachments
        $attachmentCount = 0;
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
                        $attachmentCount++;

                    } catch (\Exception $e) {
                        $errors[] = "Error subiendo archivo {$fileName}: {$e->getMessage()}";
                        \Log::error('Failed to move file to MinIO', [
                            'local_path' => $localFilePath,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Preparar resumen
        $totalProducts = count($this->productsUsed ?? []);
        $successfulProducts = $totalProducts - count($errors);
        $totalCost = $maintenanceRecord->calculated_cost;

        // Mostrar notificación de éxito con resumen
        $body = "✅ Productos procesados: {$successfulProducts}/{$totalProducts}\n";
        $body .= "📎 Archivos adjuntos: {$attachmentCount}\n";
        $body .= "💰 Costo total: $" . number_format($totalCost, 2) . " MXN";

        Notification::make()
            ->title('Mantenimiento registrado exitosamente')
            ->body($body)
            ->success()
            ->duration(5000)
            ->send();

        // Mostrar advertencias si existen
        if (!empty($warnings)) {
            Notification::make()
                ->title('Advertencias de Stock')
                ->body(implode("\n", $warnings))
                ->warning()
                ->duration(8000)
                ->send();
        }

        // Mostrar errores si existen
        if (!empty($errors)) {
            Notification::make()
                ->title('Algunos elementos no se procesaron')
                ->body(implode("\n", $errors))
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    protected ?array $productsUsed = null;
    protected ?array $newAttachments = null;
}
