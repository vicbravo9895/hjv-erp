<?php

namespace App\Filament\Resources\TravelExpenseResource\Pages;

use App\Filament\Resources\TravelExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTravelExpense extends CreateRecord
{
    protected static string $resource = TravelExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set the operator_id to the current user if they are an operator
        $user = Auth::user();
        if ($user && $user->isOperator()) {
            $data['operator_id'] = $user->id;
        }

        // Set default status
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Gasto de viaje registrado exitosamente';
    }
}