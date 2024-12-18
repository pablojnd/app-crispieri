<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankCode extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'bank_name'];

    public function banks()
    {
        return $this->hasMany(Bank::class);
    }
}
