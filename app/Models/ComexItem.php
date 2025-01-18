<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexItem extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $table = 'comex_items';

    protected $fillable = [
        'store_id',
        'import_order_id',
        'product_id',
        'quantity',
        'total_price',
        'cif_unit',
        'package_quality'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'total_price' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'cif_unit' => 'decimal:4'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function importOrder()
    {
        return $this->belongsTo(ComexImportOrder::class, 'import_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function documents()
    {
        return $this->belongsToMany(ComexDocument::class, 'comex_document_items', 'item_id', 'document_id')
            ->withPivot(['quantity', 'cif_amount'])
            ->withTimestamps();
    }

    public function containers()
    {
        return $this->belongsToMany(ComexContainer::class, 'comex_container_items', 'item_id', 'container_id')
            ->withPivot(['quantity', 'weight'])
            ->withTimestamps();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->store_id) {
                $model->store_id = $model->importOrder->store_id;
            }
        });
    }
}
