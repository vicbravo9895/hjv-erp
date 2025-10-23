<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Section --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Bienvenido al Sistema de Gestión de Flota
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Panel de control principal - {{ now()->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Widgets Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            @foreach ($this->getWidgets() as $widget)
                @if (!in_array($widget, [\App\Filament\Widgets\RecentTripsWidget::class]))
                    @livewire($widget)
                @endif
            @endforeach
        </div>

        {{-- Recent Trips Table --}}
        <div class="col-span-full">
            @livewire(\App\Filament\Widgets\RecentTripsWidget::class)
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Acciones Rápidas
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="{{ route('filament.admin.resources.trips.create') }}" 
                       class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Nuevo Viaje</p>
                            <p class="text-xs text-blue-700 dark:text-blue-300">Crear viaje</p>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.resources.expenses.create') }}" 
                       class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-900 dark:text-green-100">Registrar Gasto</p>
                            <p class="text-xs text-green-700 dark:text-green-300">Nuevo gasto</p>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.resources.vehicles.index') }}" 
                       class="flex items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Estado de Flota</p>
                            <p class="text-xs text-yellow-700 dark:text-yellow-300">Ver vehículos</p>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.pages.samsara-integration-page') }}" 
                       class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-purple-900 dark:text-purple-100">Sincronización</p>
                            <p class="text-xs text-purple-700 dark:text-purple-300">Samsara API</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>