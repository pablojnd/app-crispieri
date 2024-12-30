<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexShippingLineContainer extends Model
{
    use HasFactory, HasStoreTenancy;

    protected $table = 'comex_shipping_line_containers';

    protected $fillable = [
        'store_id',
        'shipping_line_id',
        'container_id',
        'estimated_departure',
        'actual_departure',
        'estimated_arrival',
        'actual_arrival',
        'status',
        'notes',
    ];

    protected $casts = [
        'estimated_departure' => 'date',
        'actual_departure' => 'date',
        'estimated_arrival' => 'date',
        'actual_arrival' => 'date',
    ];

    protected $with = ['containers']; // Agregar eager loading de contenedores

    public function shippingLine()
    {
        return $this->belongsTo(ComexShippingLine::class, 'shipping_line_id');
    }

    public function containers()
    {
        return $this->hasMany(ComexContainer::class, 'comex_shipping_line_container_id');
    }
}
