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
        Schema::table('maintenance_records', function (Blueprint $table) {
            // Remove the cost column as it will now be calculated automatically
            // based on product usage records
            $table->dropColumn('cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            // Re-add the cost column if we need to rollback
            $table->decimal('cost', 10, 2)->default(0)->after('date');
        });
    }
};
