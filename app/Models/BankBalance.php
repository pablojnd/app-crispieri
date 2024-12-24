<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankBalance extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $fillable = [
        'store_id',
        'bank_id',
        'balance_date',
        'balance_usd',
        'balance_clp',
        'exchange_rate',
        'notes'
    ];

    protected $casts = [
        'balance_date' => 'date',
        'balance_usd' => 'decimal:2',
        'balance_clp' => 'decimal:2',
        'exchange_rate' => 'decimal:4'
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
