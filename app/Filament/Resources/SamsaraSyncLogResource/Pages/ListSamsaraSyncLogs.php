<?php

namespace App\Filament\Resources\SamsaraSyncLogResource\Pages;

use App\Filament\Resources\SamsaraSyncLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSamsaraSyncLogs extends ListRecords
{
    protected static string $resource = SamsaraSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
