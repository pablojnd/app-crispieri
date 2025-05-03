<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_order_id',
        'product_code',
        'zeta_code',
        'description',
        'requested_quantity',
        'confirmed_quantity',
        'delivered_quantity',
        'unit_price',
        'total_price',
        'product_data',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'confirmed_quantity' => 'decimal:2',
        'delivered_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'product_data' => 'json',
    ];

    public function order()
    {
        return $this->belongsTo(InventoryOrder::class, 'inventory_order_id');
    }
}
