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
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            
            // Basic operator information
            $table->string('name')->comment('Operator full name');
            $table->string('license_number')->unique()->comment('Driver license number');
            $table->string('phone')->nullable()->comment('Contact phone number');
            $table->string('email')->nullable()->unique()->comment('Email address');
            $table->date('hire_date')->nullable()->comment('Date of hire');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status']);
            $table->index(['license_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
