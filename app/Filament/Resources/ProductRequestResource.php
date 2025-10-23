<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductRequestResource\Pages;
use App\Models\ProductRequest;
use App\Models\SparePart;
use App\Models\User;
use App\Services\AutoAssignmentService;
use App\Services\Permissions\ProductRequestPermissionService;
use App\Contracts\FormFieldResolverInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ProductRequestResource extends Resource
{
    protected static ?string $model = ProductRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Solicitudes de Productos';

    protected static ?string $modelLabel = 'Solicitud de Producto';

    protected static ?string $pluralModelLabel = 'Solicitudes de Productos';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $formFieldResolver = app(FormFieldResolverInterface::class);
        $permissionService = app(ProductRequestPermissionService::class);

        if (!$user) {
            return $form->schema([]);
        }

        $resolvedFields = $formFieldResolver->resolveFieldsForUser($user, ProductRequest::class);
        $hiddenFields = $formFieldResolver->getHiddenFields($user, ProductRequest::class);
        $defaultValues = $formFieldResolver->getDefaultValues($user, ProductRequest::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Solicitud')
                    ->schema([
                        Forms\Components\Select::make('spare_part_id')
                            ->label('Refacción')
                            ->relationship('sparePart', 'name')
                            ->required()
                            ->searchable(['name', 'part_number', 'brand'])
                            ->getOptionLabelFromRecordUsing(fn (SparePart $record): string =>
                                "{$record->name} ({$record->part_number}) - {$record->brand}"
                            )
                            ->preload()
                            ->live()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('part_number')
                                    ->label('Número de Parte')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(SparePart::class, 'part_number')
                                    ->helperText('Código único del producto'),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('brand')
                                    ->label('Marca')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Costo Unitario Estimado')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->helperText('Puede actualizar el costo cuando reciba el producto'),

                                Forms\Components\TextInput::make('location')
                                    ->label('Ubicación en Almacén (Opcional)')
                                    ->maxLength(255)
                                    ->helperText('Ej: Estante A-1, Pasillo 3, etc.'),
                            ])
                            ->createOptionUsing(function (array $data, Forms\Set $set): int {
                                // Buscar si ya existe una refacción similar
                                $existing = SparePart::where(function ($query) use ($data) {
                                    $query->where('part_number', $data['part_number'])
                                          ->orWhere(function ($q) use ($data) {
                                              $q->where('name', $data['name'])
                                                ->where('brand', $data['brand']);
                                          });
                                })->first();

                                if ($existing) {
                                    // Si existe, mostrar notificación y retornar el ID existente
                                    Notification::make()
                                        ->title('Refacción existente encontrada')
                                        ->body("Se encontró una refacción similar: {$existing->name} ({$existing->part_number}). Se ha seleccionado automáticamente.")
                                        ->info()
                                        ->duration(5000)
                                        ->send();

                                    return $existing->id;
                                }

                                // Si no existe, crear nueva con stock en 0
                                $sparePart = SparePart::create([
                                    'part_number' => $data['part_number'],
                                    'name' => $data['name'],
                                    'brand' => $data['brand'],
                                    'stock_quantity' => 0, // Nuevo producto sin stock
                                    'unit_cost' => $data['unit_cost'] ?? 0,
                                    'location' => $data['location'] ?? null,
                                ]);

                                Notification::make()
                                    ->title('Refacción creada exitosamente')
                                    ->body("Nueva refacción '{$sparePart->name}' agregada al inventario con stock en 0.")
                                    ->success()
                                    ->duration(5000)
                                    ->send();

                                return $sparePart->id;
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $sparePart = SparePart::find($state);
                                    if ($sparePart) {
                                        $set('current_stock', $sparePart->stock_quantity);
                                    }
                                }
                            })
                            ->helperText('Puede buscar por nombre, número de parte o marca. Si no existe, puede crear una nueva.'),

                        Forms\Components\Placeholder::make('current_stock')
                            ->label('Stock Actual')
                            ->content(function (Forms\Get $get) {
                                $sparePartId = $get('spare_part_id');
                                if ($sparePartId) {
                                    $sparePart = SparePart::find($sparePartId);
                                    return $sparePart ? $sparePart->stock_quantity . ' unidades' : 'N/A';
                                }
                                return 'Seleccione una refacción';
                            }),

                        Forms\Components\TextInput::make('quantity_requested')
                            ->label('Cantidad Solicitada')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridad')
                            ->options([
                                'low' => 'Baja',
                                'medium' => 'Media',
                                'high' => 'Alta',
                                'urgent' => 'Urgente',
                            ])
                            ->required()
                            ->default($defaultValues['priority'] ?? 'medium'),

                        Forms\Components\Textarea::make('justification')
                            ->label('Justificación')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Explique por qué necesita este producto y su uso previsto.'),

                        // Only show requested_by field if visible for current user role
                        $formFieldResolver->isFieldVisible($user, ProductRequest::class, 'requested_by') ?
                        Forms\Components\Select::make('requested_by')
                            ->label('Solicitado por')
                            ->relationship('requestedBy', 'name', fn ($query) => $query->workshopUsers())
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->default($defaultValues['requested_by'] ?? null)
                            ->getOptionLabelFromRecordUsing(fn(User $record): string => "{$record->name} - {$record->role} ({$record->email})")
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
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'requested_by');
                                return $fieldInfo['helpText'] ?? 'Selecciona el usuario que hace la solicitud';
                            })
                            ->hint(function () use ($user, $formFieldResolver) {
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'requested_by');
                                if ($fieldInfo['autoAssigned']) {
                                    return 'Asignado automáticamente';
                                }
                                if (!$fieldInfo['editable'] && $fieldInfo['restrictionReason']) {
                                    return 'Campo restringido';
                                }
                                return null;
                            })
                            ->hintIcon(function () use ($user, $formFieldResolver) {
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'requested_by');
                                if ($fieldInfo['autoAssigned']) {
                                    return 'heroicon-o-information-circle';
                                }
                                if (!$fieldInfo['editable']) {
                                    return 'heroicon-o-lock-closed';
                                }
                                return null;
                            })
                            ->hintColor(function () use ($user, $formFieldResolver) {
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'requested_by');
                                if ($fieldInfo['autoAssigned']) {
                                    return 'info';
                                }
                                if (!$fieldInfo['editable']) {
                                    return 'warning';
                                }
                                return null;
                            })
                            ->disabled(function () use ($user, $formFieldResolver) {
                                return !$formFieldResolver->isFieldEditable($user, ProductRequest::class, 'requested_by');
                            })
                        : Forms\Components\Placeholder::make('requested_by_restriction')
                            ->label('Solicitado por')
                            ->content(function () use ($user, $formFieldResolver) {
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'requested_by');
                                return view('filament.components.field-restriction', [
                                    'reason' => $fieldInfo['restrictionReason'] ?? 'Este campo no está disponible para tu rol.',
                                ]);
                            }),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'approved' => 'Aprobado',
                                'ordered' => 'Ordenado',
                                'received' => 'Recibido',
                            ])
                            ->default($defaultValues['status'] ?? 'pending')
                            ->helperText(function (string $operation) use ($user, $formFieldResolver) {
                                if ($operation === 'create') {
                                    return 'El estado inicial será "Pendiente"';
                                }
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'status');
                                return $fieldInfo['helpText'];
                            })
                            ->hint(function (string $operation) use ($user, $formFieldResolver) {
                                if ($operation === 'create') {
                                    return null;
                                }
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'status');
                                if (!$fieldInfo['editable'] && $fieldInfo['restrictionReason']) {
                                    return 'Campo restringido';
                                }
                                return null;
                            })
                            ->hintIcon(function (string $operation) use ($user, $formFieldResolver) {
                                if ($operation === 'create') {
                                    return null;
                                }
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'status');
                                if (!$fieldInfo['editable']) {
                                    return 'heroicon-o-lock-closed';
                                }
                                return null;
                            })
                            ->hintColor(function (string $operation) use ($user, $formFieldResolver) {
                                if ($operation === 'create') {
                                    return null;
                                }
                                $fieldInfo = $formFieldResolver->getFieldWithHelp($user, ProductRequest::class, 'status');
                                if (!$fieldInfo['editable']) {
                                    return 'warning';
                                }
                                return null;
                            })
                            ->disabled(function (string $operation) use ($user, $formFieldResolver) {
                                if ($operation === 'create')
                                    return true;
                                if (!$user)
                                    return true;
                                return !$formFieldResolver->isFieldEditable($user, ProductRequest::class, 'status');
                            })
                            ->visible(function (string $operation) use ($user, $formFieldResolver) {
                                if ($operation === 'create')
                                    return false;
                                if (!$user)
                                    return false;
                                return $formFieldResolver->isFieldVisible($user, ProductRequest::class, 'status');
                            }),

                        Forms\Components\Select::make('approved_by')
                            ->label('Aprobado por')
                            ->relationship('approvedBy', 'name', fn ($query) => $query->whereIn('role', ['super_admin', 'administrador']))
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn(User $record): string => "{$record->name} - {$record->role} ({$record->email})")
                            ->disabled(function (string $operation) use ($user, $formFieldResolver) {
                                if ($operation === 'create')
                                    return true;
                                if (!$user)
                                    return true;
                                return !$formFieldResolver->isFieldEditable($user, ProductRequest::class, 'approved_by');
                            })
                            ->visible(function (string $operation) use ($user, $formFieldResolver) {
                                if ($operation === 'create')
                                    return false;
                                if (!$user)
                                    return false;
                                return $formFieldResolver->isFieldVisible($user, ProductRequest::class, 'approved_by');
                            }),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Fecha de Aprobación')
                            ->disabled()
                            ->visible(fn(string $operation): bool => $operation !== 'create'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['sparePart', 'requestedBy', 'approvedBy']))
            ->columns([
                Tables\Columns\TextColumn::make('sparePart.name')
                    ->label('Refacción')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sparePart.part_number')
                    ->label('Número de Parte')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quantity_requested')
                    ->label('Cantidad Solicitada')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'ordered' => 'Ordenado',
                        'received' => 'Recibido',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'ordered' => 'primary',
                        'received' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Solicitado por')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Aprobado por')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Fecha de Solicitud')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Fecha de Aprobación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('spare_part_id')
                    ->label('Refacción')
                    ->relationship('sparePart', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'ordered' => 'Ordenado',
                        'received' => 'Recibido',
                    ]),

                Tables\Filters\SelectFilter::make('requested_by')
                    ->label('Solicitado por')
                    ->relationship('requestedBy', 'name', fn ($query) => $query->workshopUsers())
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
                                fn(Builder $query, $date): Builder => $query->whereDate('requested_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('requested_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (ProductRequest $record): bool {
                        $user = Auth::user();
                        if (!$user)
                            return false;
                        $permissionService = app(ProductRequestPermissionService::class);
                        return $permissionService->canApprove($user, $record);
                    })
                    ->action(function (ProductRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Solicitud aprobada')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('mark_ordered')
                    ->label('Marcar como Ordenado')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('primary')
                    ->visible(function (ProductRequest $record): bool {
                        $user = Auth::user();
                        if (!$user)
                            return false;
                        $permissionService = app(ProductRequestPermissionService::class);
                        return $permissionService->canMarkAsOrdered($user, $record);
                    })
                    ->action(function (ProductRequest $record) {
                        $record->update(['status' => 'ordered']);

                        Notification::make()
                            ->title('Solicitud marcada como ordenada')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('mark_received')
                    ->label('Marcar como Recibido')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('success')
                    ->visible(function (ProductRequest $record): bool {
                        $user = Auth::user();
                        if (!$user)
                            return false;
                        $permissionService = app(ProductRequestPermissionService::class);
                        return $permissionService->canMarkAsReceived($user, $record);
                    })
                    ->action(function (ProductRequest $record) {
                        $record->update(['status' => 'received']);

                        Notification::make()
                            ->title('Solicitud marcada como recibida')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user)
                            return false;
                        $permissionService = app(ProductRequestPermissionService::class);
                        return $permissionService->canView($user, $record);
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user)
                            return false;
                        $permissionService = app(ProductRequestPermissionService::class);
                        return $permissionService->canEdit($user, $record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user)
                            return false;
                        $permissionService = app(ProductRequestPermissionService::class);
                        return $permissionService->canDelete($user, $record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Aprobar Seleccionadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(function (): bool {
                            $user = Auth::user();
                            if (!$user)
                                return false;
                            return $user->hasAdminAccess();
                        })
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} solicitudes aprobadas")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function (): bool {
                            $user = Auth::user();
                            if (!$user)
                                return false;
                            return $user->hasAdminAccess();
                        }),
                ]),
            ])
            ->defaultSort('requested_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductRequests::route('/'),
            'create' => Pages\CreateProductRequest::route('/create'),
            'view' => Pages\ViewProductRequest::route('/{record}'),
            'edit' => Pages\EditProductRequest::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        $permissionService = app(ProductRequestPermissionService::class);
        return $permissionService->canViewAny($user);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        $permissionService = app(ProductRequestPermissionService::class);
        return $permissionService->canCreate($user);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        $permissionService = app(ProductRequestPermissionService::class);
        return $permissionService->canEdit($user, $record);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        $permissionService = app(ProductRequestPermissionService::class);
        return $permissionService->canDelete($user, $record);
    }
}