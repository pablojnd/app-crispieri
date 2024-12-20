<?php

namespace App\Models;

use App\Enums\TransportType;
use App\Enums\ImportOrderStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use App\Models\{Store, Provider, Country};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexImportOrder extends Model
{
    use HasFactory, HasStoreTenancy,  SoftDeletes;

    protected $table = 'comex_import_orders';

    protected $fillable = [
        'store_id',
        'provider_id',
        'origin_country_id',
        'reference_number',
        'external_reference',
        'sve_registration_number',
        'type',
        'status',
        'order_date',
        'estimated_departure',
        'actual_departure',
        'estimated_arrival',
        'actual_arrival'
    ];

    protected $casts = [
        'type' => TransportType::class,
        'status' => ImportOrderStatus::class,
        'order_date' => 'date',
        'estimated_departure' => 'date',
        'actual_departure' => 'date',
        'estimated_arrival' => 'date',
        'actual_arrival' => 'date'
    ];

    // Método para generar el número de referencia
    public static function generateReferenceNumber()
    {
        $initialNumber = 2505;
        $totalOrders = self::count();
        return $initialNumber + $totalOrders + 1;
    }

    // Relaciones
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
    public function originCountry()
    {
        return $this->belongsTo(Country::class, 'origin_country_id');
    }
    public function documents()
    {
        return $this->hasMany(ComexDocument::class, 'import_order_id');
    }
    public function containers()
    {
        return $this->hasMany(ComexContainer::class, 'import_order_id');
    }
    public function items()
    {
        return $this->hasMany(ComexItem::class, 'import_order_id');
    }
    public function expenses()
    {
        return $this->hasMany(ComexExpense::class, 'import_order_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\ComexImportOrderFactory::new();
    }
}
