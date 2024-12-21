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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');    // Crea addressable_type y addressable_id
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('type')->default('main');  // main, billing, shipping, etc
            $table->string('street_address')->nullable();
            $table->string('street_number')->nullable();
            $table->string('apartment')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('additional_info')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para mejor rendimiento
            $table->index('store_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
