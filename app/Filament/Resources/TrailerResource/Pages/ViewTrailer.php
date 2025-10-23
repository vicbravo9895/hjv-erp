<?php

namespace App\Filament\Resources\TrailerResource\Pages;

use App\Filament\Resources\TrailerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTrailer extends ViewRecord
{
    protected static string $resource = TrailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}