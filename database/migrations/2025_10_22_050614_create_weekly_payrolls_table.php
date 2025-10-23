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
        Schema::create('weekly_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->onDelete('cascade');
            $table->date('week_start');
            $table->date('week_end');
            $table->integer('trips_count');
            $table->decimal('base_payment', 10, 2);
            $table->decimal('adjustments', 10, 2)->default(0);
            $table->decimal('total_payment', 10, 2);
            $table->timestamps();
            
            $table->unique(['operator_id', 'week_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_payrolls');
    }
};
