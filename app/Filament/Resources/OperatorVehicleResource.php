<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperatorVehicleResource\Pages;
use App\Models\Vehicle;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class OperatorVehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    
    protected static ?string $navigationGroup = 'Mi Vehículo';
    
    protected static ?string $modelLabel = 'Vehículo';
    
    protected static ?string $pluralModelLabel = 'Mi Vehículo';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return true; // Force navigation registration for debugging
    }

    public static function form(Form $form): Form
    {
        // Read-only form for operators
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Vehículo')
                    ->schema([
                        Forms\Components\TextInput::make('unit_number')
                            ->label('Número Económico')
                            ->disabled(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->disabled(),
                        Forms\Components\TextInput::make('plate')
                            ->label('Placas')
                            ->disabled(),
                        Forms\Components\TextInput::make('brand')
                            ->label('Marca')
                            ->disabled(),
                        Forms\Components\TextInput::make('model')
                            ->label('Modelo')
                            ->disabled(),
                        Forms\Components\TextInput::make('year')
                            ->label('Año')
                            ->disabled(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Estado del Vehículo')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'available' => 'Disponible',
                                'in_use' => 'En Uso',
                                'maintenance' => 'En Mantenimiento',
                                'out_of_service' => 'Fuera de Servicio',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('current_mileage')
                            ->label('Kilometraje Actual')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Vehículo')
                    ->searchable(['name', 'unit_number', 'plate'])
                    ->sortable(['name', 'unit_number']),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'in_use' => 'En Uso',
                        'maintenance' => 'En Mantenimiento',
                        'out_of_service' => 'Fuera de Servicio',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'available',
                        'warning' => 'in_use',
                        'danger' => 'maintenance',
                        'secondary' => 'out_of_service',
                    ]),
                
                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('current_mileage')
                    ->label('Kilometraje')
                    ->suffix(' km')
                    ->numeric()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('active_trips_count')
                    ->label('Viajes Activos')
                    ->counts([
                        'trips' => fn (Builder $query) => $query->active()
                    ])
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('last_maintenance')
                    ->label('Último Mantenimiento')
                    ->date('d/m/Y')
                    ->getStateUsing(fn ($record) => $record->maintenanceRecords()->latest('date')->first()?->date)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'in_use' => 'En Uso',
                        'maintenance' => 'En Mantenimiento',
                        'out_of_service' => 'Fuera de Servicio',
                    ]),
                
                Tables\Filters\Filter::make('assigned_to_me')
                    ->label('Asignado a Mí')
                    ->query(function (Builder $query) {
                        $user = Auth::user();
                        if ($user && $user->isOperator()) {
                            return $query->whereHas('trips', function (Builder $tripQuery) use ($user) {
                                $tripQuery->where('operator_id', $user->id)->active();
                            });
                        }
                        return $query;
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for operators
            ])
            ->defaultSort('unit_number')
            ->modifyQueryUsing(function (Builder $query) {
                // Temporarily show all vehicles for debugging
                return $query->limit(20);
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Vehículo')
                    ->schema([
                        Infolists\Components\TextEntry::make('unit_number')
                            ->label('Número Económico'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nombre'),
                        Infolists\Components\TextEntry::make('plate')
                            ->label('Placas'),
                        Infolists\Components\TextEntry::make('brand')
                            ->label('Marca'),
                        Infolists\Components\TextEntry::make('model')
                            ->label('Modelo'),
                        Infolists\Components\TextEntry::make('year')
                            ->label('Año'),
                    ])->columns(2),

                Infolists\Components\Section::make('Estado y Operación')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'available' => 'Disponible',
                                'in_use' => 'En Uso',
                                'maintenance' => 'En Mantenimiento',
                                'out_of_service' => 'Fuera de Servicio',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'available' => 'success',
                                'in_use' => 'warning',
                                'maintenance' => 'danger',
                                'out_of_service' => 'secondary',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('current_mileage')
                            ->label('Kilometraje Actual')
                            ->suffix(' km')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('fuel_capacity')
                            ->label('Capacidad de Combustible')
                            ->suffix(' L')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('license_expiry')
                            ->label('Vencimiento de Licencia')
                            ->date('d/m/Y'),
                    ])->columns(2),

                Infolists\Components\Section::make('Viajes Asignados')
                    ->schema([
                        Infolists\Components\TextEntry::make('active_trips_count')
                            ->label('Viajes Activos')
                            ->getStateUsing(fn ($record) => $record->trips()->active()->count()),
                        Infolists\Components\TextEntry::make('total_trips_count')
                            ->label('Total de Viajes')
                            ->getStateUsing(fn ($record) => $record->trips()->count()),
                        Infolists\Components\TextEntry::make('current_trip')
                            ->label('Viaje Actual')
                            ->getStateUsing(function ($record) {
                                $user = Auth::user();
                                if ($user && $user->isOperator()) {
                                    $currentTrip = $record->trips()
                                        ->where('operator_id', $user->id)
                                        ->active()
                                        ->first();
                                    return $currentTrip?->display_name ?? 'Sin viaje activo';
                                }
                                return 'N/A';
                            }),
                    ])->columns(3),

                Infolists\Components\Section::make('Mantenimiento')
                    ->schema([
                        Infolists\Components\TextEntry::make('last_maintenance')
                            ->label('Último Mantenimiento')
                            ->date('d/m/Y')
                            ->getStateUsing(fn ($record) => $record->maintenanceRecords()->latest('date')->first()?->date),
                        Infolists\Components\TextEntry::make('maintenance_count')
                            ->label('Total Mantenimientos')
                            ->getStateUsing(fn ($record) => $record->maintenanceRecords()->count()),
                        Infolists\Components\TextEntry::make('next_maintenance')
                            ->label('Próximo Mantenimiento')
                            ->getStateUsing(function ($record) {
                                // Calculate based on mileage or date
                                $lastMaintenance = $record->maintenanceRecords()->latest('date')->first();
                                if ($lastMaintenance && $record->current_mileage) {
                                    $mileageSinceMaintenance = $record->current_mileage - ($lastMaintenance->mileage ?? 0);
                                    if ($mileageSinceMaintenance > 10000) {
                                        return 'Mantenimiento requerido';
                                    }
                                    return 'En ' . (10000 - $mileageSinceMaintenance) . ' km';
                                }
                                return 'No determinado';
                            }),
                    ])->columns(3),
            ]);
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
            'index' => Pages\ListOperatorVehicles::route('/'),
            'view' => Pages\ViewOperatorVehicle::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Operators cannot create vehicles
    }

    public static function canEdit($record): bool
    {
        return false; // Operators cannot edit vehicles
    }

    public static function canDelete($record): bool
    {
        return false; // Operators cannot delete vehicles
    }

    public static function canViewAny(): bool
    {
        return true; // Temporarily allow all users for debugging
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if ($user && $user->isOperator()) {
            // Show count of vehicles assigned to operator's active trips
            return Vehicle::whereHas('trips', function (Builder $query) use ($user) {
                $query->where('operator_id', $user->id)->active();
            })->count() ?: null;
        }
        return null;
    }
}