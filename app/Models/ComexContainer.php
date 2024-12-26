<?php

namespace App\Models;

use App\Enums\ContainerType;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexContainer extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $table = 'comex_containers';

    protected $fillable = [
        'store_id',
        'import_order_id',
        'container_number',
        'type',
        'weight',
        'cost',
        'notes',
        'shipping_line_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'cost' => 'decimal:2',
        'type' => ContainerType::class,
    ];

    protected $with = ['importOrder', 'shippingLine']; // Agregar shippingLine al eager loading

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->store_id && ($store = Store::current())) {
                $model->store_id = $store->id;
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function importOrder()
    {
        return $this->belongsTo(
            ComexImportOrder::class,
            'import_order_id'  // Asegurarse de que este es el nombre correcto de la columna
        );
    }

    public function documents()
    {
        return $this->belongsToMany(ComexDocument::class, 'comex_document_containers', 'container_id', 'document_id')
            ->withTimestamps();
    }

    public function items()
    {
        return $this->belongsToMany(ComexItem::class, 'comex_container_items', 'container_id', 'item_id')
            ->withPivot(['quantity', 'weight'])
            ->withTimestamps();
    }

    public function shippingLine()
    {
        return $this->belongsTo(ComexShippingLine::class, 'shipping_line_id');
    }
}
