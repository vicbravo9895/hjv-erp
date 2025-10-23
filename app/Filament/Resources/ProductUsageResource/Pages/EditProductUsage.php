<?php

namespace App\Filament\Resources\ProductUsageResource\Pages;

use App\Filament\Resources\ProductUsageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductUsage extends EditRecord
{
    protected static string $resource = ProductUsageResource::class;

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