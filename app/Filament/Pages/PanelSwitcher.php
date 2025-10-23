<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class PanelSwitcher extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static string $view = 'filament.pages.panel-switcher';
    protected static ?string $navigationLabel = 'Cambiar Panel';
    protected static ?string $title = 'Cambiar Panel';
    protected static ?int $navigationSort = 999;

    public function getAvailablePanels(): array
    {
        $user = Auth::user();
        $availablePanels = [];

        // Check each panel and user access
        if ($user->hasAdminAccess() && Filament::hasPanel('admin')) {
            $availablePanels['admin'] = [
                'name' => 'Administración',
                'description' => 'Panel completo de administración del sistema',
                'url' => Filament::getPanel('admin')->getUrl(),
                'color' => 'primary',
                'icon' => 'heroicon-o-cog-6-tooth',
            ];
        }

        if ($user->hasAccountingAccess() && Filament::hasPanel('accounting')) {
            $availablePanels['accounting'] = [
                'name' => 'Contabilidad',
                'description' => 'Gestión financiera y contable',
                'url' => Filament::getPanel('accounting')->getUrl(),
                'color' => 'success',
                'icon' => 'heroicon-o-calculator',
            ];
        }

        if ($user->hasWorkshopAccess() && Filament::hasPanel('workshop')) {
            $availablePanels['workshop'] = [
                'name' => 'Taller',
                'description' => 'Gestión de mantenimiento e inventario',
                'url' => Filament::getPanel('workshop')->getUrl(),
                'color' => 'warning',
                'icon' => 'heroicon-o-wrench-screwdriver',
            ];
        }

        if ($user->isOperator() && Filament::hasPanel('operator')) {
            $availablePanels['operator'] = [
                'name' => 'Operador',
                'description' => 'Gestión de viajes y gastos',
                'url' => Filament::getPanel('operator')->getUrl(),
                'color' => 'info',
                'icon' => 'heroicon-o-truck',
            ];
        }

        return $availablePanels;
    }

    public function getCurrentPanel(): ?string
    {
        return Filament::getCurrentPanel()?->getId();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Only show if user has access to multiple panels
        $accessCount = 0;
        if ($user->hasAdminAccess()) $accessCount++;
        if ($user->hasAccountingAccess()) $accessCount++;
        if ($user->hasWorkshopAccess()) $accessCount++;
        if ($user->isOperator()) $accessCount++;

        return $accessCount > 1;
    }
}