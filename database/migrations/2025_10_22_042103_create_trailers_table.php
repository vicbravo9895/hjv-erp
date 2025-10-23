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
        Schema::create('trailers', function (Blueprint $table) {
            $table->id();
            
            // Basic trailer information
            $table->string('external_id')->nullable()->unique()->comment('Samsara trailer ID');
            $table->string('name')->comment('Trailer name/identifier');
            $table->string('asset_number')->nullable()->comment('Asset/economic number');
            $table->string('plate')->nullable()->comment('License plate');
            $table->string('type')->nullable()->comment('Trailer type');
            $table->enum('status', ['available', 'in_trip', 'maintenance', 'out_of_service'])->default('available');
            
            // Location fields
            $table->decimal('last_lat', 10, 8)->nullable()->comment('Last known latitude');
            $table->decimal('last_lng', 11, 8)->nullable()->comment('Last known longitude');
            $table->string('formatted_location')->nullable()->comment('Human readable location');
            $table->timestamp('last_location_at')->nullable()->comment('Timestamp of last location update');
            
            // Telemetry fields
            $table->decimal('last_speed_mph', 6, 2)->nullable()->comment('Last speed in mph');
            $table->decimal('last_heading_degrees', 6, 2)->nullable()->comment('Last heading in degrees');
            
            // Synchronization fields
            $table->timestamp('synced_at')->nullable()->comment('Last sync with Samsara');
            $table->json('raw_snapshot')->nullable()->comment('Raw Samsara data snapshot');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status']);
            $table->index(['external_id']);
            $table->index(['asset_number']);
            $table->index(['synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trailers');
    }
};
