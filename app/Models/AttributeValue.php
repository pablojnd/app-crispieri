<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    protected $fillable = ['value_name', 'attribute_id'];
    public $timestamps = false;

    // public function attribute()
    // {
    //     return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    // }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute_value', 'value_id', 'product_id');
    }
}
