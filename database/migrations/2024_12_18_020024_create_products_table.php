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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Hacer el slug único por tienda
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Hacer el slug único por tienda
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('measurement_unit_id')
                ->nullable()
                ->constrained('measurement_units')
                ->nullOnDelete()
                ->comment('Unidad de medida del producto');

            // Campos originales
            $table->string('product_name');
            $table->string('slug')->unique();
            $table->integer('price')->default(0);
            $table->decimal('stock', 10, 2)->default(0);
            $table->string('sku')->unique();
            $table->boolean('status')->default(true);
            $table->string('hs_code', 10)->nullable()->comment('Código arancelario');
            $table->json('image')->nullable();
            $table->text('description')->nullable();

            // Nuevos campos comerciales y logísticos
            $table->decimal('offer_price', 10, 2)->nullable()->comment('Precio de oferta');
            $table->date('offer_start_date')->nullable()->comment('Fecha de inicio de oferta');
            $table->date('offer_end_date')->nullable()->comment('Fecha de fin de oferta');

            $table->string('supplier_code')->nullable()->comment('Código de proveedor');
            $table->string('supplier_reference')->nullable()->comment('Referencia del proveedor');

            $table->string('packing_type')->nullable()->comment('Tipo de empaque');
            $table->decimal('packing_quantity', 10, 2)->nullable()->comment('Cantidad por empaque');

            $table->decimal('weight', 10, 2)->nullable()->comment('Peso del producto');
            $table->decimal('length', 10, 2)->nullable()->comment('Longitud del producto');
            $table->decimal('width', 10, 2)->nullable()->comment('Ancho del producto');
            $table->decimal('height', 10, 2)->nullable()->comment('Altura del producto');

            $table->string('barcode')->nullable()->comment('Código de barras');
            $table->string('ean_code')->nullable()->comment('Código EAN');

            $table->boolean('is_taxable')->default(false)->comment('¿Es un producto gravable?');
            $table->decimal('tax_rate', 5, 2)->nullable()->comment('Tasa de impuesto');

            $table->integer('minimum_stock')->nullable()->comment('Stock mínimo');
            $table->integer('maximum_stock')->nullable()->comment('Stock máximo');

            $table->text('additional_notes')->nullable()->comment('Notas adicionales');

            // Campos de trazabilidad
            $table->timestamps();
            $table->softDeletes();

            // Índices y restricciones únicas
            $table->index(['store_id', 'supplier_code'], 'idx_store_supplier_code');
            $table->index(['store_id', 'category_id']);
            $table->index(['store_id', 'brand_id']);
            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
