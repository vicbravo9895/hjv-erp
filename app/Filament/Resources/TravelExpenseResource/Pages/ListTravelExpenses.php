<?php

namespace App\Filament\Resources\TravelExpenseResource\Pages;

use App\Filament\Resources\TravelExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTravelExpenses extends ListRecords
{
    protected static string $resource = TravelExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Gasto'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(fn () => $this->getModel()::count()),
            
            'pending' => Tab::make('Pendientes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'approved' => Tab::make('Aprobados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(fn () => $this->getModel()::where('status', 'approved')->count())
                ->badgeColor('success'),
            
            'reimbursed' => Tab::make('Reembolsados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'reimbursed'))
                ->badge(fn () => $this->getModel()::where('status', 'reimbursed')->count())
                ->badgeColor('primary'),
            
            'fuel' => Tab::make('Combustible')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('expense_type', 'fuel'))
                ->badge(fn () => $this->getModel()::where('expense_type', 'fuel')->count())
                ->badgeColor('info'),
        ];
    }
}