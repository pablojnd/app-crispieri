<?php

namespace App\Models;

use App\Enums\TransportType;
use Filament\Facades\Filament;
use App\Enums\ImportOrderStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\Builder;
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
        'type' => \App\Enums\TransportType::class,
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

    public function shippingLines()
    {
        return $this->hasMany(ComexShippingLine::class, 'import_order_id');
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

    // protected static function booted(): void
    // {
    //     static::addGlobalScope('store', function (Builder $query) {
    //         if (auth()->hasUser()) {
    //             $query->where('store_id', auth()->user()->store_id);
    //         }
    //     });
    // }

    public static function getActiveOrdersCount()
    {
        return self::query()
            ->whereNotIn('status', [
                ImportOrderStatus::CANCELLED,
                ImportOrderStatus::RECEIVED
            ])
            ->where('store_id', Filament::getTenant()->id)
            ->count();
    }

    public static function getTotalExpenses()
    {
        return self::query()
            ->where('store_id', Filament::getTenant()->id)
            ->whereNotIn('status', [
                ImportOrderStatus::CANCELLED,
                ImportOrderStatus::RECEIVED
            ])
            ->withSum('expenses', 'expense_amount')
            ->get()
            ->sum('expenses_sum_expense_amount') ?? 0;
    }
}
