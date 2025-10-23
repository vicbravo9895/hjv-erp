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
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('vehicle_type'); // 'vehicle' or 'trailer'
            $table->string('maintenance_type');
            $table->date('date');
            $table->decimal('cost', 10, 2);
            $table->text('description');
            $table->unsignedBigInteger('mechanic_id')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'vehicle_type']);
            $table->index('date');
            $table->index('maintenance_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
