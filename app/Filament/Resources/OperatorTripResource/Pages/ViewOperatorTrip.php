<?php

namespace App\Filament\Resources\OperatorTripResource\Pages;

use App\Filament\Resources\OperatorTripResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOperatorTrip extends ViewRecord
{
    protected static string $resource = OperatorTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_expense')
                ->label('Agregar Gasto')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn () => route('filament.operator.resources.travel-expenses.create', ['trip_id' => $this->record->id]))
                ->visible(fn () => $this->record->isActive()),
            
            Actions\Action::make('view_expenses')
                ->label('Ver Gastos')
                ->icon('heroicon-o-receipt-percent')
                ->color('info')
                ->url(fn () => route('filament.operator.resources.travel-expenses.index', ['tableFilters[trip_id][values][0]' => $this->record->id]))
                ->visible(fn () => $this->record->travelExpenses()->exists()),
        ];
    }
}