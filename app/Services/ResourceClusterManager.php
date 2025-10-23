<?php

namespace App\Services;

use App\Contracts\ResourceClusterInterface;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Collection;

class ResourceClusterManager
{
    protected array $clusters = [];
    protected array $resourceClusterMap = [];

    /**
     * Register a cluster
     */
    public function registerCluster(ResourceClusterInterface $cluster): void
    {
        $this->clusters[$cluster->getClusterName()] = $cluster;
        
        // Map resources to their cluster
        foreach ($cluster->getClusterResources() as $resource) {
            $this->resourceClusterMap[$resource] = $cluster->getClusterName();
        }
    }

    /**
     * Get all registered clusters
     */
    public function getClusters(): array
    {
        return $this->clusters;
    }

    /**
     * Get clusters for a specific panel
     */
    public function getClustersForPanel(string $panelId): Collection
    {
        return collect($this->clusters)
            ->filter(function (ResourceClusterInterface $cluster) use ($panelId) {
                return in_array($panelId, $cluster->getVisiblePanels()) || 
                       in_array('*', $cluster->getVisiblePanels());
            })
            ->sortBy(function (ResourceClusterInterface $cluster) {
                return $cluster->getClusterSort();
            });
    }

    /**
     * Get cluster for a specific resource
     */
    public function getClusterForResource(string $resource): ?ResourceClusterInterface
    {
        $clusterName = $this->resourceClusterMap[$resource] ?? null;
        return $clusterName ? $this->clusters[$clusterName] : null;
    }

    /**
     * Generate navigation groups for a panel
     */
    public function generateNavigationGroupsForPanel(string $panelId): array
    {
        return $this->getClustersForPanel($panelId)
            ->map(function (ResourceClusterInterface $cluster) {
                return NavigationGroup::make()
                    ->label($cluster->getClusterName())
                    ->icon($cluster->getClusterIcon())
                    ->collapsed($cluster->isCollapsedByDefault());
            })
            ->values()
            ->toArray();
    }

    /**
     * Get resources organized by clusters for a panel
     */
    public function getResourcesForPanel(string $panelId, array $availableResources): array
    {
        $clusteredResources = [];
        $unclustered = [];

        foreach ($availableResources as $resource) {
            $cluster = $this->getClusterForResource($resource);
            
            if ($cluster && in_array($panelId, $cluster->getVisiblePanels())) {
                $clusteredResources[] = $resource;
            } else {
                $unclustered[] = $resource;
            }
        }

        return array_merge($clusteredResources, $unclustered);
    }
}