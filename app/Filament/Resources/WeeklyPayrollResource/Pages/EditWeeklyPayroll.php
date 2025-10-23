<?php

namespace App\Filament\Resources\WeeklyPayrollResource\Pages;

use App\Filament\Resources\WeeklyPayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeeklyPayroll extends EditRecord
{
    protected static string $resource = WeeklyPayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
