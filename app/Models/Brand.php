<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Filament\Facades\Filament;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes, HasSlug;

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

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->usingSeparator('-')
            ->usingLanguage('es');
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {
    //         if (empty($model->slug)) {
    //             $model->generateSlug();
    //         }
    //     });
    // }

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
