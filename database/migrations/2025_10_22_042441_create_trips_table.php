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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            
            // Trip information
            $table->string('origin')->comment('Trip origin location');
            $table->string('destination')->comment('Trip destination location');
            $table->date('start_date')->comment('Trip start date');
            $table->date('end_date')->nullable()->comment('Trip end date');
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->timestamp('completed_at')->nullable()->comment('Trip completion timestamp');
            
            // Foreign key relationships
            $table->foreignId('truck_id')->constrained('vehicles')->comment('Assigned truck/vehicle');
            $table->foreignId('trailer_id')->nullable()->constrained('trailers')->comment('Assigned trailer');
            $table->foreignId('operator_id')->constrained('operators')->comment('Assigned operator');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status']);
            $table->index(['start_date']);
            $table->index(['truck_id']);
            $table->index(['trailer_id']);
            $table->index(['operator_id']);
            $table->index(['completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
