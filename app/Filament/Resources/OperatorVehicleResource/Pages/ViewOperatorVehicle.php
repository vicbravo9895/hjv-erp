<?php

namespace App\Filament\Resources\OperatorVehicleResource\Pages;

use App\Filament\Resources\OperatorVehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewOperatorVehicle extends ViewRecord
{
    protected static string $resource = OperatorVehicleResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        
        return [
            Actions\Action::make('view_trips')
                ->label('Ver Mis Viajes')
                ->icon('heroicon-o-map')
                ->color('info')
                ->url(fn () => route('filament.operator.resources.operator-trips.index', [
                    'tableFilters[truck_id][value]' => $this->record->id
                ]))
                ->visible(fn () => $user && $user->isOperator()),
            
            Actions\Action::make('add_expense')
                ->label('Agregar Gasto')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn () => route('filament.operator.resources.travel-expenses.create'))
                ->visible(function () use ($user) {
                    if (!$user || !$user->isOperator()) return false;
                    
                    // Check if operator has active trips with this vehicle
                    return $this->record->trips()
                        ->where('operator_id', $user->id)
                        ->active()
                        ->exists();
                }),
            
            Actions\Action::make('report_issue')
                ->label('Reportar Problema')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->action(function () {
                    // This could open a modal or redirect to a maintenance request form
                    // For now, we'll just show a notification
                    \Filament\Notifications\Notification::make()
                        ->title('Función en desarrollo')
                        ->body('La función de reporte de problemas estará disponible próximamente.')
                        ->info()
                        ->send();
                })
                ->visible(fn () => $user && $user->isOperator()),
        ];
    }
}