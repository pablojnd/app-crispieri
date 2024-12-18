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
        'name',
        'code_iso_2',
        'code_iso_3',
        'region',
        'currency_code',
        'currency_name',
        'phone_prefix',
        'is_active'
    ];

    protected $casts = [
        'name' => 'string',
        'code_iso_2' => 'string',
        'code_iso_3' => 'string',
        'region' => 'string',
        'currency_code' => 'string',
        'currency_name' => 'string',
        'phone_prefix' => 'string',
        'is_active' => 'boolean'
    ];

    // public function comexItems(): HasMany
    // {
    //     return $this->hasMany(ComexItem::class, 'origin_country_id');
    // }

    // public function providers(): HasMany
    // {
    //     return $this->hasMany(Provider::class, 'country_id');
    // }

    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->code_iso_3})";
    }
}
