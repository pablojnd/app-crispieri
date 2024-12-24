<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'country_name',
        'currency_id',
        'region',
        'is_active',
    ];

    protected $casts = [
        'country_name' => 'string',
        'region' => 'string',
        'is_active' => 'boolean'
    ];

    public function currency(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    public function comexImportOrders(): HasMany
    {
        return $this->hasMany(ComexImportOrder::class);
    }
}
