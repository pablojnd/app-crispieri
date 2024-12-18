<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'logo',
        'status',
        'is_featured',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productCount(): int
    {
        return $this->products()
            ->when(
                Auth::user(),
                fn($query) => $query->whereBelongsTo(Filament::getTenant())
            )
            ->count();
    }

    public function activeProductCount(): int
    {
        return $this->products()
            ->where('status', true)
            ->when(
                Auth::user(),
                fn($query) => $query->whereBelongsTo(Filament::getTenant())
            )
            ->count();
    }
}
