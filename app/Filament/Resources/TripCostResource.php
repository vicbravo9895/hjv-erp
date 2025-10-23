<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripCostResource\Pages;
use App\Models\Trip;
use App\Models\TripCost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TripCostResource extends BaseResource
{
    protected static ?string $model = TripCost::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Costos de Viaje';

    protected static ?string $modelLabel = 'Costo de Viaje';

    protected static ?string $pluralModelLabel = 'Costos de Viaje';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Costo')
                    ->schema([
                        Forms\Components\Select::make('trip_id')
                            ->label('Viaje')
                            ->relationship('trip', 'id')
                            ->getOptionLabelFromRecordUsing(fn (Trip $record): string => $record->display_name)
                            ->searchable(['origin', 'destination'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('cost_type')
                            ->label('Tipo de Costo')
                            ->options(TripCost::getCostTypes())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Clear quantity and unit_price for non-diesel costs
                                if ($state !== TripCost::TYPE_DIESEL) {
                                    $set('quantity', null);
                                    $set('unit_price', null);
                                }
                            }),

                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación')
                            ->maxLength(255)
                            ->placeholder('Ej: Caseta de peaje, Gasolinera, etc.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Detalles del Costo')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->step(0.001)
                            ->suffix(fn (callable $get) => $get('cost_type') === TripCost::TYPE_DIESEL ? 'litros' : 'unidades')
                            ->visible(fn (callable $get) => in_array($get('cost_type'), [TripCost::TYPE_DIESEL]))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $unitPrice = $get('unit_price');
                                if ($state && $unitPrice) {
                                    $set('amount', round($state * $unitPrice, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Precio Unitario')
                            ->numeric()
                            ->step(0.001)
                            ->prefix('$')
                            ->suffix(fn (callable $get) => $get('cost_type') === TripCost::TYPE_DIESEL ? 'por litro' : 'por unidad')
                            ->visible(fn (callable $get) => in_array($get('cost_type'), [TripCost::TYPE_DIESEL]))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $quantity = $get('quantity');
                                if ($state && $quantity) {
                                    $set('amount', round($state * $quantity, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label('Monto Total')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->maxLength(65535)
                            ->placeholder('Descripción detallada del gasto...')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('receipt_url')
                            ->label('Comprobante')
                            ->image()
                            ->imageEditor()
                            ->directory('trip-receipts')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trip.display_name')
                    ->label('Viaje')
                    ->searchable(['trips.origin', 'trips.destination'])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('cost_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => TripCost::getCostTypes()[$state] ?? $state)
                    ->colors([
                        'success' => TripCost::TYPE_DIESEL,
                        'warning' => TripCost::TYPE_TOLLS,
                        'info' => TripCost::TYPE_MANEUVERS,
                        'secondary' => TripCost::TYPE_OTHER,
                    ]),

                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric(decimalPlaces: 3)
                    ->suffix(fn (TripCost $record): string => $record->cost_type === TripCost::TYPE_DIESEL ? ' L' : '')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Precio Unit.')
                    ->money('MXN')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto Total')
                    ->money('MXN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cost_type')
                    ->label('Tipo de Costo')
                    ->options(TripCost::getCostTypes()),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTripCosts::route('/'),
            'create' => Pages\CreateTripCost::route('/create'),
            'edit' => Pages\EditTripCost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
