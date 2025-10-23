<?php

namespace App\Filament\Resources\PaymentScaleResource\Pages;

use App\Filament\Resources\PaymentScaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentScale extends EditRecord
{
    protected static string $resource = PaymentScaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
