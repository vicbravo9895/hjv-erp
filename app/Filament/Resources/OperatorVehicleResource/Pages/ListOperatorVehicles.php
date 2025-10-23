<?php

namespace App\Filament\Resources\OperatorVehicleResource\Pages;

use App\Filament\Resources\OperatorVehicleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListOperatorVehicles extends ListRecords
{
    protected static string $resource = OperatorVehicleResource::class;

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
                ->badge(fn () => $this->getModel()::count()),
            
            'assigned' => Tab::make('Asignados a Mí')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    return $query->whereHas('trips', function (Builder $tripQuery) use ($user) {
                        $tripQuery->where('operator_id', $user->id)->active();
                    });
                })
                ->badge(function () use ($user) {
                    return $this->getModel()::whereHas('trips', function (Builder $query) use ($user) {
                        $query->where('operator_id', $user->id)->active();
                    })->count();
                })
                ->badgeColor('success'),
            
            'available' => Tab::make('Disponibles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'available'))
                ->badge(fn () => $this->getModel()::where('status', 'available')->count())
                ->badgeColor('info'),
            
            'maintenance' => Tab::make('En Mantenimiento')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'maintenance'))
                ->badge(fn () => $this->getModel()::where('status', 'maintenance')->count())
                ->badgeColor('warning'),
        ];
    }
}