<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filters Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filtros de Reporte</h3>
            {{ $this->form }}
        </div>

        <!-- Summary Cards -->
        @if($this->report_type === 'expenses')
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <x-heroicon-o-currency-dollar class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Gastos</p>
                            <p class="text-2xl font-semibold text-gray-900">
                                ${{ number_format($this->getTableQuery()->sum('amount'), 2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <x-heroicon-o-document-text class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Cantidad</p>
                            <p class="text-2xl font-semibold text-gray-900">
                                {{ $this->getTableQuery()->count() }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <x-heroicon-o-calculator class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Promedio</p>
                            <p class="text-2xl font-semibold text-gray-900">
                                ${{ number_format($this->getTableQuery()->avg('amount') ?? 0, 2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <x-heroicon-o-chart-bar class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Máximo</p>
                            <p class="text-2xl font-semibold text-gray-900">
                                ${{ number_format($this->getTableQuery()->max('amount') ?? 0, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Data Table -->
        <div class="bg-white rounded-lg shadow">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>