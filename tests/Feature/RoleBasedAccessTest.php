<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_has_all_access()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasAdminAccess());
        $this->assertTrue($user->hasAccountingAccess());
    }

    public function test_administrator_has_admin_and_accounting_access()
    {
        $user = User::factory()->create(['role' => 'administrador']);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isAdministrator());
        $this->assertTrue($user->hasAdminAccess());
        $this->assertTrue($user->hasAccountingAccess());
    }

    public function test_supervisor_has_admin_access_only()
    {
        $user = User::factory()->create(['role' => 'supervisor']);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isSupervisor());
        $this->assertTrue($user->hasAdminAccess());
        $this->assertFalse($user->hasAccountingAccess());
    }

    public function test_accountant_has_accounting_access_only()
    {
        $user = User::factory()->create(['role' => 'contador']);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isAccountant());
        $this->assertFalse($user->hasAdminAccess());
        $this->assertTrue($user->hasAccountingAccess());
    }

    public function test_operator_has_no_admin_access()
    {
        $user = User::factory()->create(['role' => 'operador']);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isOperator());
        $this->assertFalse($user->hasAdminAccess());
        $this->assertFalse($user->hasAccountingAccess());
        $this->assertFalse($user->hasWorkshopAccess());
    }

    public function test_workshop_access_permissions()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'administrador']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $accountant = User::factory()->create(['role' => 'contador']);
        $operator = User::factory()->create(['role' => 'operador']);

        // Users with workshop access
        $this->assertTrue($superAdmin->hasWorkshopAccess());
        $this->assertTrue($admin->hasWorkshopAccess());
        $this->assertTrue($supervisor->hasWorkshopAccess());

        // Users without workshop access
        $this->assertFalse($accountant->hasWorkshopAccess());
        $this->assertFalse($operator->hasWorkshopAccess());
    }

    public function test_has_any_role_method()
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $operator = User::factory()->create(['role' => 'operador']);

        $this->assertTrue($admin->hasAnyRole(['administrador', 'supervisor']));
        $this->assertTrue($supervisor->hasAnyRole(['administrador', 'supervisor']));
        $this->assertFalse($operator->hasAnyRole(['administrador', 'supervisor']));
    }
}