<?php

namespace App\Filament\Pages;

use App\Models\WeeklyPayroll;
use App\Models\User;
use App\Services\PaymentCalculationService;
use App\Services\WeeklyTripCountService;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    
    protected static ?string $navigationGroup = 'Nómina';
    
    protected static ?string $title = 'Reportes de Nómina';

    protected static string $view = 'filament.pages.payroll-report';

    public ?array $data = [];
    public ?array $reportData = null;

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'operator_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros de Reporte')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->required()
                            ->default(now()->startOfMonth()),
                            
                        DatePicker::make('end_date')
                            ->label('Fecha Fin')
                            ->required()
                            ->default(now()->endOfMonth()),
                            
                        Select::make('operator_id')
                            ->label('Operador (Opcional)')
                            ->options(fn (): array => User::operators()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->placeholder('Todos los operadores'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $operatorId = $data['operator_id'] ?? null;

        $paymentService = app(PaymentCalculationService::class);
        $tripCountService = app(WeeklyTripCountService::class);

        // Get payroll summary
        $summary = $paymentService->calculatePayrollSummary($startDate, $endDate);

        // Filter by operator if specified
        if ($operatorId) {
            $summary['payrolls'] = $summary['payrolls']->where('operator_id', $operatorId);
            $summary['operator_count'] = 1;
            $summary['total_payments'] = $summary['payrolls']->sum('total_payment');
            $summary['total_base_payments'] = $summary['payrolls']->sum('base_payment');
            $summary['total_adjustments'] = $summary['payrolls']->sum('adjustments');
            $summary['total_trips'] = $summary['payrolls']->sum('trips_count');
            $summary['average_payment_per_operator'] = $summary['total_payments'];
        }

        // Group payrolls by operator
        $payrollsByOperator = $summary['payrolls']->groupBy('operator_id')->map(function ($payrolls) {
            $operator = $payrolls->first()->operator;
            return [
                'operator' => $operator,
                'payrolls' => $payrolls,
                'total_payment' => $payrolls->sum('total_payment'),
                'total_base_payment' => $payrolls->sum('base_payment'),
                'total_adjustments' => $payrolls->sum('adjustments'),
                'total_trips' => $payrolls->sum('trips_count'),
                'weeks_count' => $payrolls->count(),
                'average_weekly_payment' => $payrolls->avg('total_payment'),
            ];
        });

        // Generate week ranges for the period
        $weekRanges = $tripCountService->generateWeekRanges($startDate, $endDate);

        $this->reportData = [
            'summary' => $summary,
            'payrolls_by_operator' => $payrollsByOperator,
            'week_ranges' => $weekRanges,
            'filters' => $data,
        ];
    }

    public function getReportData(): ?array
    {
        return $this->reportData;
    }
}
