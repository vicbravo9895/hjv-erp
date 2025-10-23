<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductUsageResource\Pages;
use App\Models\ProductUsage;
use App\Models\SparePart;
use App\Models\MaintenanceRecord;
use App\Services\AutoAssignmentService;
use App\Services\Permissions\ProductUsagePermissionService;
use App\Contracts\FormFieldResolverInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;

class ProductUsageResource extends Resource
{
    protected static ?string $model = ProductUsage::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Uso de Productos';

    protected static ?string $modelLabel = 'Uso de Producto';

    protected static ?string $pluralModelLabel = 'Uso de Productos';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $formFieldResolver = app(FormFieldResolverInterface::class);
        $permissionService = app(ProductUsagePermissionService::class);
        
        if (!$user) {
            return $form->schema([]);
        }

        $resolvedFields = $formFieldResolver->resolveFieldsForUser($user, ProductUsage::class);
        $hiddenFields = $formFieldResolver->getHiddenFields($user, ProductUsage::class);
        $defaultValues = $formFieldResolver->getDefaultValues($user, ProductUsage::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Información del Uso de Producto')
                    ->schema([
                        Forms\Components\Select::make('spare_part_id')
                            ->label('Refacción')
                            ->options(SparePart::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $sparePart = SparePart::find($state);
                                    if ($sparePart) {
                                        $set('available_stock', $sparePart->stock_quantity);
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('available_stock')
                            ->label('Stock Disponible')
                            ->content(function (Get $get) {
                                $sparePartId = $get('spare_part_id');
                                if ($sparePartId) {
                                    $sparePart = SparePart::find($sparePartId);
                                    return $sparePart ? $sparePart->stock_quantity . ' unidades' : 'N/A';
                                }
                                return 'Seleccione una refacción';
                            }),

                        Forms\Components\Select::make('maintenance_record_id')
                            ->label('Registro de Mantenimiento')
                            ->relationship('maintenanceRecord', 'id')
                            ->getOptionLabelFromRecordUsing(function (MaintenanceRecord $record): string {
                                $vehicleName = $record->vehicle ? $record->vehicle->display_name : 'N/A';
                                $dateFormatted = $record->date instanceof \Carbon\Carbon ? $record->date->format('d/m/Y') : 'N/A';
                                return "#{$record->id} - {$vehicleName} - {$dateFormatted}";
                            })
                            ->searchable(['id'])
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('quantity_used')
                            ->label('Cantidad Utilizada')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $sparePartId = $get('spare_part_id');
                                        if ($sparePartId && $value) {
                                            $sparePart = SparePart::find($sparePartId);
                                            if ($sparePart && $value > $sparePart->stock_quantity) {
                                                $fail("La cantidad utilizada ({$value}) no puede ser mayor al stock disponible ({$sparePart->stock_quantity}).");
                                            }
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\DatePicker::make('date_used')
                            ->label('Fecha de Uso')
                            ->required()
                            ->default($defaultValues['date_used'] ?? now())
                            ->maxDate(now()),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),

                        // Only show used_by field if visible for current user role
                        $formFieldResolver->isFieldVisible($user, ProductUsage::class, 'used_by') ? 
                            Forms\Components\Select::make('used_by')
                                ->label('Registrado por')
                                ->relationship('usedBy', 'name')
                                ->searchable(['name', 'email'])
                                ->preload()
                                ->default($defaultValues['used_by'] ?? null)
                                ->getOptionLabelFromRecordUsing(fn (\App\Models\User $record): string => "{$record->name} ({$record->email})")
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
                                    $user = \App\Models\User::create($data);
                                    return $user->getKey();
                                })
                                ->helperText('Selecciona el usuario que registra el uso del producto')
                                ->disabled(function () use ($user, $formFieldResolver) {
                                    return !$formFieldResolver->isFieldEditable($user, ProductUsage::class, 'used_by');
                                })
                            : null,

                        Forms\Components\FileUpload::make('attachments')
                            ->label('Archivos Adjuntos')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->maxSize(10240) // 10MB
                            ->directory('attachments/product-usage')
                            ->visibility('private')
                            ->columnSpanFull()
                            ->helperText('Adjunte facturas, comprobantes o fotos relacionadas con el uso del producto. Máximo 10MB por archivo.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['sparePart', 'maintenanceRecord.vehicle', 'usedBy', 'attachments']))
            ->columns([
                Tables\Columns\TextColumn::make('sparePart.name')
                    ->label('Refacción')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sparePart.part_number')
                    ->label('Número de Parte')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('maintenanceRecord.id')
                    ->label('Mantenimiento #')
                    ->formatStateUsing(fn ($record) => "#{$record->maintenance_record_id}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle_info')
                    ->label('Vehículo')
                    ->getStateUsing(function ($record) {
                        $maintenance = $record->maintenanceRecord;
                        if ($maintenance && $maintenance->vehicle) {
                            return $maintenance->vehicle->display_name;
                        }
                        return 'N/A';
                    }),

                Tables\Columns\TextColumn::make('quantity_used')
                    ->label('Cantidad Utilizada')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_used')
                    ->label('Fecha de Uso')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usedBy.name')
                    ->label('Registrado por')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('has_attachments')
                    ->label('Adjuntos')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->attachments()->exists())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('spare_part_id')
                    ->label('Refacción')
                    ->relationship('sparePart', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('maintenance_record_id')
                    ->label('Registro de Mantenimiento')
                    ->relationship('maintenanceRecord', 'id')
                    ->getOptionLabelFromRecordUsing(function (MaintenanceRecord $record): string {
                        $vehicleName = $record->vehicle ? $record->vehicle->display_name : 'N/A';
                        return "#{$record->id} - {$vehicleName}";
                    })
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
                                fn (Builder $query, $date): Builder => $query->whereDate('date_used', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_used', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('used_by')
                    ->label('Registrado por')
                    ->relationship('usedBy', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(ProductUsagePermissionService::class);
                        return $permissionService->canView($user, $record);
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(ProductUsagePermissionService::class);
                        return $permissionService->canEdit($user, $record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(ProductUsagePermissionService::class);
                        return $permissionService->canDelete($user, $record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function () {
                            $user = Auth::user();
                            if (!$user) return false;
                            // Only show bulk delete for admin users
                            return $user->hasAdminAccess();
                        }),
                ]),
            ])
            ->defaultSort('date_used', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductUsages::route('/'),
            'create' => Pages\CreateProductUsage::route('/create'),
            'view' => Pages\ViewProductUsage::route('/{record}'),
            'edit' => Pages\EditProductUsage::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(ProductUsagePermissionService::class);
        return $permissionService->canViewAny($user);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(ProductUsagePermissionService::class);
        return $permissionService->canCreate($user);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(ProductUsagePermissionService::class);
        return $permissionService->canEdit($user, $record);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(ProductUsagePermissionService::class);
        return $permissionService->canDelete($user, $record);
    }
}