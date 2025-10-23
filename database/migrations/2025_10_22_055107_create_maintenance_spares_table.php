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
        Schema::create('maintenance_spares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('spare_part_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_used');
            $table->decimal('cost', 10, 2);
            $table->timestamps();

            $table->unique(['maintenance_record_id', 'spare_part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_spares');
    }
};
