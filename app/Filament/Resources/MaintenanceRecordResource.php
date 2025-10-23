<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceRecordResource\Pages;
use App\Models\MaintenanceRecord;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Services\AutoAssignmentService;
use App\Services\Permissions\MaintenanceRecordPermissionService;
use App\Contracts\FormFieldResolverInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MaintenanceRecordResource extends Resource
{
    protected static ?string $model = MaintenanceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Registros de Mantenimiento';

    protected static ?string $modelLabel = 'Registro de Mantenimiento';

    protected static ?string $pluralModelLabel = 'Registros de Mantenimiento';

    protected static ?string $navigationGroup = 'Mantenimiento';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $formFieldResolver = app(FormFieldResolverInterface::class);
        $permissionService = app(MaintenanceRecordPermissionService::class);
        
        if (!$user) {
            return $form->schema([]);
        }

        $resolvedFields = $formFieldResolver->resolveFieldsForUser($user, MaintenanceRecord::class);
        $hiddenFields = $formFieldResolver->getHiddenFields($user, MaintenanceRecord::class);
        $defaultValues = $formFieldResolver->getDefaultValues($user, MaintenanceRecord::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Información del Mantenimiento')
                    ->schema([
                        Forms\Components\Select::make('vehicle_type')
                            ->label('Tipo de Vehículo')
                            ->options([
                                'App\\Models\\Vehicle' => 'Tractocamión',
                                'App\\Models\\Trailer' => 'Trailer',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('vehicle_id', null)),

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
                            ->searchable(),

                        Forms\Components\Select::make('maintenance_type')
                            ->label('Tipo de Mantenimiento')
                            ->options([
                                'preventivo' => 'Preventivo',
                                'correctivo' => 'Correctivo',
                                'emergencia' => 'Emergencia',
                                'inspeccion' => 'Inspección',
                            ])
                            ->required(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->default($defaultValues['date'] ?? now()),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Productos Utilizados')
                    ->schema([
                        Forms\Components\Repeater::make('products_used')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('spare_part_id')
                                    ->label('Refacción')
                                    ->options(SparePart::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                        if ($state) {
                                            $sparePart = SparePart::find($state);
                                            if ($sparePart) {
                                                $set('available_stock', $sparePart->stock_quantity);
                                                $set('unit_cost', $sparePart->unit_cost);

                                                // Establecer cantidad default si está vacía
                                                $quantity = $get('quantity_used');
                                                if (empty($quantity) || $quantity === null || $quantity === '') {
                                                    $quantity = 1;
                                                    $set('quantity_used', 1);
                                                }

                                                // Calcular costo total de este item (convertir a float)
                                                $set('item_total', (float)$quantity * (float)$sparePart->unit_cost);
                                            }
                                        }
                                    })
                                    ->columnSpan(3),

                                Forms\Components\Placeholder::make('available_stock')
                                    ->label('Stock')
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
                                    ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                        // Si está vacío, establecer default de 1
                                        if (empty($state) || $state === null || $state === '') {
                                            $state = 1;
                                            $set('quantity_used', 1);
                                        }
                                        $unitCost = $get('unit_cost') ?? 0;
                                        $set('item_total', (float)$state * (float)$unitCost);
                                    })
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'min:0.01',
                                        function (Forms\Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                if (empty($value) || $value === null) {
                                                    $fail("La cantidad es requerida.");
                                                    return;
                                                }
                                                $stock = $get('available_stock');
                                                if ($stock !== null && $value > $stock) {
                                                    $fail("La cantidad ({$value}) excede el stock disponible ({$stock}).");
                                                }
                                            };
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

                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas')
                                    ->rows(2)
                                    ->columnSpan(7),
                            ])
                            ->columns(7)
                            ->defaultItems(0)
                            ->addActionLabel('Agregar Producto')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                $state['spare_part_id']
                                    ? SparePart::find($state['spare_part_id'])?->name
                                    : 'Nuevo Producto'
                            )
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Calcular total general
                                $total = 0;
                                if (is_array($state)) {
                                    foreach ($state as $item) {
                                        if (isset($item['spare_part_id']) && isset($item['quantity_used'])) {
                                            $sparePart = SparePart::find($item['spare_part_id']);
                                            if ($sparePart) {
                                                // Convertir a float para evitar errores de tipo
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
                    ->description('Registre los productos/refacciones utilizados en este mantenimiento. El costo se calculará automáticamente.')
                    ->collapsible()
                    ->collapsed(fn (string $operation) => $operation === 'edit'),

                Forms\Components\Section::make('Evidencias / Comprobantes')
                    ->schema([
                        Forms\Components\FileUpload::make('new_attachments')
                            ->label('Subir Nuevos Archivos')
                            ->multiple()
                            ->image()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(10240) // 10MB
                            ->directory('maintenance-records-temp')
                            ->disk('local')
                            ->visibility('private')
                            ->downloadable()
                            ->previewable()
                            ->reorderable()
                            ->imageEditor()
                            ->helperText('Suba fotos del trabajo realizado, facturas o comprobantes. Formatos: PDF, JPG, PNG. Máximo 10MB.')
                            ->columnSpanFull()
                            ->dehydrated(false),

                        Forms\Components\Placeholder::make('existing_attachments')
                            ->label('Archivos Actuales')
                            ->content(function ($record) {
                                if (!$record || !$record->exists) {
                                    return 'No hay archivos adjuntos aún. Los archivos se guardarán después de crear el registro.';
                                }

                                $attachments = $record->attachments;
                                if ($attachments->isEmpty()) {
                                    return 'No hay archivos adjuntos.';
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($attachments as $attachment) {
                                    $url = route('attachments.download', $attachment);
                                    $icon = $attachment->isImage() ? '📷' : '📄';
                                    $html .= '<div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">';
                                    $html .= '<div class="flex items-center gap-2">';
                                    $html .= '<span>' . $icon . '</span>';
                                    $html .= '<a href="' . $url . '" target="_blank" class="text-primary-600 hover:underline">' . $attachment->file_name . '</a>';
                                    $html .= '<span class="text-xs text-gray-500">(' . $attachment->human_file_size . ')</span>';
                                    $html .= '</div>';
                                    $html .= '<button type="button" wire:click="deleteAttachment(' . $attachment->id . ')" class="text-danger-600 hover:text-danger-700 text-sm">Eliminar</button>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->visible(fn ($record) => $record && $record->exists),
                    ])
                    ->collapsible()
                    ->collapsed(fn (string $operation) => $operation === 'edit'),

                Forms\Components\Section::make('Asignación')
                    ->schema(array_filter([
                        // Only show mechanic field if visible for current user role
                        $formFieldResolver->isFieldVisible($user, MaintenanceRecord::class, 'mechanic_id') ?
                            Forms\Components\Select::make('mechanic_id')
                                ->label('Mecánico')
                                ->relationship('mechanic', 'name', fn ($query) => $query->workshopUsers())
                                ->searchable(['name', 'email'])
                                ->preload()
                                ->default($defaultValues['mechanic_id'] ?? null)
                                ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->name} - {$record->role} ({$record->email})")
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nombre')
                                        ->required(),
                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->required(),
                                    Forms\Components\Hidden::make('role')
                                        ->default('workshop'),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    $user = User::create($data);
                                    return $user->getKey();
                                })
                                ->helperText(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, MaintenanceRecord::class, 'mechanic_id');
                                    return $fieldInfo['helpText'] ?? 'Selecciona el mecánico responsable del mantenimiento';
                                })
                                ->hint(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, MaintenanceRecord::class, 'mechanic_id');
                                    if ($fieldInfo['autoAssigned']) {
                                        return 'Asignado automáticamente';
                                    }
                                    if (!$fieldInfo['editable'] && $fieldInfo['restrictionReason']) {
                                        return 'Campo restringido';
                                    }
                                    return null;
                                })
                                ->hintIcon(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, MaintenanceRecord::class, 'mechanic_id');
                                    if ($fieldInfo['autoAssigned']) {
                                        return 'heroicon-o-information-circle';
                                    }
                                    if (!$fieldInfo['editable']) {
                                        return 'heroicon-o-lock-closed';
                                    }
                                    return null;
                                })
                                ->hintColor(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, MaintenanceRecord::class, 'mechanic_id');
                                    if ($fieldInfo['autoAssigned']) {
                                        return 'info';
                                    }
                                    if (!$fieldInfo['editable']) {
                                        return 'warning';
                                    }
                                    return null;
                                })
                                ->disabled(function () use ($user, $formFieldResolver) {
                                    return !$formFieldResolver->isFieldEditable($user, MaintenanceRecord::class, 'mechanic_id');
                                })
                            : Forms\Components\Placeholder::make('mechanic_id_restriction')
                                ->label('Mecánico')
                                ->content(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, MaintenanceRecord::class, 'mechanic_id');
                                    return view('filament.components.field-restriction', [
                                        'reason' => $fieldInfo['restrictionReason'] ?? 'Este campo no está disponible para tu rol.',
                                    ]);
                                }),
                    ]))
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['mechanic', 'vehicle', 'attachments']))
            ->columns([
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Models\\Vehicle' => 'Tractocamión',
                        'App\\Models\\Trailer' => 'Trailer',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'App\\Models\\Vehicle' => 'success',
                        'App\\Models\\Trailer' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('vehicle_id')
                    ->label('Vehículo')
                    ->formatStateUsing(function ($record) {
                        if ($record->vehicle_type === 'App\\Models\\Vehicle') {
                            $vehicle = Vehicle::find($record->vehicle_id);
                            return $vehicle ? $vehicle->display_name : 'N/A';
                        } elseif ($record->vehicle_type === 'App\\Models\\Trailer') {
                            $trailer = Trailer::find($record->vehicle_id);
                            return $trailer ? $trailer->display_name : 'N/A';
                        }
                        return 'N/A';
                    }),

                Tables\Columns\TextColumn::make('maintenance_type')
                    ->label('Tipo de Mantenimiento')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'preventivo' => 'Preventivo',
                        'correctivo' => 'Correctivo',
                        'emergencia' => 'Emergencia',
                        'inspeccion' => 'Inspección',
                        default => $state,
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('calculated_cost')
                    ->label('Costo Total')
                    ->money('MXN')
                    ->sortable(query: function ($query, $direction) {
                        // Custom sorting for calculated cost
                        return $query->withCount(['productUsages as calculated_cost' => function ($query) {
                            $query->selectRaw('COALESCE(SUM(product_usages.quantity_used * spare_parts.unit_cost), 0)')
                                ->join('spare_parts', 'spare_parts.id', '=', 'product_usages.spare_part_id');
                        }])->orderBy('calculated_cost', $direction);
                    })
                    ->tooltip(function ($record) {
                        $count = $record->productUsages()->count();
                        return $count > 0
                            ? "Calculado de {$count} producto(s) usado(s)"
                            : 'Sin productos registrados';
                    })
                    ->color(fn ($record) => $record->calculated_cost > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mecánico')
                    ->sortable()
                    ->searchable()
                    ->placeholder('No asignado'),

                Tables\Columns\IconColumn::make('has_attachments')
                    ->label('Evidencias')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record->attachments()->exists())
                    ->tooltip(fn ($record) => $record->attachments()->count() > 0
                        ? $record->attachments()->count() . ' archivo(s) adjunto(s)'
                        : 'Sin archivos'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->label('Tipo de Vehículo')
                    ->options([
                        'App\\Models\\Vehicle' => 'Tractocamión',
                        'App\\Models\\Trailer' => 'Trailer',
                    ]),

                Tables\Filters\SelectFilter::make('maintenance_type')
                    ->label('Tipo de Mantenimiento')
                    ->options([
                        'preventivo' => 'Preventivo',
                        'correctivo' => 'Correctivo',
                        'emergencia' => 'Emergencia',
                        'inspeccion' => 'Inspección',
                    ]),

                Tables\Filters\SelectFilter::make('mechanic_id')
                    ->label('Mecánico')
                    ->relationship('mechanic', 'name', fn ($query) => $query->workshopUsers())
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(MaintenanceRecordPermissionService::class);
                        return $permissionService->canView($user, $record);
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(MaintenanceRecordPermissionService::class);
                        return $permissionService->canEdit($user, $record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(MaintenanceRecordPermissionService::class);
                        return $permissionService->canDelete($user, $record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function () {
                            $user = Auth::user();
                            if (!$user) return false;
                            $permissionService = app(MaintenanceRecordPermissionService::class);
                            return $permissionService->canDelete($user, new MaintenanceRecord());
                        }),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenanceRecords::route('/'),
            'create' => Pages\CreateMaintenanceRecord::route('/create'),
            'edit' => Pages\EditMaintenanceRecord::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) {
            // Durante la carga inicial del panel, permitir que se registre el recurso
            // Los permisos se verificarán cuando el usuario realmente acceda
            return true;
        }
        
        $permissionService = app(MaintenanceRecordPermissionService::class);
        return $permissionService->canViewAny($user);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(MaintenanceRecordPermissionService::class);
        return $permissionService->canCreate($user);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(MaintenanceRecordPermissionService::class);
        return $permissionService->canEdit($user, $record);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(MaintenanceRecordPermissionService::class);
        return $permissionService->canDelete($user, $record);
    }
}
