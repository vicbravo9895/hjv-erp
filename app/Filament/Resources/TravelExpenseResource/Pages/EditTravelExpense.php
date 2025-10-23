<?php

namespace App\Filament\Resources\TravelExpenseResource\Pages;

use App\Filament\Resources\TravelExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTravelExpense extends EditRecord
{
    protected static string $resource = TravelExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn ($record) => Auth::user()?->isOperator() ? $record->status === 'pending' : true),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Gasto de viaje actualizado exitosamente';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Operators cannot change status
        $user = Auth::user();
        if ($user && $user->isOperator()) {
            unset($data['status']);
        }

        return $data;
    }
}