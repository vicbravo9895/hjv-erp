<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SeedDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:seed {--fresh : Drop all tables and run migrations before seeding} {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poblar la base de datos con datos de demostración realistas para el sistema de transporte';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚛 Sistema de Gestión de Transporte - Datos de Demostración');
        $this->newLine();

        // Verificar si se debe hacer fresh migration
        if ($this->option('fresh')) {
            if (!$this->option('force') && !$this->confirm('¿Está seguro de que desea eliminar todos los datos existentes?')) {
                $this->error('❌ Operación cancelada.');
                return 1;
            }

            $this->info('🗑️  Eliminando tablas existentes...');
            Artisan::call('migrate:fresh');
            $this->info('✅ Base de datos reiniciada.');
        }

        // Verificar si ya existen datos
        if (!$this->option('fresh') && $this->hasExistingData()) {
            if (!$this->option('force') && !$this->confirm('Ya existen datos en la base de datos. ¿Desea continuar? Esto puede crear duplicados.')) {
                $this->error('❌ Operación cancelada.');
                return 1;
            }
        }

        $this->info('📊 Poblando base de datos con datos de demostración...');
        $this->newLine();

        // Ejecutar el seeder de demostración
        $exitCode = Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder']);

        if ($exitCode === 0) {
            $this->newLine();
            $this->info('🎉 ¡Datos de demostración creados exitosamente!');
            $this->displayDataSummary();
            $this->displayUsageInstructions();
        } else {
            $this->error('❌ Error al crear los datos de demostración.');
            return 1;
        }

        return 0;
    }

    /**
     * Check if there's existing data in the database
     */
    private function hasExistingData(): bool
    {
        return DB::table('vehicles')->exists() || 
               DB::table('operators')->exists() || 
               DB::table('trips')->exists();
    }

    /**
     * Display summary of created data
     */
    private function displayDataSummary(): void
    {
        $this->newLine();
        $this->info('📈 Resumen de datos creados:');
        
        $data = [
            ['Módulo', 'Registros', 'Descripción'],
            ['Vehículos', DB::table('vehicles')->count(), 'Flota de camiones con telemetría'],
            ['Remolques', DB::table('trailers')->count(), 'Remolques de diferentes tipos'],
            ['Operadores', DB::table('operators')->count(), 'Conductores con licencias'],
            ['Viajes', DB::table('trips')->count(), 'Rutas y asignaciones'],
            ['Costos de viaje', DB::table('trip_costs')->count(), 'Combustible, peajes, maniobras'],
            ['Gastos', DB::table('expenses')->count(), 'Gastos operativos categorizados'],
            ['Mantenimiento', DB::table('maintenance_records')->count(), 'Servicios y reparaciones'],
            ['Nóminas', DB::table('weekly_payrolls')->count(), 'Pagos semanales'],
            ['Logs de sync', DB::table('samsara_sync_logs')->count(), 'Sincronización con Samsara'],
        ];

        $this->table($data[0], array_slice($data, 1));
    }

    /**
     * Display usage instructions
     */
    private function displayUsageInstructions(): void
    {
        $this->newLine();
        $this->info('💡 Instrucciones de uso:');
        $this->line('   • Accede al panel administrativo para explorar los datos');
        $this->line('   • Los datos cubren los últimos 3-12 meses según el módulo');
        $this->line('   • Incluye datos tanto exitosos como con errores para pruebas completas');
        $this->newLine();
        
        $this->info('🔄 Comandos útiles:');
        $this->line('   php artisan demo:seed --fresh    # Reiniciar y poblar');
        $this->line('   php artisan demo:seed --force     # Poblar sin confirmaciones');
        $this->line('   php artisan migrate:fresh         # Solo reiniciar BD');
        $this->newLine();
        
        $this->info('🎯 El sistema está listo para usar con datos realistas.');
    }
}
