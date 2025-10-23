<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;
use App\Models\Provider;
use App\Models\CostCenter;
use App\Models\Expense;

class FinancialManagementSeeder extends Seeder
{
    public function run(): void
    {
        // Create Expense Categories
        $categories = [
            ['name' => 'Renta de Patio', 'description' => 'Gastos relacionados con el alquiler de espacios de patio'],
            ['name' => 'Combustible', 'description' => 'Gastos de diésel y combustible para vehículos'],
            ['name' => 'Seguros', 'description' => 'Pólizas de seguro para vehículos y operaciones'],
            ['name' => 'Mantenimiento', 'description' => 'Reparaciones y mantenimiento de vehículos'],
            ['name' => 'Otros', 'description' => 'Gastos diversos no clasificados en otras categorías'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::create($category);
        }

        // Create Providers
        $providers = [
            [
                'name' => 'Gasolinera El Triunfo',
                'contact_name' => 'Juan Pérez',
                'phone' => '555-0123',
                'email' => 'contacto@eltriunfo.com',
                'address' => 'Carretera Nacional Km 45',
                'service_type' => 'Combustible'
            ],
            [
                'name' => 'Seguros Monterrey',
                'contact_name' => 'María González',
                'phone' => '555-0456',
                'email' => 'maria@segurosmonterrey.com',
                'address' => 'Av. Constitución 123, Monterrey',
                'service_type' => 'Seguros'
            ],
            [
                'name' => 'Taller Mecánico Los Pinos',
                'contact_name' => 'Carlos Rodríguez',
                'phone' => '555-0789',
                'email' => 'taller@lospinos.com',
                'address' => 'Zona Industrial Norte 456',
                'service_type' => 'Mantenimiento'
            ],
        ];

        foreach ($providers as $provider) {
            Provider::create($provider);
        }

        // Create Cost Centers
        $costCenters = [
            ['name' => 'Operaciones Norte', 'description' => 'Centro de costo para operaciones en la región norte', 'budget' => 50000.00],
            ['name' => 'Operaciones Sur', 'description' => 'Centro de costo para operaciones en la región sur', 'budget' => 45000.00],
            ['name' => 'Administración', 'description' => 'Gastos administrativos generales', 'budget' => 25000.00],
            ['name' => 'Mantenimiento', 'description' => 'Centro de costo para mantenimiento de flota', 'budget' => 30000.00],
        ];

        foreach ($costCenters as $costCenter) {
            CostCenter::create($costCenter);
        }

        // Create some sample expenses
        $expenses = [
            [
                'date' => now()->subDays(5),
                'amount' => 1500.00,
                'description' => 'Carga de combustible para tractocamión 001',
                'category_id' => ExpenseCategory::where('name', 'Combustible')->first()->id,
                'provider_id' => Provider::where('name', 'Gasolinera El Triunfo')->first()->id,
                'cost_center_id' => CostCenter::where('name', 'Operaciones Norte')->first()->id,
            ],
            [
                'date' => now()->subDays(3),
                'amount' => 8500.00,
                'description' => 'Póliza de seguro mensual para flota',
                'category_id' => ExpenseCategory::where('name', 'Seguros')->first()->id,
                'provider_id' => Provider::where('name', 'Seguros Monterrey')->first()->id,
                'cost_center_id' => CostCenter::where('name', 'Administración')->first()->id,
            ],
            [
                'date' => now()->subDays(1),
                'amount' => 2300.00,
                'description' => 'Reparación de frenos tractocamión 002',
                'category_id' => ExpenseCategory::where('name', 'Mantenimiento')->first()->id,
                'provider_id' => Provider::where('name', 'Taller Mecánico Los Pinos')->first()->id,
                'cost_center_id' => CostCenter::where('name', 'Mantenimiento')->first()->id,
            ],
        ];

        foreach ($expenses as $expense) {
            Expense::create($expense);
        }
    }
}