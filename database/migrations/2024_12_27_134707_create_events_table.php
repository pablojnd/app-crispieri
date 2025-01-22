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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade'); // Relación con el tenant
            $table->foreignId('shipping_line_container_id')->nullable()->constrained('comex_shipping_line_containers')->onDelete('cascade'); // Relación con el contenedor
            $table->string('title'); // Título del evento
            $table->text('description')->nullable(); // Descripción opcional
            $table->timestamp('start_at'); // Inicio del evento
            $table->timestamp('end_at')->nullable(); // Fin del evento (opcional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
