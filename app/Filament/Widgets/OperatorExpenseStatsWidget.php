<?php

namespace App\Filament\Widgets;

use App\Models\TravelExpense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OperatorExpenseStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'Gastos por Tipo (Últimos 6 Meses)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->isOperator()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Get expense data by type for the last 6 months
        $expenseTypes = TravelExpense::EXPENSE_TYPES;
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo)->format('M Y');
        });

        $datasets = [];
        $colors = [
            'fuel' => 'rgb(255, 193, 7)',      // Warning/Yellow
            'tolls' => 'rgb(13, 202, 240)',    // Info/Cyan
            'food' => 'rgb(25, 135, 84)',      // Success/Green
            'accommodation' => 'rgb(13, 110, 253)', // Primary/Blue
            'other' => 'rgb(108, 117, 125)',   // Secondary/Gray
        ];

        foreach ($expenseTypes as $type => $label) {
            $data = $months->map(function ($month) use ($user, $type) {
                $monthDate = \Carbon\Carbon::createFromFormat('M Y', $month);
                return TravelExpense::where('operator_id', $user->id)
                    ->where('expense_type', $type)
                    ->whereMonth('date', $monthDate->month)
                    ->whereYear('date', $monthDate->year)
                    ->sum('amount');
            });

            $datasets[] = [
                'label' => $label,
                'data' => $data->toArray(),
                'backgroundColor' => $colors[$type] ?? 'rgb(108, 117, 125)',
                'borderColor' => $colors[$type] ?? 'rgb(108, 117, 125)',
                'borderWidth' => 2,
                'fill' => false,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toLocaleString(); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": $" + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
}