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
        // Tabla comex_import_orders (órdenes de importación)
        Schema::create('comex_import_orders', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete()
                ->comment('Referencia a la tienda/multitenancy');
            $table->foreignId('provider_id')
                ->constrained('providers')
                ->cascadeOnDelete()
                ->comment('Proveedor asociado a la orden');
            $table->foreignId('origin_country_id')
                ->constrained('countries')
                ->cascadeOnDelete()
                ->comment('País de origen de la importación');
            $table->string('reference_number')->unique()->comment('Número interno de referencia de la orden');
            $table->string('external_reference')->nullable()->comment('Referencia externa proporcionada por el proveedor');
            $table->string('sve_registration_number')->nullable()->comment('Número de registro en SVE');
            $table->enum('type', ['air', 'sea', 'land'])->default('sea')->comment('Tipo de transporte');
            $table->enum('status', ['draft', 'confirmed', 'in_transit', 'in_customs', 'in_zofri', 'received', 'cancelled'])
                ->default('draft')
                ->comment('Estado de la orden');
            $table->date('order_date')->comment('Fecha de creación de la orden');
            $table->timestamps();
            $table->softDeletes();

            // Eliminar la restricción única compuesta ya que reference_number ya es único globalmente
            // $table->unique(['store_id', 'reference_number']);
            $table->index(['store_id']);
        });

        // Tabla comex_documents (documentos de importación)
        Schema::create('comex_documents', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete()
                ->comment('Tienda asociada al documento');
            $table->foreignId('import_order_id')
                ->constrained('comex_import_orders')
                ->cascadeOnDelete()
                ->comment('Referencia a la orden de importación');
            $table->string('document_number')->comment('Número del documento');
            $table->enum('document_type', [
                'invoice',
                'packing_list',
                'bl',
                'insurance',
                'certificate',
                'other'
            ])->comment('Tipo de documento');
            $table->enum('document_clause', [
                'fob',
                'cost_and_freight',
                'cif'
            ])->nullable()->comment('Tipo de cláusula del documento');
            $table->date('document_date')->comment('Fecha del documento');
            $table->decimal('fob_total', 15, 4)->default(0.00)->comment('Total FOB del documento');
            $table->decimal('freight_total', 15, 4)->default(0.00)->comment('Total de flete del documento');
            $table->decimal('insurance_total', 15, 4)->default(0.00)->comment('Total de seguro del documento');
            $table->decimal('cif_total', 15, 4)->storedAs('fob_total + freight_total + insurance_total')
                ->comment('Total CIF calculado (FOB + Flete + Seguro)');
            $table->decimal('factor', 15, 9)->default(0)->comment('Factor calculado (CIF Total/suma de precios)');
            $table->decimal('total_paid', 15, 4)->default(0)
                ->comment('Monto total pagado');
            $table->decimal('pending_amount', 15, 4)->default(0)->comment('Monto pendiente de pago');
            $table->string('currency_code', 3)->default('USD')->comment('Moneda del documento');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            // $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid')->comment('Estado del pago (gestionado por el modelo)');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['import_order_id', 'document_number'], 'comex_doc_unique');
            $table->index(['import_order_id', 'document_type']);
            $table->index('store_id');
            $table->index('document_date');
        });

        // Tabla para los pagos de documentos de importación
        Schema::create('comex_document_payments', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('comex_documents')->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained('banks');

            $table->decimal('amount', 12, 2);
            $table->decimal('exchange_rate', 8, 2)->default(1.0000);
            $table->enum('payment_status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['document_id', 'payment_status']);
        });

        // Tabla para navieras
        Schema::create('comex_shipping_lines', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained('comex_import_orders')->cascadeOnDelete()->comment('Orden de importación asociada al contenedor');
            $table->string('name')->comment('Nombre de la naviera');
            $table->string('contact_person')->nullable()->comment('Persona de contacto');
            $table->string('phone')->nullable()->comment('Teléfono de contacto');
            $table->string('email')->nullable()->comment('Email de contacto');
            $table->string('status')->default('active')->comment('Estado de la naviera');
            $table->date('estimated_departure')->nullable()->comment('Fecha estimada de salida');
            $table->date('actual_departure')->nullable()->comment('Fecha real de salida');
            $table->date('estimated_arrival')->nullable()->comment('Fecha estimada de llegada');
            $table->date('actual_arrival')->nullable()->comment('Fecha real de llegada');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->timestamps();

            $table->index('store_id');
            $table->index('status');
        });

        // Tabla comex_containers (contenedores de importación)
        Schema::create('comex_containers', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete()->comment('Tienda asociada al contenedor');
            $table->foreignId('shipping_line_id')->nullable()->constrained('comex_shipping_lines')->nullOnDelete();
            $table->foreignId('import_order_id')->nullable()->constrained('comex_import_orders')->cascadeOnDelete()->comment('Orden de importación asociada al contenedor');
            $table->string('container_number')->unique()->comment('Número del contenedor');
            $table->enum('type', ['20GP', '40GP', '40HC', 'LCL', 'REEFER', 'OPEN_TOP'])->comment('Tipo de contenedor');
            $table->decimal('weight', 10, 2)->default(0.00)->comment('Peso total del contenedor en KG');
            $table->decimal('cost', 15, 2)->default(0.00)->comment('Costo del contenedor');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla pivote para la relación muchos a muchos entre documentos y contenedores
        Schema::create('comex_document_containers', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('document_id')->constrained('comex_documents')->cascadeOnDelete();
            $table->foreignId('container_id')->constrained('comex_containers')->cascadeOnDelete();
            // $table->primary(['document_id', 'container_id']);
            $table->timestamps();
        });

        // Modificar la tabla comex_items para incluir import_order_id
        Schema::create('comex_items', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete()->comment('Tienda asociada al ítem');
            $table->foreignId('import_order_id')->constrained('comex_import_orders')->cascadeOnDelete()->comment('Orden de importación asociada');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete()->comment('Producto asociado al ítem de importación');
            $table->string('package_quality')->default(1)->comment('Calidad de bultos');
            $table->decimal('quantity', 12, 2)->default(1)->comment('Cantidad del ítem');
            $table->decimal('total_price', 15, 4)->default(0)->comment('Precio total del ítem');
            $table->decimal('unit_price', 15, 4)->storedAs('CASE WHEN quantity = 0 THEN 0 ELSE total_price / quantity END')->comment('Precio unitario calculado');
            $table->decimal('cif_unit', 15, 4)->nullable()->comment('CIF * factor ');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'import_order_id']);
            $table->index('product_id');
            $table->index('store_id');
        });

        // Modificar la tabla pivote comex_document_items
        Schema::create('comex_document_items', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('document_id')->constrained('comex_documents')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('comex_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->nullable()->comment('Cantidad específica para el documento');
            $table->decimal('cif_amount', 15, 4)->nullable()->comment('Monto CIF asignado');
            $table->timestamps();

            $table->unique(['document_id', 'item_id']);
        });

        // Modificar la tabla pivote comex_container_items
        Schema::create('comex_container_items', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('container_id')->constrained('comex_containers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('comex_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->nullable()->comment('Cantidad específica para el contenedor');
            $table->decimal('weight', 10, 2)->nullable()->comment('Peso específico en KG');
            $table->timestamps();
            // Índices y restricciones mejoradas
            $table->unique(['container_id', 'item_id']);
            $table->index(['item_id']);
            $table->index(['container_id']);
        });

        // Tabla para gastos asociados a la importación
        Schema::create('comex_expenses', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained('comex_import_orders')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->comment('Moneda del gasto');
            $table->date('expense_date')->comment('Fecha del gasto');
            $table->enum('expense_type', [
                'gate_in',
                'thc',
                'manifest_opening',
                'guarantee',
                'liability_letter',
                'bl_issuance',
                'demurrage',
                'container_movement',
                'cranes',
                'unloading',
                'freight',
                'other'
            ])->comment('Tipo de gasto');
            $table->decimal('expense_quantity', 8, 2)->nullable()->comment('Cantidad del gasto');
            $table->decimal('expense_amount', 15, 4)->default(0)->comment('Monto del gasto');
            $table->string('payment_status', 50)->default('pending')->comment('Estado del pago');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['import_order_id', 'expense_type']);
            $table->index('store_id');
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comex_expenses');
        Schema::dropIfExists('comex_item_containers');
        Schema::dropIfExists('comex_document_items');
        Schema::dropIfExists('comex_items');
        Schema::dropIfExists('comex_document_containers');
        Schema::dropIfExists('comex_containers');
        Schema::dropIfExists('comex_shipping_lines');
        Schema::dropIfExists('comex_document_payments');
        Schema::dropIfExists('comex_documents');
        Schema::dropIfExists('comex_import_orders');
    }
};
