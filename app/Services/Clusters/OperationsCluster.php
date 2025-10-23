<?php

namespace App\Services\Clusters;

use App\Filament\Resources\TripResource;
use App\Filament\Resources\TripCostResource;
use App\Filament\Resources\OperatorTripResource;

class OperationsCluster extends BaseResourceCluster
{
    protected string $name = 'Operaciones';
    protected string $icon = 'heroicon-o-map';
    protected int $sort = 20;
    protected bool $collapsedByDefault = true;
    protected array $visiblePanels = ['admin', 'operator'];
    protected ?string $description = 'Gestión de viajes y operaciones';

    protected array $resources = [
        TripResource::class,
        TripCostResource::class,
    ];
}