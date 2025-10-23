<?php

namespace App\Filament\Resources\TripCostResource\Pages;

use App\Filament\Resources\TripCostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTripCost extends EditRecord
{
    protected static string $resource = TripCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar'),
        ];
    }

    public function getTitle(): string
    {
        return 'Editar Costo de Viaje';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
