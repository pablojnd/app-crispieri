<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $fillable = [
        'store_id',
        'type',
        'street_address',
        'street_number',
        'apartment',
        'city',
        'state',
        'country',
        'postal_code',
        'is_default',
        'additional_info'
    ];

    public function addressable()
    {
        return $this->morphTo();
    }
}
