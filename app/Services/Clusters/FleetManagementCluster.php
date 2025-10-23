<?php

namespace App\Services\Clusters;

use App\Filament\Resources\VehicleResource;
use App\Filament\Resources\TrailerResource;
use App\Filament\Resources\OperatorResource;

class FleetManagementCluster extends BaseResourceCluster
{
    protected string $name = 'Gestión de Flota';
    protected string $icon = 'heroicon-o-truck';
    protected int $sort = 10;
    protected bool $collapsedByDefault = true;
    protected array $visiblePanels = ['admin'];
    protected ?string $description = 'Gestión de vehículos, trailers y operadores';

    protected array $resources = [
        VehicleResource::class,
        TrailerResource::class,
        OperatorResource::class,
    ];
}