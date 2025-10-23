<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrailerResource\Pages;
use App\Models\Trailer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrailerResource extends BaseResource
{
    protected static ?string $model = Trailer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Trailers';

    protected static ?string $modelLabel = 'Trailer';

    protected static ?string $pluralModelLabel = 'Trailers';

    protected static ?string $navigationGroup = 'Gestión de Flota';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('asset_number')
                            ->label('Número de Plataforma')
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

                Forms\Components\Section::make('Especificaciones del Trailer')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Trailer')
                            ->required()
                            ->options([
                                'dry_van' => 'Caja Seca',
                                'refrigerated' => 'Refrigerado',
                                'flatbed' => 'Plataforma',
                                'lowboy' => 'Cama Baja',
                                'tanker' => 'Tanque',
                                'container' => 'Contenedor',
                                'other' => 'Otro',
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Información de Samsara')
                    ->schema([
                        Forms\Components\TextInput::make('external_id')
                            ->label('ID Externo (Samsara)')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('formatted_location')
                            ->label('Ubicación Actual')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('last_speed_mph')
                            ->label('Velocidad')
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('mph'),

                        Forms\Components\TextInput::make('last_heading_degrees')
                            ->label('Dirección')
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('°'),
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
                Tables\Columns\TextColumn::make('asset_number')
                    ->label('Número de Plataforma')
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

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'dry_van' => 'Caja Seca',
                        'refrigerated' => 'Refrigerado',
                        'flatbed' => 'Plataforma',
                        'lowboy' => 'Cama Baja',
                        'tanker' => 'Tanque',
                        'container' => 'Contenedor',
                        'other' => 'Otro',
                        default => $state,
                    })
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estatus')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'in_trip',
                        'danger' => 'maintenance',
                        'secondary' => 'out_of_service',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'in_trip' => 'En Viaje',
                        'maintenance' => 'En Mantenimiento',
                        'out_of_service' => 'Fuera de Servicio',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('formatted_location')
                    ->label('Ubicación Actual')
                    ->toggleable()
                    ->placeholder('Sin ubicación'),

                Tables\Columns\TextColumn::make('last_speed_mph')
                    ->label('Velocidad')
                    ->suffix(' mph')
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

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'dry_van' => 'Caja Seca',
                        'refrigerated' => 'Refrigerado',
                        'flatbed' => 'Plataforma',
                        'lowboy' => 'Cama Baja',
                        'tanker' => 'Tanque',
                        'container' => 'Contenedor',
                        'other' => 'Otro',
                    ]),

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
            ->defaultSort('asset_number');
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
            'index' => Pages\ListTrailers::route('/'),
            'create' => Pages\CreateTrailer::route('/create'),
            'view' => Pages\ViewTrailer::route('/{record}'),
            'edit' => Pages\EditTrailer::route('/{record}/edit'),
        ];
    }
}