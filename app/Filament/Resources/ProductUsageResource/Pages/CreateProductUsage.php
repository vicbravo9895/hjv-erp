<?php

namespace App\Filament\Resources\ProductUsageResource\Pages;

use App\Filament\Resources\ProductUsageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProductUsage extends CreateRecord
{
    protected static string $resource = ProductUsageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['used_by'] = Auth::id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}