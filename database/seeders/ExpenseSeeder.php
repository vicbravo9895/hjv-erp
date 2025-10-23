<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Provider;
use App\Models\CostCenter;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        // Primero crear categorías de gastos
        $categories = [
            ['name' => 'Combustible', 'description' => 'Gastos de combustible y lubricantes'],
            ['name' => 'Mantenimiento', 'description' => 'Reparaciones y mantenimiento de vehículos'],
            ['name' => 'Seguros', 'description' => 'Pólizas de seguro de vehículos y carga'],
            ['name' => 'Peajes', 'description' => 'Pagos de casetas y peajes'],
            ['name' => 'Oficina', 'description' => 'Gastos administrativos y de oficina'],
            ['name' => 'Personal', 'description' => 'Gastos relacionados con el personal'],
            ['name' => 'Tecnología', 'description' => 'Software, hardware y comunicaciones'],
            ['name' => 'Legal', 'description' => 'Servicios legales y trámites'],
        ];
        
        foreach ($categories as $category) {
            ExpenseCategory::create($category);
        }
        
        // Crear proveedores
        $providers = [
            ['name' => 'Pemex', 'contact_name' => 'Juan Pérez', 'email' => 'contacto@pemex.com', 'phone' => '+52 55 1234 5678', 'service_type' => 'Combustible'],
            ['name' => 'Shell México', 'contact_name' => 'María González', 'email' => 'ventas@shell.mx', 'phone' => '+52 33 2345 6789', 'service_type' => 'Combustible'],
            ['name' => 'Talleres Hernández', 'contact_name' => 'Carlos Hernández', 'email' => 'info@talleres-hernandez.com', 'phone' => '+52 81 3456 7890', 'service_type' => 'Mantenimiento'],
            ['name' => 'Seguros Monterrey', 'contact_name' => 'Ana López', 'email' => 'seguros@monterrey.com', 'phone' => '+52 81 4567 8901', 'service_type' => 'Seguros'],
            ['name' => 'Llantas y Servicios SA', 'contact_name' => 'Roberto Martínez', 'email' => 'ventas@llantas.com', 'phone' => '+52 55 5678 9012', 'service_type' => 'Refacciones'],
            ['name' => 'Refacciones del Norte', 'contact_name' => 'Luis Rodríguez', 'email' => 'pedidos@refaccionesnorte.mx', 'phone' => '+52 81 6789 0123', 'service_type' => 'Refacciones'],
            ['name' => 'Oficina Total', 'contact_name' => 'Patricia Sánchez', 'email' => 'ventas@oficinatotal.com', 'phone' => '+52 55 7890 1234', 'service_type' => 'Suministros'],
            ['name' => 'TechTrans Solutions', 'contact_name' => 'Miguel Torres', 'email' => 'soporte@techtrans.mx', 'phone' => '+52 33 8901 2345', 'service_type' => 'Tecnología'],
        ];
        
        foreach ($providers as $provider) {
            Provider::create($provider);
        }
        
        // Crear centros de costo
        $costCenters = [
            ['name' => 'Flota Principal', 'description' => 'Vehículos de carga principal'],
            ['name' => 'Administración', 'description' => 'Gastos administrativos generales'],
            ['name' => 'Mantenimiento', 'description' => 'Taller y servicios de mantenimiento'],
            ['name' => 'Operaciones', 'description' => 'Gastos operativos directos'],
            ['name' => 'Tecnología', 'description' => 'Sistemas y tecnología'],
        ];
        
        foreach ($costCenters as $costCenter) {
            CostCenter::create($costCenter);
        }
        
        // Obtener IDs para relaciones
        $categoryIds = ExpenseCategory::pluck('id')->toArray();
        $providerIds = Provider::pluck('id')->toArray();
        $costCenterIds = CostCenter::pluck('id')->toArray();
        
        // Crear gastos de los últimos 6 meses
        for ($i = 0; $i < 300; $i++) {
            $date = $faker->dateTimeBetween('-6 months', 'now');
            $categoryId = $faker->randomElement($categoryIds);
            $category = ExpenseCategory::find($categoryId);
            
            // Generar descripción y monto basado en la categoría
            $expenseData = $this->generateExpenseData($faker, $category->name);
            
            Expense::create([
                'date' => $date,
                'amount' => $expenseData['amount'],
                'description' => $expenseData['description'],
                'category_id' => $categoryId,
                'provider_id' => $faker->randomElement($providerIds),
                'cost_center_id' => $faker->randomElement($costCenterIds),
                'receipt_url' => $faker->boolean(40) ? $faker->url() : null, // 40% tienen recibo
            ]);
        }
    }
    
    private function generateExpenseData($faker, $categoryName): array
    {
        switch ($categoryName) {
            case 'Combustible':
                return [
                    'amount' => $faker->randomFloat(2, 5000, 50000),
                    'description' => $faker->randomElement([
                        'Carga de diésel flota principal',
                        'Combustible vehículos administrativos',
                        'Lubricantes y aditivos',
                        'Combustible generadores'
                    ])
                ];
                
            case 'Mantenimiento':
                return [
                    'amount' => $faker->randomFloat(2, 1000, 25000),
                    'description' => $faker->randomElement([
                        'Servicio preventivo unidad #' . $faker->numberBetween(1, 15),
                        'Reparación de motor',
                        'Cambio de llantas',
                        'Reparación de frenos',
                        'Servicio de transmisión',
                        'Reparación eléctrica'
                    ])
                ];
                
            case 'Seguros':
                return [
                    'amount' => $faker->randomFloat(2, 8000, 35000),
                    'description' => $faker->randomElement([
                        'Prima mensual seguro de flota',
                        'Seguro de carga',
                        'Seguro de responsabilidad civil',
                        'Deducible por siniestro'
                    ])
                ];
                
            case 'Peajes':
                return [
                    'amount' => $faker->randomFloat(2, 200, 3000),
                    'description' => $faker->randomElement([
                        'Peajes ruta México-Guadalajara',
                        'Casetas autopista Monterrey',
                        'Peajes diversos rutas nacionales',
                        'TAG prepago casetas'
                    ])
                ];
                
            case 'Oficina':
                return [
                    'amount' => $faker->randomFloat(2, 500, 8000),
                    'description' => $faker->randomElement([
                        'Material de oficina',
                        'Servicios de limpieza',
                        'Renta de oficina',
                        'Servicios públicos',
                        'Papelería y suministros'
                    ])
                ];
                
            case 'Personal':
                return [
                    'amount' => $faker->randomFloat(2, 2000, 15000),
                    'description' => $faker->randomElement([
                        'Capacitación operadores',
                        'Exámenes médicos',
                        'Uniformes y equipo de seguridad',
                        'Bonos y incentivos',
                        'Gastos de viaje personal'
                    ])
                ];
                
            case 'Tecnología':
                return [
                    'amount' => $faker->randomFloat(2, 1000, 20000),
                    'description' => $faker->randomElement([
                        'Licencias de software',
                        'Mantenimiento sistemas GPS',
                        'Servicios de comunicación',
                        'Hardware y equipos',
                        'Servicios en la nube'
                    ])
                ];
                
            case 'Legal':
                return [
                    'amount' => $faker->randomFloat(2, 1500, 12000),
                    'description' => $faker->randomElement([
                        'Servicios legales corporativos',
                        'Trámites y permisos',
                        'Renovación de licencias',
                        'Asesoría fiscal',
                        'Gestión documental'
                    ])
                ];
                
            default:
                return [
                    'amount' => $faker->randomFloat(2, 500, 5000),
                    'description' => 'Gasto general'
                ];
        }
    }
}