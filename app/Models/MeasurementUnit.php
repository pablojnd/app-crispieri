<?php

namespace App\Models;

use App\Enums\MeasurementUnitType;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeasurementUnit extends Model
{
    use HasFactory, SoftDeletes, HasStoreTenancy;

    protected $fillable = [
        'name',
        'abbreviation',
        'type',
        'description',
        'is_base_unit',
        'conversion_factor'
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'conversion_factor' => 'decimal:4',
        'type' => MeasurementUnitType::class
    ];

    // public function products(): HasMany
    // {
    //     return $this->hasMany(Product::class);
    // }
}
