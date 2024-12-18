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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            // Información básica
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('rut')->unique();
            $table->string('tax_id')->nullable();

            // Tipo y estado
            $table->enum('type', ['manufacturer', 'distributor', 'wholesaler', 'retailer'])
                ->default('distributor');
            $table->boolean('active')->default(true);
            // Dirección
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            // Información adicional
            $table->string('website')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('store_id');
            $table->index(['store_id', 'name']);
            $table->index(['store_id', 'rut']);
            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
