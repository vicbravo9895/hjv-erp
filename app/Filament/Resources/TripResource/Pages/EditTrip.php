<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use App\Services\VehicleAssignmentService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('complete')
                ->label('Finalizar Viaje')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'in_progress')
                ->requiresConfirmation()
                ->modalHeading('Finalizar Viaje')
                ->modalDescription('¿Está seguro de que desea finalizar este viaje? Esto liberará los recursos asignados.')
                ->action(function () {
                    $this->completeTrip();
                }),
            
            Actions\DeleteAction::make()
                ->visible(fn (): bool => in_array($this->record->status, ['planned', 'cancelled'])),
        ];
    }

    public function getTitle(): string
    {
        return 'Editar Viaje';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $this->handleStatusChange();
    }

    protected function handleStatusChange(): void
    {
        $originalStatus = $this->record->getOriginal('status');
        $newStatus = $this->data['status'];

        if ($originalStatus !== $newStatus) {
            $assignmentService = app(VehicleAssignmentService::class);

            // If changing to in_progress, assign resources
            if ($newStatus === 'in_progress' && $originalStatus === 'planned') {
                $result = $assignmentService->assignToTrip($this->record);
                
                if (!$result['success']) {
                    Notification::make()
                        ->title('Error al asignar recursos')
                        ->body(implode(', ', $result['errors']))
                        ->danger()
                        ->send();
                    
                    $this->halt();
                }
            }

            // If changing to completed, release resources and set completion time
            if ($newStatus === 'completed' && in_array($originalStatus, ['planned', 'in_progress'])) {
                $this->data['completed_at'] = now();
                
                $result = $assignmentService->releaseFromTrip($this->record);
                
                if (!$result['success']) {
                    Notification::make()
                        ->title('Advertencia')
                        ->body('Viaje completado pero no se pudieron liberar todos los recursos: ' . implode(', ', $result['errors']))
                        ->warning()
                        ->send();
                }
            }

            // If changing from in_progress to planned, release resources
            if ($newStatus === 'planned' && $originalStatus === 'in_progress') {
                $result = $assignmentService->releaseFromTrip($this->record);
                
                if (!$result['success']) {
                    Notification::make()
                        ->title('Advertencia')
                        ->body('No se pudieron liberar todos los recursos: ' . implode(', ', $result['errors']))
                        ->warning()
                        ->send();
                }
            }

            // If changing to cancelled, release resources
            if ($newStatus === 'cancelled' && $originalStatus === 'in_progress') {
                $result = $assignmentService->releaseFromTrip($this->record);
                
                if (!$result['success']) {
                    Notification::make()
                        ->title('Advertencia')
                        ->body('Viaje cancelado pero no se pudieron liberar todos los recursos: ' . implode(', ', $result['errors']))
                        ->warning()
                        ->send();
                }
            }
        }
    }

    protected function completeTrip(): void
    {
        $assignmentService = app(VehicleAssignmentService::class);
        
        $this->record->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $result = $assignmentService->releaseFromTrip($this->record);
        
        if ($result['success']) {
            Notification::make()
                ->title('Viaje finalizado correctamente')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Error al liberar recursos')
                ->body(implode(', ', $result['errors']))
                ->danger()
                ->send();
        }

        $this->redirect($this->getResource()::getUrl('index'));
    }
}
