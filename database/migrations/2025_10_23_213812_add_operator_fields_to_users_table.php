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
        Schema::table('users', function (Blueprint $table) {
            // Add operator-specific fields
            $table->string('license_number')->nullable()->unique()->comment('Driver license number');
            $table->string('phone')->nullable()->comment('Contact phone number');
            $table->date('hire_date')->nullable()->comment('Date of hire');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->comment('Operator status');
            
            // Add indexes for performance
            $table->index(['status']);
            $table->index(['license_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status']);
            $table->dropIndex(['license_number']);
            
            // Drop columns
            $table->dropColumn(['license_number', 'phone', 'hire_date', 'status']);
        });
    }
};
