<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores');
            $table->string('order_number')->unique();
            $table->string('reference')->nullable();
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'pending', 'confirmed', 'modified', 'cancelled', 'completed'])->default('draft');
            $table->string('created_by')->nullable();
            $table->string('last_modified_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_order_id')->constrained('inventory_orders')->cascadeOnDelete();
            $table->string('product_code'); // Código del artículo
            $table->string('zeta_code')->nullable(); // Código zeta
            $table->text('description');
            $table->decimal('requested_quantity', 10, 2);
            $table->decimal('confirmed_quantity', 10, 2)->nullable();
            $table->decimal('delivered_quantity', 10, 2)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->json('product_data')->nullable(); // Guardar datos adicionales del producto
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_order_items');
        Schema::dropIfExists('inventory_orders');
    }
};
