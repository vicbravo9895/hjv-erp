<?php

namespace App\Services\Clusters;

use App\Filament\Resources\OperatorTripResource;

class MyTripsCluster extends BaseResourceCluster
{
    protected string $name = 'Mis Viajes';
    protected string $icon = 'heroicon-o-map-pin';
    protected int $sort = 15;
    protected bool $collapsedByDefault = true;
    protected array $visiblePanels = ['operator'];
    protected ?string $description = 'Mis viajes como operador';

    protected array $resources = [
        OperatorTripResource::class,
    ];
}