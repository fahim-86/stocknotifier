<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->renameColumn('last_fetched_at', 'fetched_at');
        });
    }

    public function down()
    {
        Schema::table('table_name', function (Blueprint $table) {
            $table->renameColumn('fetched_at', 'last_fetched_at');
        });
    }
};
