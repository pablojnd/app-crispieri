<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'code',
        'decimal_places',
        'is_active'
    ];

    public function getFormattedNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    public function banks()
    {
        return $this->hasMany(Bank::class);
    }
}
