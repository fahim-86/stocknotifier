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
        Schema::table('alerts', function (Blueprint $table) {
            // Adjust 18 and 8 to your chosen precision/scale
            $table->decimal('high_price', 8, 2)->nullable()->change();
            $table->decimal('low_price', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            // Revert to the original type (e.g. DOUBLE or FLOAT)
            $table->double('high_price')->nullable()->change();
            $table->double('low_price')->nullable()->change();
        });
    }
};
