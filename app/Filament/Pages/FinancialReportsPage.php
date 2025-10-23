<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Provider;
use App\Models\CostCenter;
use App\Models\WeeklyPayroll;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FinancialReportsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.financial-reports';
    protected static ?string $navigationLabel = 'Reportes Financieros';
    protected static ?string $title = 'Reportes Financieros';
    protected static ?string $navigationGroup = 'Reportes';

    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $category_id = null;
    public ?string $provider_id = null;
    public ?string $cost_center_id = null;
    public string $report_type = 'expenses';

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('report_type')
                    ->label('Tipo de Reporte')
                    ->options([
                        'expenses' => 'Gastos por Período',
                        'expenses_by_category' => 'Gastos por Categoría',
                        'expenses_by_provider' => 'Gastos por Proveedor',
                        'expenses_by_cost_center' => 'Gastos por Centro de Costo',
                        'payroll_summary' => 'Resumen de Nómina',
                    ])
                    ->default('expenses')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),

                DatePicker::make('start_date')
                    ->label('Fecha Inicio')
                    ->default(now()->startOfMonth())
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),

                DatePicker::make('end_date')
                    ->label('Fecha Fin')
                    ->default(now()->endOfMonth())
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),

                Select::make('category_id')
                    ->label('Categoría')
                    ->options(ExpenseCategory::pluck('name', 'id'))
                    ->placeholder('Todas las categorías')
                    ->visible(fn () => in_array($this->report_type, ['expenses', 'expenses_by_category']))
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),

                Select::make('provider_id')
                    ->label('Proveedor')
                    ->options(Provider::pluck('name', 'id'))
                    ->placeholder('Todos los proveedores')
                    ->visible(fn () => in_array($this->report_type, ['expenses', 'expenses_by_provider']))
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),

                Select::make('cost_center_id')
                    ->label('Centro de Costo')
                    ->options(CostCenter::pluck('name', 'id'))
                    ->placeholder('Todos los centros de costo')
                    ->visible(fn () => in_array($this->report_type, ['expenses', 'expenses_by_cost_center']))
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->headerActions([
                Action::make('export')
                    ->label('Exportar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        // TODO: Implement export functionality
                        $this->notify('success', 'Funcionalidad de exportación pendiente de implementar');
                    }),
            ])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        $startDate = $this->start_date ? Carbon::parse($this->start_date) : now()->startOfMonth();
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : now()->endOfMonth();

        switch ($this->report_type) {
            case 'expenses':
                return $this->getExpensesQuery($startDate, $endDate);
            
            case 'expenses_by_category':
                return $this->getExpensesByCategoryQuery($startDate, $endDate);
            
            case 'expenses_by_provider':
                return $this->getExpensesByProviderQuery($startDate, $endDate);
            
            case 'expenses_by_cost_center':
                return $this->getExpensesByCostCenterQuery($startDate, $endDate);
            
            case 'payroll_summary':
                return $this->getPayrollSummaryQuery($startDate, $endDate);
            
            default:
                return Expense::query()->whereRaw('1 = 0'); // Empty query
        }
    }

    protected function getTableColumns(): array
    {
        switch ($this->report_type) {
            case 'expenses':
                return [
                    TextColumn::make('date')
                        ->label('Fecha')
                        ->date('d/m/Y')
                        ->sortable(),
                    TextColumn::make('description')
                        ->label('Descripción')
                        ->searchable(),
                    TextColumn::make('category.name')
                        ->label('Categoría')
                        ->searchable(),
                    TextColumn::make('provider.name')
                        ->label('Proveedor')
                        ->searchable(),
                    TextColumn::make('cost_center.name')
                        ->label('Centro de Costo')
                        ->searchable(),
                    TextColumn::make('amount')
                        ->label('Monto')
                        ->money('MXN')
                        ->sortable(),
                ];

            case 'expenses_by_category':
                return [
                    TextColumn::make('category_name')
                        ->label('Categoría'),
                    TextColumn::make('total_amount')
                        ->label('Total')
                        ->money('MXN'),
                    TextColumn::make('expense_count')
                        ->label('Cantidad de Gastos'),
                    TextColumn::make('average_amount')
                        ->label('Promedio')
                        ->money('MXN'),
                ];

            case 'expenses_by_provider':
                return [
                    TextColumn::make('provider_name')
                        ->label('Proveedor'),
                    TextColumn::make('total_amount')
                        ->label('Total')
                        ->money('MXN'),
                    TextColumn::make('expense_count')
                        ->label('Cantidad de Gastos'),
                    TextColumn::make('average_amount')
                        ->label('Promedio')
                        ->money('MXN'),
                ];

            case 'expenses_by_cost_center':
                return [
                    TextColumn::make('cost_center_name')
                        ->label('Centro de Costo'),
                    TextColumn::make('total_amount')
                        ->label('Total')
                        ->money('MXN'),
                    TextColumn::make('expense_count')
                        ->label('Cantidad de Gastos'),
                    TextColumn::make('budget')
                        ->label('Presupuesto')
                        ->money('MXN'),
                    TextColumn::make('budget_usage')
                        ->label('% Usado')
                        ->formatStateUsing(fn ($state) => number_format($state, 1) . '%'),
                ];

            case 'payroll_summary':
                return [
                    TextColumn::make('operator_name')
                        ->label('Operador'),
                    TextColumn::make('week_period')
                        ->label('Período'),
                    TextColumn::make('trips_count')
                        ->label('Viajes'),
                    TextColumn::make('base_payment')
                        ->label('Pago Base')
                        ->money('MXN'),
                    TextColumn::make('adjustments')
                        ->label('Ajustes')
                        ->money('MXN'),
                    TextColumn::make('total_payment')
                        ->label('Total')
                        ->money('MXN'),
                ];

            default:
                return [];
        }
    }

    protected function getExpensesQuery(Carbon $startDate, Carbon $endDate): Builder
    {
        $query = Expense::with(['category', 'provider', 'costCenter'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        if ($this->provider_id) {
            $query->where('provider_id', $this->provider_id);
        }

        if ($this->cost_center_id) {
            $query->where('cost_center_id', $this->cost_center_id);
        }

        return $query->orderBy('date', 'desc');
    }

    protected function getExpensesByCategoryQuery(Carbon $startDate, Carbon $endDate): Builder
    {
        return DB::table('expenses')
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->when($this->category_id, fn ($query) => $query->where('expenses.category_id', $this->category_id))
            ->select([
                'expense_categories.name as category_name',
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('AVG(expenses.amount) as average_amount'),
            ])
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderBy('total_amount', 'desc');
    }

    protected function getExpensesByProviderQuery(Carbon $startDate, Carbon $endDate): Builder
    {
        return DB::table('expenses')
            ->join('providers', 'expenses.provider_id', '=', 'providers.id')
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->when($this->provider_id, fn ($query) => $query->where('expenses.provider_id', $this->provider_id))
            ->select([
                'providers.name as provider_name',
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('AVG(expenses.amount) as average_amount'),
            ])
            ->groupBy('providers.id', 'providers.name')
            ->orderBy('total_amount', 'desc');
    }

    protected function getExpensesByCostCenterQuery(Carbon $startDate, Carbon $endDate): Builder
    {
        return DB::table('expenses')
            ->join('cost_centers', 'expenses.cost_center_id', '=', 'cost_centers.id')
            ->whereBetween('expenses.date', [$startDate, $endDate])
            ->when($this->cost_center_id, fn ($query) => $query->where('expenses.cost_center_id', $this->cost_center_id))
            ->select([
                'cost_centers.name as cost_center_name',
                'cost_centers.budget',
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('(SUM(expenses.amount) / cost_centers.budget * 100) as budget_usage'),
            ])
            ->groupBy('cost_centers.id', 'cost_centers.name', 'cost_centers.budget')
            ->orderBy('total_amount', 'desc');
    }

    protected function getPayrollSummaryQuery(Carbon $startDate, Carbon $endDate): Builder
    {
        return DB::table('weekly_payrolls')
            ->join('operators', 'weekly_payrolls.operator_id', '=', 'operators.id')
            ->whereBetween('weekly_payrolls.week_start', [$startDate, $endDate])
            ->select([
                'operators.name as operator_name',
                DB::raw("CONCAT(DATE_FORMAT(weekly_payrolls.week_start, '%d/%m'), ' - ', DATE_FORMAT(weekly_payrolls.week_end, '%d/%m/%Y')) as week_period"),
                'weekly_payrolls.trips_count',
                'weekly_payrolls.base_payment',
                'weekly_payrolls.adjustments',
                'weekly_payrolls.total_payment',
            ])
            ->orderBy('weekly_payrolls.week_start', 'desc');
    }

    protected function notify(string $type, string $message): void
    {
        session()->flash('filament.notifications', [
            [
                'type' => $type,
                'title' => $message,
            ]
        ]);
    }
}