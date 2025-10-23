<?php

namespace App\Filament\Resources\TrailerResource\Pages;

use App\Filament\Resources\TrailerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrailer extends CreateRecord
{
    protected static string $resource = TrailerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}