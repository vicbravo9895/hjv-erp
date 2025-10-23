<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehicleResource extends BaseResource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Tractocamiones';

    protected static ?string $modelLabel = 'Tractocamión';

    protected static ?string $pluralModelLabel = 'Tractocamiones';

    protected static ?string $navigationGroup = 'Gestión de Flota';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('unit_number')
                            ->label('Número Económico')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('plate')
                            ->label('Placas')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        Forms\Components\Select::make('status')
                            ->label('Estatus')
                            ->required()
                            ->options([
                                'available' => 'Disponible',
                                'in_trip' => 'En Viaje',
                                'maintenance' => 'En Mantenimiento',
                                'out_of_service' => 'Fuera de Servicio',
                            ])
                            ->default('available'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Especificaciones del Vehículo')
                    ->schema([
                        Forms\Components\TextInput::make('make')
                            ->label('Marca')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('model')
                            ->label('Modelo')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('year')
                            ->label('Año')
                            ->required()
                            ->numeric()
                            ->minValue(1990)
                            ->maxValue(date('Y') + 1),

                        Forms\Components\TextInput::make('vin')
                            ->label('VIN')
                            ->unique(ignoreRecord: true)
                            ->maxLength(17)
                            ->minLength(17),

                        Forms\Components\TextInput::make('serial_number')
                            ->label('Número de Serie')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información de Samsara')
                    ->schema([
                        Forms\Components\TextInput::make('external_id')
                            ->label('ID Externo (Samsara)')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('current_driver_name')
                            ->label('Conductor Actual')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('formatted_location')
                            ->label('Ubicación Actual')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('last_odometer_km')
                            ->label('Odómetro (km)')
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('km'),

                        Forms\Components\TextInput::make('last_fuel_percent')
                            ->label('Combustible')
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('%'),

                        Forms\Components\TextInput::make('last_engine_state')
                            ->label('Estado del Motor')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unit_number')
                    ->label('Número Económico')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plate')
                    ->label('Placas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('make')
                    ->label('Marca')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estatus')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'in_trip',
                        'danger' => 'maintenance',
                        'secondary' => 'out_of_service',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'in_trip' => 'En Viaje',
                        'maintenance' => 'En Mantenimiento',
                        'out_of_service' => 'Fuera de Servicio',
                        null => 'Sin definir',
                        default => $state ?? 'Sin definir',
                    }),

                Tables\Columns\TextColumn::make('current_driver_name')
                    ->label('Conductor Actual')
                    ->toggleable()
                    ->placeholder('Sin asignar'),

                Tables\Columns\TextColumn::make('last_odometer_km')
                    ->label('Odómetro')
                    ->suffix(' km')
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Última Sincronización')
                    ->dateTime()
                    ->toggleable()
                    ->placeholder('No sincronizado'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estatus')
                    ->options([
                        'available' => 'Disponible',
                        'in_trip' => 'En Viaje',
                        'maintenance' => 'En Mantenimiento',
                        'out_of_service' => 'Fuera de Servicio',
                    ]),

                Tables\Filters\SelectFilter::make('make')
                    ->label('Marca')
                    ->options(fn (): array => Vehicle::distinct('make')
                        ->whereNotNull('make')
                        ->pluck('make', 'make')
                        ->filter()
                        ->toArray()),

                Tables\Filters\Filter::make('synced')
                    ->label('Sincronizados con Samsara')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('synced_at')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('unit_number');
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    /**
     * Check resource-specific access based on user role
     */
    protected static function checkResourceAccess($user, string $action, ?Model $record = null): bool
    {
        // Fleet management access: Super Admin, Administrator, Supervisor
        return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }
}