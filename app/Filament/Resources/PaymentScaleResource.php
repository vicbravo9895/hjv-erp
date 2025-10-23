<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentScaleResource\Pages;
use App\Filament\Resources\PaymentScaleResource\RelationManagers;
use App\Models\PaymentScale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentScaleResource extends BaseResource
{
    protected static ?string $model = PaymentScale::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    
    protected static ?string $navigationGroup = 'Finanzas';
    
    protected static ?int $navigationSort = 7;
    
    protected static ?string $modelLabel = 'Escala de Pagos';
    
    protected static ?string $pluralModelLabel = 'Escalas de Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Escala de Pagos')
                    ->schema([
                        Forms\Components\TextInput::make('trips_count')
                            ->label('Número de Viajes')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->unique(ignoreRecord: true)
                            ->helperText('Número de viajes completados en la semana'),
                            
                        Forms\Components\TextInput::make('payment_amount')
                            ->label('Monto de Pago')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Monto a pagar por esta cantidad de viajes'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trips_count')
                    ->label('Número de Viajes')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('payment_amount')
                    ->label('Monto de Pago')
                    ->money('MXN')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            ->defaultSort('trips_count');
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
            'index' => Pages\ListPaymentScales::route('/'),
            'create' => Pages\CreatePaymentScale::route('/create'),
            'edit' => Pages\EditPaymentScale::route('/{record}/edit'),
        ];
    }
}
