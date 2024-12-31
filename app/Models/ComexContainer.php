<?php

namespace App\Models;

use App\Enums\ContainerType;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexContainer extends Model
{
    use HasFactory, HasStoreTenancy;

    protected $table = 'comex_containers';

    protected $fillable = [
        'store_id',
        'import_order_id',
        'comex_shipping_line_container_id',
        'container_number',
        'type',
        'weight',
        'cost',
        'notes',
    ];

    protected $casts = [

        'weight' => 'decimal:2',
        'cost' => 'decimal:2',
        'type' => ContainerType::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($container) {
            // Eliminar registros relacionados si es necesario
            $container->items()->detach();
            $container->documents()->detach();
            $container->expenses()->detach();
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

    public function comexShippingLineContainer()
    {
        return $this->belongsTo(ComexShippingLineContainer::class, 'comex_shipping_line_container_id');
    }

    public function expenses()
    {
        return $this->belongsToMany(ComexExpense::class, 'comex_expense_containers', 'container_id', 'expense_id')
            ->withTimestamps();
    }
}
