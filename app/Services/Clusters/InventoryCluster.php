<?php

namespace App\Services\Clusters;

use App\Filament\Resources\SparePartResource;
use App\Filament\Resources\ProductUsageResource;
use App\Filament\Resources\ProductRequestResource;

class InventoryCluster extends BaseResourceCluster
{
    protected string $name = 'Inventario';
    protected string $icon = 'heroicon-o-cube';
    protected int $sort = 35;
    protected bool $collapsedByDefault = true;
    protected array $visiblePanels = ['admin', 'workshop'];
    protected ?string $description = 'Gestión de inventario y refacciones';

    protected array $resources = [
        ProductUsageResource::class,
        SparePartResource::class,
        ProductRequestResource::class,
    ];
}