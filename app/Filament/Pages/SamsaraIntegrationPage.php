<?php

namespace App\Filament\Pages;

use App\Models\SamsaraSyncLog;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Services\SamsaraSyncLogService;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SamsaraIntegrationPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';
    
    protected static ?string $navigationGroup = 'Sistema';
    
    protected static ?string $title = 'Monitoreo Samsara';

    protected static string $view = 'filament.pages.samsara-integration';

    public ?array $data = [];
    public ?array $monitoringData = null;

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->subDays(7),
            'end_date' => now(),
            'sync_type' => null,
        ]);
        
        // Load initial data
        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros de Monitoreo')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->required()
                            ->default(now()->subDays(7)),
                            
                        DatePicker::make('end_date')
                            ->label('Fecha Fin')
                            ->required()
                            ->default(now()),
                            
                        Select::make('sync_type')
                            ->label('Tipo de Sincronización')
                            ->options([
                                'vehicles' => 'Vehículos',
                                'trailers' => 'Trailers',
                            ])
                            ->placeholder('Todos los tipos'),
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
        $syncType = $data['sync_type'] ?? null;

        $syncLogService = app(SamsaraSyncLogService::class);

        // Get sync statistics
        $stats = $syncLogService->getSyncStats($startDate, $endDate, $syncType);

        // Get recent sync logs
        $recentSyncs = $syncLogService->getRecentSyncs(50, $syncType);

        // Get current system status
        $systemStatus = $this->getSystemStatus();

        // Get sync configuration
        $syncConfig = $this->getSyncConfiguration();

        $this->monitoringData = [
            'stats' => $stats,
            'recent_syncs' => $recentSyncs,
            'system_status' => $systemStatus,
            'sync_config' => $syncConfig,
            'filters' => $data,
        ];
    }

    public function getMonitoringData(): ?array
    {
        return $this->monitoringData;
    }

    protected function getSystemStatus(): array
    {
        // Check for running syncs
        $runningSyncs = SamsaraSyncLog::running()
            ->where('started_at', '>', now()->subMinutes(30))
            ->get();

        // Check for stuck syncs
        $stuckSyncs = SamsaraSyncLog::running()
            ->where('started_at', '<', now()->subMinutes(30))
            ->get();

        // Get last successful sync for each type
        $lastVehicleSync = SamsaraSyncLog::ofType('vehicles')
            ->successful()
            ->orderBy('completed_at', 'desc')
            ->first();

        $lastTrailerSync = SamsaraSyncLog::ofType('trailers')
            ->successful()
            ->orderBy('completed_at', 'desc')
            ->first();

        // Get sync health (success rate last 24 hours)
        $vehicleSuccessRate = SamsaraSyncLog::getSuccessRate('vehicles', 1);
        $trailerSuccessRate = SamsaraSyncLog::getSuccessRate('trailers', 1);

        // Get data freshness
        $vehicleDataFreshness = Vehicle::where('synced_at', '>', now()->subHours(2))->count();
        $trailerDataFreshness = Trailer::where('synced_at', '>', now()->subHours(2))->count();

        return [
            'running_syncs' => $runningSyncs,
            'stuck_syncs' => $stuckSyncs,
            'last_syncs' => [
                'vehicles' => $lastVehicleSync,
                'trailers' => $lastTrailerSync,
            ],
            'success_rates' => [
                'vehicles' => $vehicleSuccessRate,
                'trailers' => $trailerSuccessRate,
            ],
            'data_freshness' => [
                'vehicles' => $vehicleDataFreshness,
                'trailers' => $trailerDataFreshness,
            ],
            'total_vehicles' => Vehicle::count(),
            'total_trailers' => Trailer::count(),
        ];
    }

    protected function getSyncConfiguration(): array
    {
        return [
            'vehicles_enabled' => config('samsara.sync.enable_vehicles_sync', true),
            'trailers_enabled' => config('samsara.sync.enable_trailers_sync', true),
            'operating_hours' => config('samsara.sync.operating_hours'),
            'weekdays_only' => config('samsara.sync.weekdays_only', false),
            'timeout' => config('samsara.sync.timeout', 30),
            'retry_times' => config('samsara.sync.retry_times', 3),
            'page_limit' => config('samsara.sync.page_limit', 100),
        ];
    }

    public function getStats(): array
    {
        if (!$this->monitoringData) {
            return [];
        }

        $stats = $this->monitoringData['stats'];
        $systemStatus = $this->monitoringData['system_status'];

        return [
            Stat::make('Total de Sincronizaciones', $stats['total_syncs'])
                ->description('En el período seleccionado')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Sincronizaciones Exitosas', $stats['successful_syncs'])
                ->description(sprintf('%.1f%% de éxito', $stats['total_syncs'] > 0 ? ($stats['successful_syncs'] / $stats['total_syncs']) * 100 : 0))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Sincronizaciones Fallidas', $stats['failed_syncs'])
                ->description(sprintf('%.1f%% de fallos', $stats['total_syncs'] > 0 ? ($stats['failed_syncs'] / $stats['total_syncs']) * 100 : 0))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($stats['failed_syncs'] > 0 ? 'danger' : 'success'),

            Stat::make('Registros Sincronizados', number_format($stats['total_records_synced']))
                ->description('Total de registros procesados')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('info'),

            Stat::make('Duración Promedio', $stats['average_duration'] ? round($stats['average_duration'], 1) . 's' : 'N/A')
                ->description('Tiempo promedio por sincronización')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Sincronizaciones Activas', count($systemStatus['running_syncs']))
                ->description(count($systemStatus['stuck_syncs']) > 0 ? count($systemStatus['stuck_syncs']) . ' bloqueadas' : 'Funcionando normalmente')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color(count($systemStatus['stuck_syncs']) > 0 ? 'danger' : 'success'),
        ];
    }

    public function refreshData(): void
    {
        $this->generateReport();
        $this->dispatch('refresh-page');
    }
}