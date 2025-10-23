<?php

namespace App\Filament\Resources\ProductUsageResource\Pages;

use App\Filament\Resources\ProductUsageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductUsage extends ViewRecord
{
    protected static string $resource = ProductUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}