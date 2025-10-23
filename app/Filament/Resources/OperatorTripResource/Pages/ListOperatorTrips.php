<?php

namespace App\Filament\Resources\OperatorTripResource\Pages;

use App\Filament\Resources\OperatorTripResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListOperatorTrips extends ListRecords
{
    protected static string $resource = OperatorTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for operators
        ];
    }

    public function getTabs(): array
    {
        $user = Auth::user();
        if (!$user || !$user->isOperator()) {
            return [];
        }

        return [
            'all' => Tab::make('Todos')
                ->badge(fn () => $this->getModel()::where('operator_id', $user->id)->count()),
            
            'active' => Tab::make('Activos')
                ->modifyQueryUsing(fn (Builder $query) => $query->active())
                ->badge(fn () => $this->getModel()::where('operator_id', $user->id)->active()->count())
                ->badgeColor('warning'),
            
            'completed' => Tab::make('Completados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => $this->getModel()::where('operator_id', $user->id)->where('status', 'completed')->count())
                ->badgeColor('success'),
            
            'with_expenses' => Tab::make('Con Gastos')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('travelExpenses'))
                ->badge(fn () => $this->getModel()::where('operator_id', $user->id)->has('travelExpenses')->count())
                ->badgeColor('info'),
        ];
    }
}