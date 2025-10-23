<?php

namespace App\Filament\Resources\TravelExpenseResource\Pages;

use App\Filament\Resources\TravelExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;

class ViewTravelExpense extends ViewRecord
{
    protected static string $resource = TravelExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => Auth::user()?->isOperator() ? $record->status === 'pending' : true),
            Actions\DeleteAction::make()
                ->visible(fn ($record) => Auth::user()?->isOperator() ? $record->status === 'pending' : true),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Gasto')
                    ->schema([
                        Infolists\Components\TextEntry::make('trip.display_name')
                            ->label('Viaje'),
                        Infolists\Components\TextEntry::make('operator.name')
                            ->label('Operador'),
                        Infolists\Components\TextEntry::make('expense_type_display')
                            ->label('Tipo de Gasto')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Combustible' => 'warning',
                                'Peajes' => 'info',
                                'Alimentación' => 'success',
                                'Hospedaje' => 'primary',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Monto')
                            ->money('MXN'),
                        Infolists\Components\TextEntry::make('date')
                            ->label('Fecha')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('location')
                            ->label('Ubicación')
                            ->placeholder('No especificada'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Información de Combustible')
                    ->schema([
                        Infolists\Components\TextEntry::make('fuel_liters')
                            ->label('Litros')
                            ->suffix(' L')
                            ->numeric(decimalPlaces: 2),
                        Infolists\Components\TextEntry::make('fuel_price_per_liter')
                            ->label('Precio por Litro')
                            ->money('MXN')
                            ->suffix('/L'),
                        Infolists\Components\TextEntry::make('odometer_reading')
                            ->label('Odómetro')
                            ->suffix(' km')
                            ->numeric(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->isFuelExpense()),

                Infolists\Components\Section::make('Estado y Seguimiento')
                    ->schema([
                        Infolists\Components\TextEntry::make('status_display')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Pendiente' => 'warning',
                                'Aprobado' => 'success',
                                'Reembolsado' => 'primary',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Registrado')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(3),

                Infolists\Components\Section::make('Archivos Adjuntos')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('attachments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('file_name')
                                    ->label('Archivo'),
                                Infolists\Components\TextEntry::make('file_size')
                                    ->label('Tamaño')
                                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Subido')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->attachments()->exists()),
            ]);
    }
}