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
        'name',
        'account_number',
        'currency_id',
        'notes'
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
