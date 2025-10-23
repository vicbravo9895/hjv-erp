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
        Schema::create('travel_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('users')->onDelete('cascade');
            $table->enum('expense_type', ['fuel', 'tolls', 'food', 'accommodation', 'other']);
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->decimal('fuel_liters', 8, 2)->nullable();
            $table->decimal('fuel_price_per_liter', 6, 3)->nullable();
            $table->integer('odometer_reading')->nullable();
            $table->enum('status', ['pending', 'approved', 'reimbursed'])->default('pending');
            $table->timestamps();

            $table->index(['trip_id', 'operator_id']);
            $table->index(['expense_type', 'status']);
            $table->index('date');
            $table->index('operator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_expenses');
    }
};
