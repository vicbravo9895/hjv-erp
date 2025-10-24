<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('license_plate')->nullable()->after('plate')->comment('License plate from Samsara');
            $table->string('esn')->nullable()->after('serial_number')->comment('Electronic Serial Number');
            $table->string('camera_serial')->nullable()->after('esn')->comment('Camera serial number');
            $table->string('gateway_model')->nullable()->comment('Gateway model');
            $table->string('gateway_serial')->nullable()->comment('Gateway serial');
            $table->string('vehicle_type')->nullable()->comment('Vehicle type (truck, trailer, etc)');
            $table->string('regulation_mode')->nullable()->comment('Vehicle regulation mode');
            $table->integer('gross_vehicle_weight')->nullable()->comment('Gross vehicle weight in lbs');
            $table->text('notes')->nullable()->comment('Notes about the vehicle');
            $table->json('external_ids')->nullable()->comment('External IDs (maintenance, payroll, etc)');
            $table->json('tags')->nullable()->comment('Samsara tags');
            $table->json('attributes')->nullable()->comment('Custom attributes');
            $table->json('sensor_configuration')->nullable()->comment('Sensor configuration');
            $table->string('static_assigned_driver_id')->nullable()->comment('Static assigned driver ID');
            $table->string('static_assigned_driver_name')->nullable()->comment('Static assigned driver name');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'license_plate',
                'esn',
                'camera_serial',
                'gateway_model',
                'gateway_serial',
                'vehicle_type',
                'regulation_mode',
                'gross_vehicle_weight',
                'notes',
                'external_ids',
                'tags',
                'attributes',
                'sensor_configuration',
                'static_assigned_driver_id',
                'static_assigned_driver_name',
            ]);
        });
    }
};
