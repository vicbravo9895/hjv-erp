<?php

namespace App\Filament\Resources\TrailerResource\Pages;

use App\Filament\Resources\TrailerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrailer extends EditRecord
{
    protected static string $resource = TrailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}