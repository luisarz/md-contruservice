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
        Schema::table('inventory_groupeds', function (Blueprint $table) {
            // Agregar el foreign key que faltaba
            $table->foreign('inventory_grouped_id')
                  ->references('id')
                  ->on('inventories')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_groupeds', function (Blueprint $table) {
            $table->dropForeign(['inventory_grouped_id']);
        });
    }
};
