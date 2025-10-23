<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TravelExpenseResource\Pages;
use App\Models\TravelExpense;
use App\Models\Trip;
use App\Models\User;
use App\Services\AutoAssignmentService;
use App\Services\Permissions\TravelExpensePermissionService;
use App\Contracts\FormFieldResolverInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TravelExpenseResource extends Resource
{
    protected static ?string $model = TravelExpense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    
    protected static ?string $navigationGroup = 'Finanzas';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'Gasto de Viaje';
    
    protected static ?string $pluralModelLabel = 'Gastos de Viaje';

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $formFieldResolver = app(FormFieldResolverInterface::class);
        $permissionService = app(TravelExpensePermissionService::class);
        
        if (!$user) {
            return $form->schema([]);
        }

        $resolvedFields = $formFieldResolver->resolveFieldsForUser($user, TravelExpense::class);
        $hiddenFields = $formFieldResolver->getHiddenFields($user, TravelExpense::class);
        $defaultValues = $formFieldResolver->getDefaultValues($user, TravelExpense::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Información del Gasto')
                    ->schema(array_filter([
                        Forms\Components\Select::make('trip_id')
                            ->label('Viaje')
                            ->required()
                            ->options(function () {
                                $user = Auth::user();
                                if ($user && $user->isOperator()) {
                                    return Trip::where('operator_id', $user->id)
                                        ->active()
                                        ->get()
                                        ->pluck('display_name', 'id');
                                }
                                return Trip::active()->get()->pluck('display_name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->default(function () {
                                $user = Auth::user();
                                if ($user && $user->isOperator()) {
                                    $activeTrip = Trip::where('operator_id', $user->id)
                                        ->active()
                                        ->orderBy('start_date', 'desc')
                                        ->first();
                                    return $activeTrip?->id;
                                }
                                return null;
                            }),

                        // Only show operator field if visible for current user role
                        $formFieldResolver->isFieldVisible($user, TravelExpense::class, 'operator_id') ?
                            Forms\Components\Select::make('operator_id')
                                ->label('Operador')
                                ->relationship('operator', 'name', fn ($query) => $query->operators())
                                ->searchable(['name', 'email'])
                                ->preload()
                                ->required()
                                ->default($defaultValues['operator_id'] ?? null)
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
                                        ->default('operator'),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    $user = User::create($data);
                                    return $user->getKey();
                                })
                                ->helperText(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'operator_id');
                                    return $fieldInfo['helpText'] ?? 'Selecciona el operador que incurrió en este gasto';
                                })
                                ->hint(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'operator_id');
                                    if ($fieldInfo['autoAssigned']) {
                                        return 'Asignado automáticamente';
                                    }
                                    if (!$fieldInfo['editable'] && $fieldInfo['restrictionReason']) {
                                        return 'Campo restringido';
                                    }
                                    return null;
                                })
                                ->hintIcon(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'operator_id');
                                    if ($fieldInfo['autoAssigned']) {
                                        return 'heroicon-o-information-circle';
                                    }
                                    if (!$fieldInfo['editable']) {
                                        return 'heroicon-o-lock-closed';
                                    }
                                    return null;
                                })
                                ->hintColor(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'operator_id');
                                    if ($fieldInfo['autoAssigned']) {
                                        return 'info';
                                    }
                                    if (!$fieldInfo['editable']) {
                                        return 'warning';
                                    }
                                    return null;
                                })
                                ->disabled(function () use ($user, $formFieldResolver) {
                                    return !$formFieldResolver->isFieldEditable($user, TravelExpense::class, 'operator_id');
                                })
                            : Forms\Components\Placeholder::make('operator_id_restriction')
                                ->label('Operador')
                                ->content(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'operator_id');
                                    return view('filament.components.field-restriction', [
                                        'reason' => $fieldInfo['restrictionReason'] ?? 'Este campo no está disponible para tu rol.',
                                    ]);
                                }),
                        
                        Forms\Components\Select::make('expense_type')
                            ->label('Tipo de Gasto')
                            ->required()
                            ->options(TravelExpense::EXPENSE_TYPES)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Clear fuel-specific fields when changing type
                                if ($state !== 'fuel') {
                                    $set('fuel_liters', null);
                                    $set('fuel_price_per_liter', null);
                                    $set('odometer_reading', null);
                                }
                            }),
                        
                        Forms\Components\TextInput::make('amount')
                            ->label('Monto Total')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0.01)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                // Auto-calculate fuel price per liter if fuel expense
                                if ($get('expense_type') === 'fuel' && $state && $get('fuel_liters')) {
                                    $pricePerLiter = $state / $get('fuel_liters');
                                    $set('fuel_price_per_liter', round($pricePerLiter, 3));
                                }
                            }),
                        
                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha del Gasto')
                            ->required()
                            ->default($defaultValues['date'] ?? now())
                            ->maxDate(now()),
                        
                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación')
                            ->maxLength(255)
                            ->placeholder('Ej: Gasolinera Shell, Km 45 Carretera México-Querétaro'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]))->columns(2),
                
                Forms\Components\Section::make('Información de Combustible')
                    ->schema([
                        Forms\Components\TextInput::make('fuel_liters')
                            ->label('Litros de Combustible')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->suffix('L')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                // Auto-calculate amount if fuel expense
                                if ($get('expense_type') === 'fuel' && $state && $get('fuel_price_per_liter')) {
                                    $amount = $state * $get('fuel_price_per_liter');
                                    $set('amount', round($amount, 2));
                                }
                            }),
                        
                        Forms\Components\TextInput::make('fuel_price_per_liter')
                            ->label('Precio por Litro')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0.001)
                            ->prefix('$')
                            ->suffix('/L')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                // Auto-calculate amount if fuel expense
                                if ($get('expense_type') === 'fuel' && $state && $get('fuel_liters')) {
                                    $amount = $get('fuel_liters') * $state;
                                    $set('amount', round($amount, 2));
                                }
                            }),
                        
                        Forms\Components\TextInput::make('odometer_reading')
                            ->label('Lectura del Odómetro')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('km')
                            ->placeholder('Ej: 125000'),
                    ])
                    ->columns(3)
                    ->visible(fn (Forms\Get $get): bool => $get('expense_type') === 'fuel'),
                
                Forms\Components\Section::make('Comprobantes')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Archivos Adjuntos')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(10240) // 10MB
                            ->directory('travel-expenses-temp')
                            ->disk('local') // Use local disk for temporary uploads
                            ->visibility('private')
                            ->downloadable()
                            ->previewable()
                            ->reorderable()
                            ->helperText('Suba facturas, recibos o comprobantes. Formatos permitidos: PDF, JPG, PNG. Máximo 10MB por archivo.')
                            ->columnSpanFull()
                            ->saveRelationshipsUsing(function ($component, $state, $record) {
                                if (!$record || empty($state)) return;
                                
                                // Delete existing attachments if new files are uploaded
                                $record->attachments()->delete();
                                
                                // Create new attachments by moving files from local to MinIO
                                foreach ($state as $localFilePath) {
                                    if (is_string($localFilePath)) {
                                        try {
                                            // Get file info from local storage
                                            $localDisk = \Storage::disk('local');
                                            $minioDisk = \Storage::disk('minio');
                                            
                                            if (!$localDisk->exists($localFilePath)) {
                                                continue;
                                            }
                                            
                                            $fileName = basename($localFilePath);
                                            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                            $fileContent = $localDisk->get($localFilePath);
                                            $fileSize = $localDisk->size($localFilePath);
                                            
                                            // Generate unique path for MinIO
                                            $minioPath = 'travel-expenses/' . $record->id . '/' . time() . '_' . $fileName;
                                            
                                            // Move file to MinIO
                                            $minioDisk->put($minioPath, $fileContent, 'private');
                                            
                                            // Determine MIME type from extension
                                            $mimeType = match ($extension) {
                                                'pdf' => 'application/pdf',
                                                'jpg', 'jpeg' => 'image/jpeg',
                                                'png' => 'image/png',
                                                'gif' => 'image/gif',
                                                default => 'application/octet-stream',
                                            };
                                            
                                            // Create attachment record
                                            $record->attachments()->create([
                                                'file_name' => $fileName,
                                                'file_path' => $minioPath,
                                                'file_size' => $fileSize,
                                                'mime_type' => $mimeType,
                                                'uploaded_by' => auth()->id(),
                                            ]);
                                            
                                            // Clean up local file
                                            $localDisk->delete($localFilePath);
                                            
                                        } catch (\Exception $e) {
                                            \Log::error('Failed to move file to MinIO and create attachment', [
                                                'local_path' => $localFilePath,
                                                'error' => $e->getMessage()
                                            ]);
                                        }
                                    }
                                }
                            })
                            ->loadStateFromRelationshipsUsing(function ($component, $record) {
                                if (!$record || !$record->exists) return [];
                                
                                // Return attachment info for display (but not editable)
                                return $record->attachments->map(function ($attachment) {
                                    return [
                                        'name' => $attachment->file_name,
                                        'size' => $attachment->file_size,
                                        'url' => route('attachments.download', $attachment),
                                    ];
                                })->toArray();
                            }),
                    ]),
                
                Forms\Components\Section::make('Estado')
                    ->schema([
                        $formFieldResolver->isFieldVisible($user, TravelExpense::class, 'status') ?
                            Forms\Components\Select::make('status')
                                ->label('Estado')
                                ->options(TravelExpense::STATUSES)
                                ->default($defaultValues['status'] ?? 'pending')
                                ->required()
                                ->helperText(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'status');
                                    return $fieldInfo['helpText'];
                                })
                                ->hint(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'status');
                                    if (!$fieldInfo['editable'] && $fieldInfo['restrictionReason']) {
                                        return 'Campo restringido';
                                    }
                                    return null;
                                })
                                ->hintIcon(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'status');
                                    if (!$fieldInfo['editable']) {
                                        return 'heroicon-o-lock-closed';
                                    }
                                    return null;
                                })
                                ->hintColor(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'status');
                                    if (!$fieldInfo['editable']) {
                                        return 'warning';
                                    }
                                    return null;
                                })
                                ->disabled(function () use ($user, $formFieldResolver) {
                                    return !$formFieldResolver->isFieldEditable($user, TravelExpense::class, 'status');
                                })
                            : Forms\Components\Placeholder::make('status_restriction')
                                ->label('Estado')
                                ->content(function () use ($user, $formFieldResolver) {
                                    $fieldInfo = $formFieldResolver->getFieldWithHelp($user, TravelExpense::class, 'status');
                                    return view('filament.components.field-restriction', [
                                        'reason' => $fieldInfo['restrictionReason'] ?? 'Este campo no está disponible para tu rol.',
                                    ]);
                                }),
                    ])
                    ->visible(function () use ($user, $formFieldResolver) {
                        if (!$user) return false;
                        // Show status section if status field is visible for user
                        return $formFieldResolver->isFieldVisible($user, TravelExpense::class, 'status');
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['operator', 'trip', 'attachments']))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('trip.display_name')
                    ->label('Viaje')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operador')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->visible(fn () => !Auth::user()?->isOperator()),
                
                Tables\Columns\TextColumn::make('expense_type_display')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Combustible' => 'warning',
                        'Peajes' => 'info',
                        'Alimentación' => 'success',
                        'Hospedaje' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('MXN')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('fuel_liters')
                    ->label('Litros')
                    ->suffix(' L')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable()
                    ->visible(fn ($record) => $record?->isFuelExpense()),
                
                Tables\Columns\TextColumn::make('status_display')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        'Aprobado' => 'success',
                        'Reembolsado' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('has_attachments')
                    ->label('Archivos')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => $record->attachments()->exists()),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expense_type')
                    ->label('Tipo de Gasto')
                    ->options(TravelExpense::EXPENSE_TYPES),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(TravelExpense::STATUSES),
                
                Tables\Filters\SelectFilter::make('operator_id')
                    ->label('Operador')
                    ->relationship('operator', 'name', fn ($query) => $query->operators())
                    ->searchable()
                    ->preload()
                    ->visible(fn () => !Auth::user()?->isOperator()),

                Tables\Filters\SelectFilter::make('trip_id')
                    ->label('Viaje')
                    ->relationship('trip', 'origin')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
                
                Tables\Filters\Filter::make('fuel_expenses')
                    ->label('Solo Combustible')
                    ->query(fn (Builder $query): Builder => $query->where('expense_type', 'fuel'))
                    ->toggle(),
                
                Tables\Filters\Filter::make('pending_reimbursement')
                    ->label('Pendientes de Reembolso')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', ['pending', 'approved']))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(TravelExpensePermissionService::class);
                        return $permissionService->canView($user, $record);
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(TravelExpensePermissionService::class);
                        return $permissionService->canEdit($user, $record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        $permissionService = app(TravelExpensePermissionService::class);
                        return $permissionService->canDelete($user, $record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function () {
                            $user = Auth::user();
                            if (!$user) return false;
                            // Only show bulk delete for admin/accounting users
                            return $user->hasAdminAccess() || $user->hasAccountingAccess();
                        }),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user && $user->isOperator()) {
                    // Operators can only see their own expenses
                    $query->where('operator_id', $user->id);
                }
                return $query;
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTravelExpenses::route('/'),
            'create' => Pages\CreateTravelExpense::route('/create'),
            'view' => Pages\ViewTravelExpense::route('/{record}'),
            'edit' => Pages\EditTravelExpense::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(TravelExpensePermissionService::class);
        return $permissionService->canViewAny($user);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(TravelExpensePermissionService::class);
        return $permissionService->canCreate($user);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(TravelExpensePermissionService::class);
        return $permissionService->canEdit($user, $record);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $permissionService = app(TravelExpensePermissionService::class);
        return $permissionService->canDelete($user, $record);
    }

    /**
     * Get MIME type from file extension.
     */
    private function getMimeTypeFromExtension(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }
}