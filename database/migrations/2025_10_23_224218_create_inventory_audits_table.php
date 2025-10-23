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
        Schema::create('inventory_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id')->constrained()->onDelete('cascade');
            $table->string('change_type'); // 'product_request_received', 'product_request_reversed', 'maintenance_usage', 'manual_adjustment'
            $table->decimal('quantity_change', 10, 2); // Positive or negative
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->string('reference_type')->nullable(); // ProductRequest, MaintenanceRecord, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for querying audit history
            $table->index(['spare_part_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_audits');
    }
};
