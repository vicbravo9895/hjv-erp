<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\User;
use App\Services\VehicleAssignmentService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    public function getTitle(): string
    {
        return 'Crear Viaje';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $this->validateTripAssignment();
    }

    protected function afterCreate(): void
    {
        $this->assignResourcesToTrip();
    }

    protected function validateTripAssignment(): void
    {
        $data = $this->data;
        
        $vehicle = Vehicle::find($data['truck_id']);
        $trailer = isset($data['trailer_id']) ? Trailer::find($data['trailer_id']) : null;
        $operator = User::find($data['operator_id']);

        if (!$vehicle || !$operator) {
            $this->halt();
            return;
        }

        $assignmentService = app(VehicleAssignmentService::class);
        
        $startDateTime = new \DateTime($data['start_date']);
        $endDateTime = new \DateTime($data['end_date'] ?? $data['start_date']);

        if ($trailer) {
            $validation = $assignmentService->validateTripAssignment(
                $vehicle,
                $trailer,
                $operator,
                $startDateTime,
                $endDateTime
            );
        } else {
            // Validate without trailer
            $vehicleValidation = $assignmentService->canAssignVehicle($vehicle, $startDateTime, $endDateTime);
            $operatorValidation = $assignmentService->canAssignOperator($operator, $startDateTime, $endDateTime);
            
            $validation = [
                'can_assign' => $vehicleValidation['can_assign'] && $operatorValidation['can_assign'],
                'errors' => array_merge($vehicleValidation['errors'], $operatorValidation['errors'])
            ];
        }

        if (!$validation['can_assign']) {
            Notification::make()
                ->title('Error de Validación')
                ->body('No se puede crear el viaje: ' . implode(', ', $validation['errors']))
                ->danger()
                ->persistent()
                ->send();
            
            $this->halt();
        }
    }

    protected function assignResourcesToTrip(): void
    {
        $trip = $this->record;
        
        // Only assign resources if trip is in progress
        if ($trip->status === 'in_progress') {
            $assignmentService = app(VehicleAssignmentService::class);
            $result = $assignmentService->assignToTrip($trip);
            
            if (!$result['success']) {
                Notification::make()
                    ->title('Advertencia')
                    ->body('Viaje creado pero no se pudieron asignar todos los recursos: ' . implode(', ', $result['errors']))
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Viaje creado exitosamente')
                    ->body('Los recursos han sido asignados correctamente.')
                    ->success()
                    ->send();
            }
        }
    }
}
