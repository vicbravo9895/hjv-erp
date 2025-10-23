<?php

namespace App\Services\Clusters;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\SamsaraSyncLogResource;

class SystemCluster extends BaseResourceCluster
{
    protected string $name = 'Sistema';
    protected string $icon = 'heroicon-o-cog-6-tooth';
    protected int $sort = 60;
    protected bool $collapsedByDefault = true;
    protected array $visiblePanels = ['admin'];
    protected ?string $description = 'Configuración del sistema y logs';

    protected array $resources = [
        UserResource::class,
        SamsaraSyncLogResource::class,
    ];
}