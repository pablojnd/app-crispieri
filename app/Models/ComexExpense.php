<?php

namespace App\Models;

use App\Enums\ExpenseType;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexExpense extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $table = 'comex_expenses';

    protected $fillable = [
        'store_id',
        'import_order_id',
        'currency_id',
        'expense_date',
        'expense_type',
        'expense_quantity',
        'expense_amount',
        'notes'
    ];

    protected $casts = [
        'expense_type' => ExpenseType::class,
        'expense_date' => 'date',
        'expense_quantity' => 'decimal:2',
        'expense_amount' => 'decimal:4'
    ];

    protected $with = ['currency'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function importOrder()
    {
        return $this->belongsTo(
            ComexImportOrder::class,
            'import_order_id' // Especificar el nombre correcto de la clave foránea
        );
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
