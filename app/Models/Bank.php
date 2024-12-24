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

    // Relación con el último saldo del banco
    public function latestBalance()
    {
        return $this->hasOne(BankBalance::class)->latestOfMany('balance_date');
    }

    public static function getTotalUsdBalance(): float
    {
        return static::query()
            ->where('is_active', true)
            ->whereHas('currency', fn($q) => $q->where('code', 'USD'))
            ->withAggregate('latestBalance', 'balance_usd')
            ->whereBelongsTo(Filament::getTenant())
            ->get()
            ->sum('latest_balance_balance_usd') ?? 0;
    }

    public static function getAvailableBanksForSelect()
    {
        return static::query()
            ->when(
                Filament::getTenant(),
                fn($query) => $query->where('store_id', Filament::getTenant()->id)
            )
            ->get()
            ->mapWithKeys(function ($bank) {
                return [$bank->id => $bank->account_number];
            })
            ->toArray();
        $label = sprintf(



            '%s - %s',
            $bank->bankCode->name ?? 'N/A',
            $bank->account_number
        );
        return [$bank->id => $label];
    }
}
