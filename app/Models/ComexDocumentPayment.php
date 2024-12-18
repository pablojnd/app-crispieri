<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComexDocumentPayment extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $table = 'comex_document_payments';

    protected $fillable = [
        'store_id',
        'document_id',
        'bank_id',
        'amount',
        'exchange_rate',
        'payment_status',
        'payment_date',
        'reference_number',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'payment_date' => 'date'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function document()
    {
        return $this->belongsTo(ComexDocument::class);
    }
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
