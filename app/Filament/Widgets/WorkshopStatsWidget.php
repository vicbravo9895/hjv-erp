<?php

namespace App\Filament\Widgets;

use App\Models\SparePart;
use App\Models\ProductRequest;
use App\Models\ProductUsage;
use App\Models\MaintenanceRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WorkshopStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Low stock count (stock <= 10)
        $lowStockCount = SparePart::where('stock_quantity', '<=', 10)->count();
        
        // Out of stock count (stock = 0)
        $outOfStockCount = SparePart::where('stock_quantity', '<=', 0)->count();
        
        // Pending requests count
        $pendingRequestsCount = ProductRequest::where('status', 'pending')->count();
        
        // Recent product usage (last 7 days)
        $recentUsageCount = ProductUsage::where('date_used', '>=', now()->subDays(7))->count();
        
        // Recent maintenance records (last 7 days)
        $recentMaintenanceCount = MaintenanceRecord::where('date', '>=', now()->subDays(7))->count();
        
        // Total spare parts value
        $totalInventoryValue = SparePart::selectRaw('SUM(stock_quantity * unit_cost) as total')->value('total') ?? 0;

        return [
            Stat::make('Stock Bajo', $lowStockCount)
                ->description('Productos con stock ≤ 10 unidades')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'warning' : 'success')
                ->url(route('filament.workshop.resources.spare-parts.index', ['tableFilters[low_stock][isActive]' => true])),

            Stat::make('Sin Stock', $outOfStockCount)
                ->description('Productos agotados')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockCount > 0 ? 'danger' : 'success')
                ->url(route('filament.workshop.resources.spare-parts.index', ['tableFilters[out_of_stock][isActive]' => true])),

            Stat::make('Solicitudes Pendientes', $pendingRequestsCount)
                ->description('Esperando aprobación')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingRequestsCount > 0 ? 'info' : 'success')
                ->url(route('filament.workshop.resources.product-requests.index', ['tableFilters[status][value]' => 'pending'])),

            Stat::make('Uso Reciente', $recentUsageCount)
                ->description('Productos usados (últimos 7 días)')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->url(route('filament.workshop.resources.product-usages.index')),

            Stat::make('Mantenimientos', $recentMaintenanceCount)
                ->description('Realizados (últimos 7 días)')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('success')
                ->url(route('filament.workshop.resources.maintenance-records.index')),

            Stat::make('Valor Inventario', '$' . number_format($totalInventoryValue, 2))
                ->description('Valor total del inventario')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info')
                ->url(route('filament.workshop.resources.spare-parts.index')),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}