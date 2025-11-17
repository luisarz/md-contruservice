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
        Schema::create('contingencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id'); // Sucursal
            $table->foreign('warehouse_id')->references('id')->on('branches');
            $table->string('uuid_hacienda');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->unsignedBigInteger('contingency_types_id'); // Tipo de contingencia
            $table->foreign('contingency_types_id')->references('id')->on('contingency_types');
            $table->string('continvengy_motivation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contingencies');
    }
};
