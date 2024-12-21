<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'rut',
        'tax_id',
        'type',
        'active',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'website',
        'observations',
        'store_id'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function mainAddress()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('type', 'main')
            ->where('is_default', true);
    }
}
