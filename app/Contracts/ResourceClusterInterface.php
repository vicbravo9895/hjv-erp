<?php

namespace App\Contracts;

interface ResourceClusterInterface
{
    /**
     * Get the cluster name/label
     */
    public function getClusterName(): string;

    /**
     * Get the cluster icon
     */
    public function getClusterIcon(): string;

    /**
     * Get the cluster sort order
     */
    public function getClusterSort(): int;

    /**
     * Get the resources that belong to this cluster
     */
    public function getClusterResources(): array;

    /**
     * Whether the cluster should be collapsed by default
     */
    public function isCollapsedByDefault(): bool;

    /**
     * Get the panels where this cluster should be visible
     */
    public function getVisiblePanels(): array;

    /**
     * Get the cluster description (optional)
     */
    public function getClusterDescription(): ?string;
}