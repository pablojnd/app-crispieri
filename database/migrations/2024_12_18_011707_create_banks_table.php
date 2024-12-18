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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id('id')->comment('Código ISO de la moneda');
            $table->string('name')->comment('Nombre de la moneda');
            $table->string('symbol', 5)->comment('Símbolo de la moneda');
            $table->boolean('is_active')->default(true)->comment('Estado activo de la moneda');
            $table->timestamps();
        });

        Schema::create('bank_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Código único del banco');
            $table->string('bank_name')->comment('Nombre del banco');
            $table->string('code_bank_name')->virtualAs("CONCAT(code, ' - ', bank_name)")->comment('Código y nombre concatenados');
            $table->timestamps();
        });

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->comment('Referencia a la tienda/multitenancy');
            $table->foreignId('bank_code_id')->constrained('bank_codes')->comment('Referencia al código del banco');
            $table->foreignId('currency_id')->constrained('currencies')->comment('Moneda de la cuenta');
            $table->string('account_number')->comment('Número de cuenta bancaria');
            $table->enum('account_type', ['checking', 'savings', 'other'])->default('checking')->comment('Tipo de cuenta');
            $table->boolean('is_active')->default(true)->comment('Estado activo del banco');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['store_id', 'bank_code_id', 'account_number']);
        });

        Schema::create('bank_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->comment('Referencia a la tienda/multitenancy');
            $table->foreignId('bank_id')->constrained('banks')->cascadeOnDelete()->comment('Referencia a la cuenta bancaria');
            $table->date('balance_date')->comment('Fecha del registro de saldo');
            $table->decimal('balance_usd', 18, 2)->default(0.00)->comment('Saldo registrado en USD');
            $table->decimal('balance_clp', 18, 2)->default(0.00)->comment('Saldo registrado en CLP');
            // $table->decimal('reserved_balance', 18, 2)->default(0.00)->comment('Saldo reservado');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000)->comment('Tipo de cambio');
            $table->text('notes')->nullable()->comment('Notas o comentarios');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['bank_id', 'balance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
