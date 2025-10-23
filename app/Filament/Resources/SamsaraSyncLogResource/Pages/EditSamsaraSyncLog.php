<?php

namespace App\Filament\Resources\SamsaraSyncLogResource\Pages;

use App\Filament\Resources\SamsaraSyncLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSamsaraSyncLog extends EditRecord
{
    protected static string $resource = SamsaraSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
