<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Seeder específico para datos de demostración del sistema de transporte.
     * Este seeder puede ejecutarse independientemente para poblar la base de datos
     * con datos realistas para pruebas y demostraciones.
     */
    public function run(): void
    {
        $this->command->info('🚛 Iniciando población de datos de demostración...');
        
        // Verificar si ya existen datos
        if ($this->hasExistingData()) {
            if (!$this->command->confirm('Ya existen datos en la base de datos. ¿Desea continuar? Esto puede crear duplicados.')) {
                $this->command->info('❌ Operación cancelada.');
                return;
            }
        }
        
        DB::transaction(function () {
            $this->command->info('📋 Creando escalas de pago...');
            $this->call(PaymentScaleSeeder::class);
            
            $this->command->info('🚚 Creando flota de vehículos...');
            $this->call(VehicleSeeder::class);
            
            $this->command->info('🚛 Creando remolques...');
            $this->call(TrailerSeeder::class);
            
            $this->command->info('👨‍💼 Creando operadores...');
            $this->call(OperatorSeeder::class);
            
            $this->command->info('🗺️ Generando viajes...');
            $this->call(TripSeeder::class);
            
            $this->command->info('💰 Calculando costos de viajes...');
            $this->call(TripCostSeeder::class);
            
            $this->command->info('📊 Creando gastos operativos...');
            $this->call(ExpenseSeeder::class);
            
            $this->command->info('🔧 Generando registros de mantenimiento...');
            $this->call(MaintenanceSeeder::class);
            
            $this->command->info('💵 Calculando nóminas semanales...');
            $this->call(WeeklyPayrollSeeder::class);
            
            $this->command->info('🔄 Creando logs de sincronización...');
            $this->call(SamsaraSyncSeeder::class);
        });
        
        $this->displaySummary();
    }
    
    private function hasExistingData(): bool
    {
        return DB::table('vehicles')->exists() || 
               DB::table('operators')->exists() || 
               DB::table('trips')->exists();
    }
    
    private function displaySummary(): void
    {
        $this->command->info('');
        $this->command->info('✅ ¡Datos de demostración creados exitosamente!');
        $this->command->info('');
        $this->command->info('📈 Resumen de datos creados:');
        $this->command->line('   • Vehículos: ' . DB::table('vehicles')->count());
        $this->command->line('   • Remolques: ' . DB::table('trailers')->count());
        $this->command->line('   • Operadores: ' . DB::table('operators')->count());
        $this->command->line('   • Viajes: ' . DB::table('trips')->count());
        $this->command->line('   • Costos de viaje: ' . DB::table('trip_costs')->count());
        $this->command->line('   • Gastos: ' . DB::table('expenses')->count());
        $this->command->line('   • Registros de mantenimiento: ' . DB::table('maintenance_records')->count());
        $this->command->line('   • Nóminas: ' . DB::table('weekly_payrolls')->count());
        $this->command->line('   • Logs de sincronización: ' . DB::table('samsara_sync_logs')->count());
        $this->command->info('');
        $this->command->info('🎯 El sistema está listo para usar con datos realistas.');
        $this->command->info('💡 Puedes acceder al panel administrativo para explorar los datos.');
    }
}