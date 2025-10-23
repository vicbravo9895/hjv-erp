<?php

namespace App\Filament\Resources\PaymentScaleResource\Pages;

use App\Filament\Resources\PaymentScaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentScales extends ListRecords
{
    protected static string $resource = PaymentScaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
