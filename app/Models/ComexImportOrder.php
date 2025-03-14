<?php

namespace App\Models;

use App\Enums\PaymentStatus;
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
        $initialNumber = 2492;
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

    public function comexShippingLineContainers()
    {
        return $this->hasMany(ComexShippingLineContainer::class, 'import_order_id');
    }

    public function items()
    {
        return $this->hasMany(ComexItem::class, 'import_order_id');
    }
    public function expenses()
    {
        return $this->hasMany(ComexExpense::class, 'import_order_id');
    }

    // Agregar esta relación
    public function shippingLines()
    {
        return $this->belongsToMany(ComexShippingLine::class, 'comex_shipping_line_containers', 'import_order_id', 'shipping_line_id')
            ->using(ComexShippingLineContainer::class)
            ->withPivot(['estimated_departure', 'actual_departure', 'estimated_arrival', 'actual_arrival'])
            ->wherePivot('comex_shipping_line_containers.store_id', $this->store_id);
    }

    public function getTotalCifAndExpenses()
    {
        $totalCif = $this->documents()->sum('cif_total') ?? 0;

        $totalExpenses = $this->expenses()
            ->whereIn('payment_status', ['completed', 'partially_paid'])
            ->sum('expense_amount') ?? 0;

        return number_format($totalCif + $totalExpenses, 4, ',', '.');
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
                ImportOrderStatus::RECEIVED,
                ImportOrderStatus::FINISH
            ])
            ->where('store_id', Filament::getTenant()->id)
            ->count();
    }

    public static function getTotalExpenses()
    {
        $query = self::query()
            ->where('store_id', Filament::getTenant()->id)
            ->whereNotIn('status', [
                ImportOrderStatus::CANCELLED,
                ImportOrderStatus::RECEIVED,
                ImportOrderStatus::FINISH
            ]);

        // Suma de gastos pendientes y parcialmente pagados
        $expensesSum = clone $query;
        $totalExpenses = $expensesSum->withSum(['expenses' => function ($query) {
            $query->whereIn('payment_status', [
                PaymentStatus::PENDING->value,
                PaymentStatus::PARTIALLY_PAID->value
            ]);
        }], 'expense_amount')
            ->get()
            ->sum('expenses_sum_expense_amount') ?? 0;

        // Calcular pending_amount de documentos
        $documentsSum = clone $query;
        $documents = $documentsSum->with(['documents' => function ($query) {
            $query->withSum('items', 'total_price')
                ->withSum('payments', 'amount');
        }])->get();

        $totalPendingAmount = $documents->sum(function ($order) {
            return $order->documents->sum(function ($document) {
                $cifTotal = $document->cif_total;
                $totalPaid = $document->payments_sum_amount ?? 0;
                return max(0, $cifTotal - $totalPaid);
            });
        });

        return $totalExpenses + $totalPendingAmount;
    }
}
