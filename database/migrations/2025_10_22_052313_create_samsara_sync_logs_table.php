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
        Schema::create('samsara_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type'); // vehicles, trailers, drivers
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('synced_records')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->json('params')->nullable(); // Parameters used for the sync
            $table->json('additional_data')->nullable(); // Additional data from sync
            $table->json('error_details')->nullable(); // Detailed error information
            $table->timestamps();

            // Indexes for better performance
            $table->index(['sync_type', 'started_at']);
            $table->index(['status', 'started_at']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('samsara_sync_logs');
    }
};
