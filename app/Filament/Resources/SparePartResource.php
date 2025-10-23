<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartResource\Pages;
use App\Models\SparePart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SparePartResource extends Resource
{
    protected static ?string $model = SparePart::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Refacciones';

    protected static ?string $modelLabel = 'Refacción';

    protected static ?string $pluralModelLabel = 'Refacciones';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Refacción')
                    ->schema([
                        Forms\Components\TextInput::make('part_number')
                            ->label('Número de Parte')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('brand')
                            ->label('Marca')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('stock_quantity')
                            ->label('Cantidad en Stock')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0),

                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Costo Unitario')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0),

                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación en Almacén')
                            ->maxLength(255)
                            ->helperText('Ej: Estante A-1, Pasillo 3, etc.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('part_number')
                    ->label('Número de Parte')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Costo Unitario')
                    ->money('MXN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->label('Marca')
                    ->options(fn (): array => SparePart::distinct()->pluck('brand', 'brand')->toArray()),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock Bajo')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', '<=', 10)),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Sin Stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', '<=', 0)),

                Tables\Filters\Filter::make('cost_range')
                    ->form([
                        Forms\Components\TextInput::make('min_cost')
                            ->label('Costo Mínimo')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('max_cost')
                            ->label('Costo Máximo')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_cost'],
                                fn (Builder $query, $cost): Builder => $query->where('unit_cost', '>=', $cost),
                            )
                            ->when(
                                $data['max_cost'],
                                fn (Builder $query, $cost): Builder => $query->where('unit_cost', '<=', $cost),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('part_number');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpareParts::route('/'),
            'create' => Pages\CreateSparePart::route('/create'),
            'edit' => Pages\EditSparePart::route('/{record}/edit'),
        ];
    }
}
