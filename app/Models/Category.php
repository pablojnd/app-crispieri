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
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'parent_id',
        'store_id'
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

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
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

    public function recursiveProductCount(): int
    {
        $count = $this->products()
            ->when(
                Auth::user(),
                fn($query) => $query->whereBelongsTo(Filament::getTenant())
            )
            ->count();

        foreach ($this->children as $child) {
            $count += $child->recursiveProductCount();
        }

        return $count;
    }
}
