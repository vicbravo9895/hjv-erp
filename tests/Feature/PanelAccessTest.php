<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_routes_are_registered()
    {
        // Test that all panel routes are properly registered
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.admin.pages.dashboard'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.workshop.pages.dashboard'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.operator.pages.dashboard'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.accounting.pages.dashboard'));
    }

    public function test_panel_switcher_routes_are_registered()
    {
        // Test that panel switcher routes are registered for all panels
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.admin.pages.panel-switcher'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.workshop.pages.panel-switcher'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.operator.pages.panel-switcher'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.accounting.pages.panel-switcher'));
    }

    public function test_middleware_aliases_are_registered()
    {
        // Test that middleware aliases are properly registered
        $middlewareAliases = app('router')->getMiddleware();
        
        $this->assertArrayHasKey('admin.access', $middlewareAliases);
        $this->assertArrayHasKey('accounting.access', $middlewareAliases);
        $this->assertArrayHasKey('workshop.access', $middlewareAliases);
        $this->assertArrayHasKey('operator.access', $middlewareAliases);
    }

    public function test_user_role_based_panel_determination()
    {
        $operator = User::factory()->create(['role' => 'operador']);
        $admin = User::factory()->create(['role' => 'administrador']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $accountant = User::factory()->create(['role' => 'contador']);

        // Test operator access
        $this->assertTrue($operator->isOperator());
        $this->assertFalse($operator->hasAdminAccess());
        $this->assertFalse($operator->hasWorkshopAccess());
        $this->assertFalse($operator->hasAccountingAccess());

        // Test admin access
        $this->assertTrue($admin->hasAdminAccess());
        $this->assertTrue($admin->hasWorkshopAccess());
        $this->assertTrue($admin->hasAccountingAccess());

        // Test supervisor access
        $this->assertTrue($supervisor->hasAdminAccess());
        $this->assertTrue($supervisor->hasWorkshopAccess());
        $this->assertFalse($supervisor->hasAccountingAccess());

        // Test accountant access
        $this->assertFalse($accountant->hasAdminAccess());
        $this->assertFalse($accountant->hasWorkshopAccess());
        $this->assertTrue($accountant->hasAccountingAccess());
    }
}