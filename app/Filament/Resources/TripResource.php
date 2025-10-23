<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\User;
use App\Services\VehicleAssignmentService;
use App\Services\Validation\TripValidationService;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class TripResource extends BaseResource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Viajes';

    protected static ?string $modelLabel = 'Viaje';

    protected static ?string $pluralModelLabel = 'Viajes';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Viaje')
                    ->schema([
                        Forms\Components\TextInput::make('origin')
                            ->label('Origen')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ciudad o ubicación de origen'),

                        Forms\Components\TextInput::make('destination')
                            ->label('Destino')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ciudad o ubicación de destino'),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                // Auto-set end_date if not set and start_date is provided
                                if ($state && !$get('end_date')) {
                                    $set('end_date', $state);
                                }
                            }),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de Fin')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('start_date')
                            ->reactive(),

                        Forms\Components\Select::make('status')
                            ->label('Estatus')
                            ->options([
                                'planned' => 'Planeado',
                                'in_progress' => 'En Progreso',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->default('planned')
                            ->required()
                            ->reactive(),
                    ])->columns(2),

                Forms\Components\Section::make('Asignación de Recursos')
                    ->schema([
                        Forms\Components\Select::make('truck_id')
                            ->label('Tractocamión')
                            ->relationship('truck', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => $record->display_name)
                            ->searchable(['name', 'unit_number', 'plate'])
                            ->preload()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set, ?Model $record) {
                                static::validateTripAssignment($get, $set, $record);
                            }),

                        Forms\Components\Select::make('trailer_id')
                            ->label('Trailer')
                            ->relationship('trailer', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Trailer $record): string => $record->display_name)
                            ->searchable(['name', 'asset_number', 'plate'])
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set, ?Model $record) {
                                static::validateTripAssignment($get, $set, $record);
                            }),

                        Forms\Components\Select::make('operator_id')
                            ->label('Operador')
                            ->relationship('operator', 'name')
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->getOperatorDisplayName())
                            ->searchable(['name', 'license_number'])
                            ->preload()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set, ?Model $record) {
                                static::validateTripAssignment($get, $set, $record);
                            }),

                        Forms\Components\Placeholder::make('validation_message')
                            ->label('')
                            ->content(fn (callable $get): string => static::getValidationMessage($get))
                            ->visible(fn (callable $get): bool => static::hasValidationMessage($get))
                            ->extraAttributes(['class' => 'text-sm']),
                    ])->columns(3),

                Forms\Components\Section::make('Finalización del Viaje')
                    ->schema([
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Fecha y Hora de Finalización')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->visible(fn (callable $get) => $get('status') === 'completed'),
                    ])
                    ->visible(fn (callable $get) => $get('status') === 'completed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Viaje')
                    ->searchable(['origin', 'destination'])
                    ->sortable(['origin', 'destination']),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estatus')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Planeado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'planned',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('truck.display_name')
                    ->label('Tractocamión')
                    ->searchable(['vehicles.name', 'vehicles.unit_number'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('trailer.display_name')
                    ->label('Trailer')
                    ->searchable(['trailers.name', 'trailers.asset_number'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operador')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Costo Total')
                    ->money('MXN')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Finalizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estatus')
                    ->options([
                        'planned' => 'Planeado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('truck_id')
                    ->label('Tractocamión')
                    ->relationship('truck', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => $record->display_name),

                Tables\Filters\SelectFilter::make('operator_id')
                    ->label('Operador')
                    ->relationship('operator', 'name'),

                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('Finalizar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Trip $record): bool => $record->status === 'in_progress')
                    ->requiresConfirmation()
                    ->modalHeading('Finalizar Viaje')
                    ->modalDescription('¿Está seguro de que desea finalizar este viaje? Esto liberará los recursos asignados.')
                    ->action(function (Trip $record) {
                        $assignmentService = app(VehicleAssignmentService::class);
                        
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);

                        $result = $assignmentService->releaseFromTrip($record);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Viaje finalizado correctamente')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error al liberar recursos')
                                ->body(implode(', ', $result['errors']))
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Trip $record): bool => in_array($record->status, ['planned', 'cancelled'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => true), // Add custom logic if needed
                ]),
            ])
            ->defaultSort('start_date', 'desc');
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
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    /**
     * Validate trip assignment using TripValidationService.
     */
    protected static function validateTripAssignment(callable $get, callable $set, ?Model $record): void
    {
        $truckId = $get('truck_id');
        $operatorId = $get('operator_id');
        $startDate = $get('start_date');
        $endDate = $get('end_date');

        // Clear previous validation message
        $set('validation_message', null);

        // Skip validation if required fields are missing
        if (!$truckId || !$operatorId || !$startDate || !$endDate) {
            return;
        }

        try {
            $validationService = app(TripValidationService::class);
            
            // Convert dates to Carbon instances
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            // Exclude current trip from validation when editing
            $excludeTripId = $record?->id;

            // Validate vehicle availability
            $vehicleValidation = $validationService->validateVehicleAvailability(
                $truckId,
                $start,
                $end,
                $excludeTripId
            );

            // Validate operator availability
            $operatorValidation = $validationService->validateOperatorAvailability(
                $operatorId,
                $start,
                $end,
                $excludeTripId
            );

            // Store validation results for display
            $validationResults = [
                'vehicle' => $vehicleValidation,
                'operator' => $operatorValidation,
            ];

            // Store in a way that can be retrieved by the placeholder
            $set('validation_message', json_encode($validationResults));

            // Show notification if there are errors
            if (!$vehicleValidation->isValid || !$operatorValidation->isValid) {
                $message = static::formatValidationNotification($vehicleValidation, $operatorValidation);
                
                Notification::make()
                    ->title('⚠️ Conflicto de Programación')
                    ->body($message)
                    ->warning()
                    ->persistent()
                    ->send();
            } elseif ($vehicleValidation->hasSuggestions() || $operatorValidation->hasSuggestions()) {
                // Show success notification with suggestions
                Notification::make()
                    ->title('✅ Asignación Válida')
                    ->body('Los recursos están disponibles para las fechas seleccionadas.')
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            // Log error but don't break the form
            \Log::error('Trip validation error: ' . $e->getMessage());
        }
    }

    /**
     * Get validation message for display in form.
     */
    protected static function getValidationMessage(callable $get): string
    {
        $validationData = $get('validation_message');
        
        if (!$validationData) {
            return '';
        }

        try {
            $results = json_decode($validationData, true);
            
            if (!$results) {
                return '';
            }

            $message = '';

            // Format vehicle validation
            if (isset($results['vehicle'])) {
                $vehicleResult = (object) $results['vehicle'];
                if (!empty($vehicleResult->errors)) {
                    $message .= "🚛 **Vehículo:**\n";
                    foreach ($vehicleResult->errors as $error) {
                        $message .= "❌ {$error}\n";
                    }
                    if (!empty($vehicleResult->warnings)) {
                        foreach ($vehicleResult->warnings as $warning) {
                            $message .= "⚠️ {$warning}\n";
                        }
                    }
                    if (!empty($vehicleResult->suggestions)) {
                        foreach ($vehicleResult->suggestions as $suggestion) {
                            $message .= "💡 {$suggestion}\n";
                        }
                    }
                    $message .= "\n";
                }
            }

            // Format operator validation
            if (isset($results['operator'])) {
                $operatorResult = (object) $results['operator'];
                if (!empty($operatorResult->errors)) {
                    $message .= "👤 **Operador:**\n";
                    foreach ($operatorResult->errors as $error) {
                        $message .= "❌ {$error}\n";
                    }
                    if (!empty($operatorResult->warnings)) {
                        foreach ($operatorResult->warnings as $warning) {
                            $message .= "⚠️ {$warning}\n";
                        }
                    }
                    if (!empty($operatorResult->suggestions)) {
                        foreach ($operatorResult->suggestions as $suggestion) {
                            $message .= "💡 {$suggestion}\n";
                        }
                    }
                }
            }

            return $message;

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if there's a validation message to display.
     */
    protected static function hasValidationMessage(callable $get): bool
    {
        $message = static::getValidationMessage($get);
        return !empty($message);
    }

    /**
     * Format validation results for notification.
     */
    protected static function formatValidationNotification($vehicleValidation, $operatorValidation): string
    {
        $message = '';

        if (!$vehicleValidation->isValid) {
            $message .= "🚛 Vehículo: " . implode(', ', $vehicleValidation->errors);
        }

        if (!$operatorValidation->isValid) {
            if ($message) {
                $message .= "\n\n";
            }
            $message .= "👤 Operador: " . implode(', ', $operatorValidation->errors);
        }

        // Add suggestions
        $suggestions = array_merge(
            $vehicleValidation->suggestions ?? [],
            $operatorValidation->suggestions ?? []
        );

        if (!empty($suggestions)) {
            $message .= "\n\n💡 " . implode("\n💡 ", $suggestions);
        }

        return $message;
    }

    /**
     * Check resource-specific access based on user role
     */
    protected static function checkResourceAccess($user, string $action, ?Model $record = null): bool
    {
        // Trip management access: Super Admin, Administrator, Supervisor
        return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }
}
