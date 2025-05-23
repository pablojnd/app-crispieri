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
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('rut')->unique()->nullable();
            $table->enum('type', ['manufacturer', 'distributor', 'wholesaler', 'retailer'])
                ->default('distributor');
            $table->boolean('active')->default(true);
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
