<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'code_adu', 'symbol', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function banks()
    {
        return $this->hasMany(Bank::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
