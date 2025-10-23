<?php

namespace App\Filament\Resources\TripCostResource\Pages;

use App\Filament\Resources\TripCostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTripCosts extends ListRecords
{
    protected static string $resource = TripCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Costo'),
        ];
    }

    public function getTitle(): string
    {
        return 'Costos de Viaje';
    }
}
