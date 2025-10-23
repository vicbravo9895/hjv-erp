<?php

namespace App\Services\Clusters;

use App\Contracts\ResourceClusterInterface;

abstract class BaseResourceCluster implements ResourceClusterInterface
{
    protected string $name;
    protected string $icon;
    protected int $sort = 0;
    protected array $resources = [];
    protected bool $collapsedByDefault = true;
    protected array $visiblePanels = ['*'];
    protected ?string $description = null;

    public function getClusterName(): string
    {
        return $this->name;
    }

    public function getClusterIcon(): string
    {
        return $this->icon;
    }

    public function getClusterSort(): int
    {
        return $this->sort;
    }

    public function getClusterResources(): array
    {
        return $this->resources;
    }

    public function isCollapsedByDefault(): bool
    {
        return $this->collapsedByDefault;
    }

    public function getVisiblePanels(): array
    {
        return $this->visiblePanels;
    }

    public function getClusterDescription(): ?string
    {
        return $this->description;
    }
}