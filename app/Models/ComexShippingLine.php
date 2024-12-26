<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexShippingLine extends Model
{
    use HasFactory, HasStoreTenancy;

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
        'estimated_departure' => 'datetime',
        'actual_departure' => 'datetime',
        'estimated_arrival' => 'datetime',
        'actual_arrival' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
