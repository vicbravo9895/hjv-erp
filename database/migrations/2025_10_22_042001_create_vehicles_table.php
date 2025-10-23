<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            
            // Basic vehicle information
            $table->string('external_id')->nullable()->unique()->comment('Samsara vehicle ID');
            $table->string('vin')->nullable()->unique()->comment('Vehicle Identification Number');
            $table->string('serial_number')->nullable()->comment('Vehicle serial number');
            $table->string('name')->comment('Vehicle name/identifier');
            $table->string('unit_number')->nullable()->comment('Economic/unit number');
            $table->string('plate')->nullable()->comment('License plate');
            $table->string('make')->nullable()->comment('Vehicle manufacturer');
            $table->string('model')->nullable()->comment('Vehicle model');
            $table->integer('year')->nullable()->comment('Vehicle year');
            $table->enum('status', ['available', 'in_trip', 'maintenance', 'out_of_service'])->default('available');
            
            // Location fields
            $table->decimal('last_lat', 10, 8)->nullable()->comment('Last known latitude');
            $table->decimal('last_lng', 11, 8)->nullable()->comment('Last known longitude');
            $table->string('formatted_location')->nullable()->comment('Human readable location');
            $table->timestamp('last_location_at')->nullable()->comment('Timestamp of last location update');
            
            // Telemetry fields
            $table->decimal('last_odometer_km', 10, 2)->nullable()->comment('Last odometer reading in kilometers');
            $table->decimal('last_fuel_percent', 5, 2)->nullable()->comment('Last fuel percentage');
            $table->string('last_engine_state')->nullable()->comment('Last engine state (on/off/idle)');
            $table->decimal('last_speed_mph', 6, 2)->nullable()->comment('Last speed in mph');
            
            // Driver fields
            $table->string('current_driver_external_id')->nullable()->comment('Samsara driver ID');
            $table->string('current_driver_name')->nullable()->comment('Current driver name');
            
            // Synchronization fields
            $table->timestamp('synced_at')->nullable()->comment('Last sync with Samsara');
            $table->json('raw_snapshot')->nullable()->comment('Raw Samsara data snapshot');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status']);
            $table->index(['external_id']);
            $table->index(['unit_number']);
            $table->index(['synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
