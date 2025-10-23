<?php

namespace App\Services\Clusters;

use App\Filament\Resources\MaintenanceRecordResource;

class MaintenanceCluster extends BaseResourceCluster
{
    protected string $name = 'Mantenimiento';
    protected string $icon = 'heroicon-o-wrench-screwdriver';
    protected int $sort = 30;
    protected bool $collapsedByDefault = false;
    protected array $visiblePanels = ['admin', 'workshop'];
    protected ?string $description = 'Gestión de mantenimiento y taller';

    protected array $resources = [
        MaintenanceRecordResource::class,
    ];
}