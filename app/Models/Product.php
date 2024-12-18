<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function measurementUnit()
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
