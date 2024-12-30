<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexShippingLine extends Model
{
    use HasFactory, HasStoreTenancy;

    protected $table = 'comex_shipping_lines';

    protected $fillable = [
        'store_id',
        'import_order_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'status',
        'notes',
        // 'estimated_departure',
        // 'actual_departure',
        // 'estimated_arrival',
        // 'actual_arrival',
    ];

    // protected $casts = [
    //     'estimated_departure' => 'date',
    //     'actual_departure' => 'date',
    //     'estimated_arrival' => 'date',
    //     'actual_arrival' => 'date',
    // ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function comexShippingLineContainer()
    {
        return $this->hasMany(ComexShippingLineContainer::class, 'shipping_line_id');
    }

    public function importOrders()
    {
        return $this->belongsToMany(ComexImportOrder::class, 'comex_shipping_line_containers', 'shipping_line_id', 'import_order_id');
    }

    public function containers()
    {
        return $this->hasManyThrough(
            ComexContainer::class,
            ComexShippingLineContainer::class,
            'shipping_line_id',
            'comex_shipping_line_container_id'
        );
    }

    public function expenses()
    {
        return $this->hasMany(ComexExpense::class, 'shipping_line_id');
    }
}
