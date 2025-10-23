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
        Schema::create('trip_costs', function (Blueprint $table) {
            $table->id();
            
            // Trip relationship
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade')->comment('Associated trip');
            
            // Cost information
            $table->enum('cost_type', ['diesel', 'tolls', 'maneuvers', 'other'])->comment('Type of cost');
            $table->decimal('amount', 10, 2)->comment('Total cost amount');
            $table->text('description')->nullable()->comment('Cost description');
            $table->string('receipt_url')->nullable()->comment('Receipt file URL');
            $table->string('location')->nullable()->comment('Location where cost was incurred');
            
            // Detailed cost breakdown (for diesel and other quantifiable costs)
            $table->decimal('quantity', 8, 3)->nullable()->comment('Quantity (liters for diesel, etc.)');
            $table->decimal('unit_price', 8, 3)->nullable()->comment('Price per unit');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['trip_id']);
            $table->index(['cost_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_costs');
    }
};
