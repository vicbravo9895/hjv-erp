<?php

namespace App\Filament\Resources\MaintenanceRecordResource\Pages;

use App\Filament\Resources\MaintenanceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceRecords extends ListRecords
{
    protected static string $resource = MaintenanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
