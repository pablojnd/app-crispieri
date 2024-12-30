<?php

namespace App\Models;

use BinaryCats\Sku\HasSku;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use BinaryCats\Sku\Concerns\SkuOptions;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory, HasStoreTenancy, HasSku, HasSlug, SoftDeletes;

    protected $fillable = [
        'product_name',
        'slug',
        'description',
        'price',
        'stock',
        'sku',
        'status',
        'hs_code',
        'image',
        'offer_price',
        'offer_start_date',
        'offer_end_date',
        'supplier_code',
        'supplier_reference',
        'packing_type',
        'packing_quantity',
        'weight',
        'length',
        'width',
        'height',
        'barcode',
        'ean_code',
        'is_taxable',
        'tax_rate',
        'minimum_stock',
        'maximum_stock',
        'additional_notes',
        'category_id',
        'brand_id',
        'measurement_unit_id',
        'store_id'
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'decimal:2',
        'status' => 'boolean',
        'image' => 'array',
        'is_taxable' => 'boolean',
        'offer_start_date' => 'date',
        'offer_end_date' => 'date',
    ];

    public function skuOptions(): SkuOptions
    {
        return SkuOptions::make()
            ->from(['label', 'product_name'])
            ->target('sku')
            ->using('_')
            ->forceUnique(true)
            ->generateOnCreate(true)
            ->refreshOnUpdate(false);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('product_name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->usingSeparator('-')
            ->usingLanguage('es');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values')
            ->using(ProductAttributeValue::class)
            ->withTimestamps();
    }

    // Nueva relación para el repeater
    public function product_attribute_values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    protected function productName(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => strtoupper($value),
        );
    }
}
