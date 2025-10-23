<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filters Form -->
        <x-filament::section>
            <x-slot name="heading">
                Generar Reporte de Nómina
            </x-slot>
            
            <form wire:submit="generateReport">
                {{ $this->form }}
                
                <div class="mt-6">
                    <x-filament::button type="submit" size="lg">
                        <x-heroicon-o-document-chart-bar class="w-5 h-5 mr-2" />
                        Generar Reporte
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <!-- Report Results -->
        @if($this->getReportData())
            @php $reportData = $this->getReportData(); @endphp
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-filament::section class="text-center">
                    <div class="text-2xl font-bold text-primary-600">
                        ${{ number_format($reportData['summary']['total_payments'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600">Total Pagado</div>
                </x-filament::section>
                
                <x-filament::section class="text-center">
                    <div class="text-2xl font-bold text-success-600">
                        {{ $reportData['summary']['total_trips'] }}
                    </div>
                    <div class="text-sm text-gray-600">Total Viajes</div>
                </x-filament::section>
                
                <x-filament::section class="text-center">
                    <div class="text-2xl font-bold text-info-600">
                        {{ $reportData['summary']['operator_count'] }}
                    </div>
                    <div class="text-sm text-gray-600">Operadores</div>
                </x-filament::section>
                
                <x-filament::section class="text-center">
                    <div class="text-2xl font-bold text-warning-600">
                        ${{ number_format($reportData['summary']['average_payment_per_operator'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600">Promedio por Operador</div>
                </x-filament::section>
            </div>

            <!-- Detailed Report by Operator -->
            <x-filament::section>
                <x-slot name="heading">
                    Detalle por Operador
                </x-slot>
                
                <div class="space-y-4">
                    @foreach($reportData['payrolls_by_operator'] as $operatorData)
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-semibold">{{ $operatorData['operator']->name }}</h3>
                                <div class="text-right">
                                    <div class="text-xl font-bold text-primary-600">
                                        ${{ number_format($operatorData['total_payment'], 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">Total del Período</div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="font-medium">Viajes:</span> {{ $operatorData['total_trips'] }}
                                </div>
                                <div>
                                    <span class="font-medium">Semanas:</span> {{ $operatorData['weeks_count'] }}
                                </div>
                                <div>
                                    <span class="font-medium">Pago Base:</span> ${{ number_format($operatorData['total_base_payment'], 2) }}
                                </div>
                                <div>
                                    <span class="font-medium">Ajustes:</span> 
                                    <span class="{{ $operatorData['total_adjustments'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                        ${{ number_format($operatorData['total_adjustments'], 2) }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Weekly Breakdown -->
                            <div class="mt-4">
                                <h4 class="font-medium mb-2">Desglose Semanal:</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Semana</th>
                                                <th class="px-3 py-2 text-center">Viajes</th>
                                                <th class="px-3 py-2 text-right">Pago Base</th>
                                                <th class="px-3 py-2 text-right">Ajustes</th>
                                                <th class="px-3 py-2 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($operatorData['payrolls'] as $payroll)
                                                <tr class="border-t">
                                                    <td class="px-3 py-2">{{ $payroll->week_range }}</td>
                                                    <td class="px-3 py-2 text-center">{{ $payroll->trips_count }}</td>
                                                    <td class="px-3 py-2 text-right">${{ number_format($payroll->base_payment, 2) }}</td>
                                                    <td class="px-3 py-2 text-right {{ $payroll->adjustments >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                                        ${{ number_format($payroll->adjustments, 2) }}
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-medium">${{ number_format($payroll->total_payment, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <!-- Summary Table -->
            @if($reportData['summary']['total_adjustments'] != 0)
                <x-filament::section>
                    <x-slot name="heading">
                        Resumen de Ajustes
                    </x-slot>
                    
                    <div class="text-center p-4">
                        <div class="text-lg">
                            <span class="font-medium">Total de Ajustes:</span>
                            <span class="{{ $reportData['summary']['total_adjustments'] >= 0 ? 'text-success-600' : 'text-danger-600' }} font-bold">
                                ${{ number_format($reportData['summary']['total_adjustments'], 2) }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            Pago Base Total: ${{ number_format($reportData['summary']['total_base_payments'], 2) }}
                        </div>
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>