<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Model
{
    use HasFactory, HasStoreTenancy, SoftDeletes;

    protected $fillable = [
        'store_id',
        'bank_code_id',
        'currency_id',
        'account_number',
        'account_type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function bankCode()
    {
        return $this->belongsTo(BankCode::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function bankBalances()
    {
        return $this->hasMany(BankBalance::class);
    }

    public function latestBalance()
    {
        return $this->hasOne(BankBalance::class)
            ->orderByDesc('balance_date');
    }

    public static function getTotalUsdBalance(): float
    {
        $banks = static::query()
            ->where('is_active', true)
            ->with(['bankBalances' => function ($query) {
                $query->orderByDesc('balance_date')
                    ->limit(1);
            }])
            ->get();

        return $banks->sum(function ($bank) {
            return $bank->bankBalances->first()?->balance_usd ?? 0;
        });
    }
}
