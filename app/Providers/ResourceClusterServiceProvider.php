<?php

namespace App\Providers;

use App\Services\ResourceClusterManager;
use App\Services\Clusters\FleetManagementCluster;
use App\Services\Clusters\MyTripsCluster;
use App\Services\Clusters\OperationsCluster;
use App\Services\Clusters\MaintenanceCluster;
use App\Services\Clusters\InventoryCluster;
use App\Services\Clusters\FinancialCluster;
use App\Services\Clusters\SystemCluster;
use Illuminate\Support\ServiceProvider;

class ResourceClusterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResourceClusterManager::class, function ($app) {
            $manager = new ResourceClusterManager();
            
            // Register all clusters
            $manager->registerCluster(new FleetManagementCluster());
            $manager->registerCluster(new MyTripsCluster());
            $manager->registerCluster(new OperationsCluster());
            $manager->registerCluster(new MaintenanceCluster());
            $manager->registerCluster(new InventoryCluster());
            $manager->registerCluster(new FinancialCluster());
            $manager->registerCluster(new SystemCluster());
            
            return $manager;
        });
    }

    public function boot(): void
    {
        //
    }
}