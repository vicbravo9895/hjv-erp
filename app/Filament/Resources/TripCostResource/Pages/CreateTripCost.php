<?php

namespace App\Filament\Resources\TripCostResource\Pages;

use App\Filament\Resources\TripCostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTripCost extends CreateRecord
{
    protected static string $resource = TripCostResource::class;

    public function getTitle(): string
    {
        return 'Crear Costo de Viaje';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
