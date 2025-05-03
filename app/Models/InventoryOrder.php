<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasStoreTenancy;

class InventoryOrder extends Model
{
    use HasFactory, SoftDeletes, HasStoreTenancy;

    protected $fillable = [
        'store_id',
        'order_number',
        'reference',
        'client_name',
        'client_email',
        'client_phone',
        'notes',
        'status',
        'created_by',
        'last_modified_by',
        'confirmed_at',
        'completed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relaciones
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryOrderItem::class);
    }

    // Generar un número de orden único
    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $lastOrder = self::where('order_number', 'like', "{$prefix}-{$date}%")
            ->orderBy('order_number', 'desc')
            ->first();

        $number = 1;
        if ($lastOrder) {
            $parts = explode('-', $lastOrder->order_number);
            $number = (int)end($parts) + 1;
        }

        return sprintf("%s-%s-%04d", $prefix, $date, $number);
    }

    // Calcular total del pedido
    public function getOrderTotal()
    {
        return $this->items->sum('total_price');
    }
}
