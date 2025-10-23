<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filters Form -->
        <x-filament::section>
            <x-slot name="heading">
                Filtros de Monitoreo
            </x-slot>
            
            {{ $this->form }}
            
            <x-slot name="footerActions">
                <x-filament::button wire:click="generateReport" color="primary">
                    Actualizar Datos
                </x-filament::button>
                
                <x-filament::button wire:click="refreshData" color="gray" outlined>
                    <x-heroicon-m-arrow-path class="w-4 h-4 mr-2" />
                    Refrescar
                </x-filament::button>
            </x-slot>
        </x-filament::section>

        @if($this->getMonitoringData())
            @php
                $data = $this->getMonitoringData();
                $stats = $data['stats'];
                $systemStatus = $data['system_status'];
                $syncConfig = $data['sync_config'];
                $recentSyncs = $data['recent_syncs'];
            @endphp

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($this->getStats() as $stat)
                    <x-filament::card>
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        {{ $stat->getLabel() }}
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ $stat->getValue() }}
                                    </p>
                                    @if($stat->getDescription())
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $stat->getDescription() }}
                                        </p>
                                    @endif
                                </div>
                                @if($stat->getIcon())
                                    <div class="p-3 rounded-full bg-{{ $stat->getColor() ?? 'gray' }}-100 dark:bg-{{ $stat->getColor() ?? 'gray' }}-900">
                                        <x-dynamic-component :component="$stat->getIcon()" class="w-6 h-6 text-{{ $stat->getColor() ?? 'gray' }}-600 dark:text-{{ $stat->getColor() ?? 'gray' }}-400" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-filament::card>
                @endforeach
            </div>

            <!-- System Status -->
            <x-filament::section>
                <x-slot name="heading">
                    Estado del Sistema
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Configuration Status -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Configuración</h4>
                        
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium">Sincronización de Vehículos</span>
                                <span class="px-2 py-1 text-xs rounded-full {{ $syncConfig['vehicles_enabled'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                    {{ $syncConfig['vehicles_enabled'] ? 'Habilitada' : 'Deshabilitada' }}
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium">Sincronización de Trailers</span>
                                <span class="px-2 py-1 text-xs rounded-full {{ $syncConfig['trailers_enabled'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                    {{ $syncConfig['trailers_enabled'] ? 'Habilitada' : 'Deshabilitada' }}
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium">Horario Operativo</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $syncConfig['operating_hours']['start'] }}:00 - {{ $syncConfig['operating_hours']['end'] }}:00
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium">Solo Días Laborales</span>
                                <span class="px-2 py-1 text-xs rounded-full {{ $syncConfig['weekdays_only'] ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                                    {{ $syncConfig['weekdays_only'] ? 'Sí' : 'No' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Health Status -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Estado de Salud</h4>
                        
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium">Éxito Vehículos (24h)</span>
                                <span class="px-2 py-1 text-xs rounded-full {{ $systemStatus['success_rates']['vehicles'] >= 90 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($systemStatus['success_rates']['vehicles'] >= 70 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                    {{ $systemStatus['success_rates']['vehicles'] }}%
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium">Éxito Trailers (24h)</span>
                                <span class="px-2 py-1 text-xs rounded-full {{ $systemStatus['success_rates']['trailers'] >= 90 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($systemStatus['success_rates']['trailers'] >= 70 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                    {{ $systemStatus['success_rates']['trailers'] }}%
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium">Datos Frescos Vehículos</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $systemStatus['data_freshness']['vehicles'] }} / {{ $systemStatus['total_vehicles'] }}
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-sm font-medium">Datos Frescos Trailers</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $systemStatus['data_freshness']['trailers'] }} / {{ $systemStatus['total_trailers'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @if(count($systemStatus['running_syncs']) > 0 || count($systemStatus['stuck_syncs']) > 0)
                    <div class="mt-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sincronizaciones Activas</h4>
                        
                        @if(count($systemStatus['running_syncs']) > 0)
                            <div class="mb-4">
                                <h5 class="text-md font-medium text-green-700 dark:text-green-300 mb-2">En Progreso</h5>
                                <div class="space-y-2">
                                    @foreach($systemStatus['running_syncs'] as $sync)
                                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                            <div>
                                                <span class="text-sm font-medium">{{ ucfirst($sync->sync_type) }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                                    Iniciado: {{ $sync->started_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <div class="flex items-center">
                                                <x-heroicon-m-arrow-path class="w-4 h-4 text-green-600 dark:text-green-400 animate-spin mr-2" />
                                                <span class="text-xs text-green-600 dark:text-green-400">Ejecutando</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(count($systemStatus['stuck_syncs']) > 0)
                            <div>
                                <h5 class="text-md font-medium text-red-700 dark:text-red-300 mb-2">Bloqueadas</h5>
                                <div class="space-y-2">
                                    @foreach($systemStatus['stuck_syncs'] as $sync)
                                        <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                            <div>
                                                <span class="text-sm font-medium">{{ ucfirst($sync->sync_type) }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                                    Iniciado: {{ $sync->started_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <div class="flex items-center">
                                                <x-heroicon-m-exclamation-triangle class="w-4 h-4 text-red-600 dark:text-red-400 mr-2" />
                                                <span class="text-xs text-red-600 dark:text-red-400">Bloqueada</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </x-filament::section>

            <!-- Recent Sync Logs -->
            <x-filament::section>
                <x-slot name="heading">
                    Registros Recientes de Sincronización
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Registros
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Duración
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Iniciado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Completado
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($recentSyncs as $sync)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ ucfirst($sync->sync_type) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            {{ $sync->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                               ($sync->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                            {{ $sync->status === 'completed' ? 'Completado' : ($sync->status === 'failed' ? 'Fallido' : 'Ejecutando') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $sync->synced_records ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $sync->formatted_duration }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $sync->started_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $sync->completed_at ? $sync->completed_at->format('d/m/Y H:i:s') : 'N/A' }}
                                    </td>
                                </tr>
                                @if($sync->status === 'failed' && $sync->error_message)
                                    <tr class="bg-red-50 dark:bg-red-900/10">
                                        <td colspan="6" class="px-6 py-2 text-sm text-red-600 dark:text-red-400">
                                            <strong>Error:</strong> {{ $sync->error_message }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No hay registros de sincronización en el período seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <!-- Sync by Type Statistics -->
            @if(isset($stats['by_type']) && count($stats['by_type']) > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        Estadísticas por Tipo de Sincronización
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($stats['by_type'] as $type => $typeStats)
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    {{ ucfirst($type) }}
                                </h4>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Total:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $typeStats['count'] }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Exitosas:</span>
                                        <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $typeStats['successful'] }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Fallidas:</span>
                                        <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $typeStats['failed'] }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Registros Sincronizados:</span>
                                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ number_format($typeStats['records_synced']) }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Tasa de Éxito:</span>
                                        <span class="text-sm font-medium {{ $typeStats['count'] > 0 && ($typeStats['successful'] / $typeStats['count']) >= 0.9 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                            {{ $typeStats['count'] > 0 ? round(($typeStats['successful'] / $typeStats['count']) * 100, 1) : 0 }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>