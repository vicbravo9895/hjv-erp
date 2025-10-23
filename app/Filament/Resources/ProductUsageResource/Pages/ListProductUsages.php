<?php

namespace App\Filament\Resources\ProductUsageResource\Pages;

use App\Filament\Resources\ProductUsageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductUsages extends ListRecords
{
    protected static string $resource = ProductUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}