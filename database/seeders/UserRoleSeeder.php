<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create Super Admin
        User::firstOrCreate(
            ['email' => 'superadmin@flota.com'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        // Create Administrator
        User::firstOrCreate(
            ['email' => 'admin@flota.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => 'administrador',
            ]
        );

        // Create Supervisor
        User::firstOrCreate(
            ['email' => 'supervisor@flota.com'],
            [
                'name' => 'Supervisor',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
            ]
        );

        // Create Accountant
        User::firstOrCreate(
            ['email' => 'contador@flota.com'],
            [
                'name' => 'Contador',
                'password' => Hash::make('password'),
                'role' => 'contador',
            ]
        );

        // Create Operator
        User::firstOrCreate(
            ['email' => 'operador@flota.com'],
            [
                'name' => 'Operador',
                'password' => Hash::make('password'),
                'role' => 'operador',
            ]
        );
    }
}