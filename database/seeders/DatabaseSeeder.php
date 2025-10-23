<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario de prueba
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Ejecutar todos los seeders en orden
        $this->call([
            // Usuarios y roles
            UserRoleSeeder::class,
            PaymentScaleSeeder::class,
            
            // Entidades principales
            VehicleSeeder::class,
            TrailerSeeder::class,
            OperatorSeeder::class,
            
            // Operaciones
            TripSeeder::class,
            TripCostSeeder::class,
            
            // Finanzas y gastos
            ExpenseSeeder::class,
            FinancialManagementSeeder::class,
            
            // Mantenimiento
            MaintenanceSeeder::class,
            
            // Nóminas
            WeeklyPayrollSeeder::class,
            
            // Sincronización
            SamsaraSyncSeeder::class,
        ]);
        
        $this->command->info('✅ Base de datos poblada exitosamente con datos de demostración');
        $this->command->info('📊 Datos creados:');
        $this->command->info('   • Vehículos: 15 unidades');
        $this->command->info('   • Remolques: 20 unidades');
        $this->command->info('   • Operadores: 25 personas');
        $this->command->info('   • Viajes: ~150 registros');
        $this->command->info('   • Costos de viaje: ~600 registros');
        $this->command->info('   • Gastos generales: 300 registros');
        $this->command->info('   • Registros de mantenimiento: ~200 registros');
        $this->command->info('   • Nóminas semanales: ~300 registros');
    }
}
