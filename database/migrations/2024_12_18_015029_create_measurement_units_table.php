<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('measurement_units', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('store_id')
            //     ->constrained('stores')
            //     ->cascadeOnDelete();
            $table->string('name')->comment('Nombre de la unidad de medida');
            $table->string('code')->comment('Código de la unidad de medida');
            $table->string('abbreviation')->comment('Abreviatura');
            $table->text('description')->nullable();
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('conversion_factor', 10, 4)->nullable()->comment('Factor de conversión a unidad base');

            $table->timestamps();
            $table->softDeletes();

            // Cambiar las restricciones únicas para incluir store_id
            $table->unique(['name', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_units');
    }
};
