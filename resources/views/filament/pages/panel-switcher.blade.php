<x-filament-panels::page>
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach($this->getAvailablePanels() as $panelId => $panel)
            <div class="relative">
                <a href="{{ $panel['url'] }}" 
                   class="block p-6 bg-white rounded-lg border border-gray-200 shadow-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 transition-colors">
                    
                    @if($this->getCurrentPanel() === $panelId)
                        <div class="absolute top-2 right-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                Actual
                            </span>
                        </div>
                    @endif
                    
                    <div class="flex items-center mb-3">
                        <x-filament::icon 
                            :icon="$panel['icon']" 
                            class="w-8 h-8 text-{{ $panel['color'] }}-500 mr-3"
                        />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $panel['name'] }}
                        </h3>
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $panel['description'] }}
                    </p>
                    
                    @if($this->getCurrentPanel() !== $panelId)
                        <div class="mt-4">
                            <span class="inline-flex items-center text-sm font-medium text-{{ $panel['color'] }}-600 hover:text-{{ $panel['color'] }}-500">
                                Cambiar a este panel
                                <x-filament::icon icon="heroicon-m-arrow-right" class="w-4 h-4 ml-1" />
                            </span>
                        </div>
                    @endif
                </a>
            </div>
        @endforeach
    </div>
    
    @if(empty($this->getAvailablePanels()))
        <div class="text-center py-12">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Sin acceso a paneles
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                No tienes acceso a ningún panel del sistema.
            </p>
        </div>
    @endif
</x-filament-panels::page>