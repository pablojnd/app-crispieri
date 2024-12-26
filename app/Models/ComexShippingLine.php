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
        'name',
        'contact_person',
        'phone',
        'email',
        'status',
        'notes',
        'estimated_departure',
        'actual_departure',
        'estimated_arrival',
        'actual_arrival',
    ];

    protected $casts = [
        'estimated_departure' => 'date',
        'actual_departure' => 'date',
        'estimated_arrival' => 'date',
        'actual_arrival' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function containers()
    {
        return $this->hasMany(ComexContainer::class, 'shipping_line_id');
    }
}
