<?php

return [
    App\Providers\AppServiceProvider::class,
    // App\Providers\ResourceClusterServiceProvider::class, // Disabled - using direct resource registration
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\AccountingPanelProvider::class,
    App\Providers\Filament\WorkshopPanelProvider::class,
    App\Providers\Filament\OperatorPanelProvider::class,
];
