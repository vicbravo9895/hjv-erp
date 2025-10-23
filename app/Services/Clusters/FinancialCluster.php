<?php

namespace App\Services\Clusters;

use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\ExpenseCategoryResource;
use App\Filament\Resources\ProviderResource;
use App\Filament\Resources\CostCenterResource;
use App\Filament\Resources\TravelExpenseResource;
use App\Filament\Resources\WeeklyPayrollResource;
use App\Filament\Resources\PaymentScaleResource;

class FinancialCluster extends BaseResourceCluster
{
    protected string $name = 'Finanzas';
    protected string $icon = 'heroicon-o-currency-dollar';
    protected int $sort = 40;
    protected bool $collapsedByDefault = true;
    protected array $visiblePanels = ['admin', 'accounting', 'operator'];
    protected ?string $description = 'Gestión de gastos, proveedores y centros de costo';

    protected array $resources = [
        TravelExpenseResource::class,
        ProviderResource::class,
        ExpenseResource::class,
        WeeklyPayrollResource::class,
        CostCenterResource::class,
        ExpenseCategoryResource::class,
        PaymentScaleResource::class,
    ];
}