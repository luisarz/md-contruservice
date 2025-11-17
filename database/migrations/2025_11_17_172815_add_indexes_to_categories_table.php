<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índices para optimizar búsquedas y filtros en categorías
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Índice para búsquedas por nombre (searchable en CategoryResource)
            $table->index('name', 'idx_categories_name');

            // Índice para filtros por estado activo
            $table->index('is_active', 'idx_categories_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_name');
            $table->dropIndex('idx_categories_active');
        });
    }
};
