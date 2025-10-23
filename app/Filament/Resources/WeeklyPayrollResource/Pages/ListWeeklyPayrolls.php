<?php

namespace App\Filament\Resources\WeeklyPayrollResource\Pages;

use App\Filament\Resources\WeeklyPayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeeklyPayrolls extends ListRecords
{
    protected static string $resource = WeeklyPayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
