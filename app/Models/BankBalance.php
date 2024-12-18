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
        'balance_date',
        'exchange_rate',
        'balance',
        'balance_usd',
        'balance_clp',
        'bank_id',
        'store_id',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
