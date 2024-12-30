<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\{DocumentType, DocumentClauseType};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany};

class ComexDocument extends Model
{
    use HasFactory, SoftDeletes, HasStoreTenancy;

    protected $table = 'comex_documents';

    protected $fillable = [
        'store_id',
        'import_order_id',
        'document_number',
        'document_type',
        'document_clause',
        'document_date',
        'fob_total',
        'freight_total',
        'insurance_total',
        'currency_code',
        'notes',
        'total_paid',
        'pending_amount',
        'factor',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
        'document_clause' => DocumentClauseType::class,
        'document_date' => 'date',
        'fob_total' => 'decimal:4',
        'freight_total' => 'decimal:4',
        'insurance_total' => 'decimal:4',
        'total_paid' => 'decimal:4',
        'pending_amount' => 'decimal:4',
        'factor' => 'decimal:9',
    ];

    // protected $withCount = ['payments', 'items'];

    // protected $with = ['items.product'];

    // protected $appends = [
    //     'cif_total',
    //     'total_paid',
    //     'pending_amount',
    //     'items_sum_total_price',
    //     'items_tooltip'
    // ];

    // Relaciones
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function importOrder(): BelongsTo
    {
        return $this->belongsTo(ComexImportOrder::class, 'import_order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ComexDocumentPayment::class, 'document_id');
    }

    public function containers(): BelongsToMany
    {
        return $this->belongsToMany(ComexContainer::class, 'comex_document_containers', 'document_id', 'container_id')
            ->withTimestamps();
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ComexItem::class, 'comex_document_items', 'document_id', 'item_id')
            ->withPivot(['quantity', 'cif_amount'])
            ->withTimestamps();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ComexExpense::class, 'document_id');
    }

    public function getItemsTooltipAttribute(): string
    {
        if (!$this->items->count()) {
            return 'Sin items';
        }

        return $this->items->map(function ($item) {
            return sprintf(
                '%s (%s x $%s)',
                $item->product?->product_name ?? 'Sin producto',
                number_format($item->pivot?->quantity ?? $item->quantity ?? 0, 2),
                number_format($item->pivot?->unit_price ?? $item->unit_price ?? 0, 2)
            );
        })->join("\n");
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()
            ->where('payment_status', 'completed')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('document_id')
            ->value('total') ?? 0.00;
    }

    public function getPendingAmountAttribute(): float
    {
        return max(0, $this->cif_total - $this->getTotalPaidAttribute());
    }


    public function getCifTotalAttribute(): float
    {
        return $this->fob_total + $this->freight_total + $this->insurance_total;
    }


    public static function getAvailablePaymentStatuses(): array
    {
        return [
            PaymentStatus::PENDING,
            PaymentStatus::PARTIALLY_PAID,
            PaymentStatus::COMPLETED,
        ];
    }

    public function sumAmount(): float
    {
        return $this->items->sum(
            fn($item) => $item->pivot->quantity * $item->unit_price
        );
    }

    public function getFactorAttribute(): float
    {
        $cif = $this->cif_total;
        $totalItemsPrice = $this->items()->sum('total_price');

        return $totalItemsPrice > 0 ? round($cif / $totalItemsPrice, 9) : 0;
    }

    public function updateFactor(): void
    {
        $totalItemsPrice = $this->getTotalItemsPriceAttribute();
        $factor = $this->calculateFactor($totalItemsPrice);

        $this->update(['factor' => $factor]);

        foreach ($this->items()->get() as $item) {
            $cifAmount = $item->total_price * $factor;
            $cifUnit = $item->quantity > 0 ? ($cifAmount / $item->quantity) : 0;

            $this->items()->updateExistingPivot($item->id, [
                'cif_amount' => $cifAmount,
            ]);

            $item->update([
                'cif_unit' => $cifUnit,
            ]);
        }

        $this->refresh();
    }

    protected function calculateFactor(float $totalItemsPrice): float
    {
        return $totalItemsPrice > 0 ? round($this->cif_total / $totalItemsPrice, 9) : 0;
    }

    public function getItemsSumTotalPriceAttribute(): float
    {
        return $this->getTotalItemsPriceAttribute();
    }

    public function getTotalItemsPriceAttribute(): float
    {
        return $this->items()
            ->selectRaw('COALESCE(SUM(total_price), 0) as total')
            ->value('total') ?? 0;
    }
}
