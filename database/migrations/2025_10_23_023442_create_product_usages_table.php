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
        Schema::create('product_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id')->constrained()->onDelete('cascade');
            $table->foreignId('maintenance_record_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_used', 10, 2);
            $table->date('date_used');
            $table->text('notes')->nullable();
            $table->foreignId('used_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['spare_part_id', 'maintenance_record_id']);
            $table->index('date_used');
            $table->index('used_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_usages');
    }
};
