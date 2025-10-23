<?php

namespace App\Filament\Resources\ProductRequestResource\Pages;

use App\Filament\Resources\ProductRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateProductRequest extends CreateRecord
{
    protected static string $resource = ProductRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = Auth::id();
        $data['requested_at'] = now();
        $data['status'] = 'pending';
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Send notification to supervisors about new request
        $supervisors = \App\Models\User::whereIn('role', ['super_admin', 'administrador', 'supervisor'])->get();
        
        foreach ($supervisors as $supervisor) {
            Notification::make()
                ->title('Nueva solicitud de producto')
                ->body("Se ha creado una nueva solicitud de producto por {$this->record->requestedBy->name}")
                ->info()
                ->sendToDatabase($supervisor);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}