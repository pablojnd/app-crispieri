<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'logo',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'website',
        'is_active',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user', 'store_id', 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
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

    public function banks(): HasMany
    {
        return $this->hasMany(Bank::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function measurementUnits(): HasMany
    {
        return $this->hasMany(MeasurementUnit::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function comexImportOrders(): HasMany
    {
        return $this->hasMany(ComexImportOrder::class);
    }

    public function comexDocuments(): HasMany
    {
        return $this->hasMany(ComexDocument::class);
    }

    public function comexContainers(): HasMany
    {
        return $this->hasMany(ComexContainer::class);
    }

    public function comexItems(): HasMany
    {
        return $this->hasMany(ComexItem::class);
    }
}
