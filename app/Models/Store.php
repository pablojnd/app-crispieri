<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Store extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'email',
        'logo',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'website',
        'is_active',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user', 'store_id', 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
