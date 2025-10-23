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
        Schema::create('payment_scales', function (Blueprint $table) {
            $table->id();
            $table->integer('trips_count');
            $table->decimal('payment_amount', 10, 2);
            $table->timestamps();
            
            $table->unique('trips_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_scales');
    }
};
