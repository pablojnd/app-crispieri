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

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($shippingLineContainer) {
            // Al eliminar una naviera, primero eliminamos sus contenedores
            $shippingLineContainer->containers->each(function ($container) {
                $container->items()->detach();
                $container->documents()->detach();
                $container->expenses()->detach();
                $container->delete();
            });
        });
    }

    public function shippingLine()
    {
        return $this->belongsTo(ComexShippingLine::class, 'shipping_line_id');
    }

    public function containers()
    {
        return $this->hasMany(ComexContainer::class, 'comex_shipping_line_container_id');
    }
}
