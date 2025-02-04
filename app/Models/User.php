<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'latest_store_id', // Añadimos latest_store_id a fillable
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->stores;
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        // Primero intentamos obtener la última tienda usada
        if ($this->latestStore) {
            return $this->latestStore;
        }

        // Si no hay última tienda, intentamos obtener la tienda con ID 3
        $defaultStore = Store::find(3);

        // Si existe la tienda 3 y el usuario tiene acceso a ella, la establecemos como predeterminada
        if ($defaultStore && $this->stores->contains($defaultStore)) {
            $this->setDefaultStore($defaultStore);
            return $defaultStore;
        }

        // Si no se cumple lo anterior, retornamos la primera tienda del usuario
        return $this->stores()->first();
    }

    public function latestStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'latest_store_id');
    }

    public function setDefaultStore(Store $store): void
    {
        if ($this->stores->contains($store)) {
            $this->latest_store_id = $store->id;
            $this->save();
        }
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->stores()->whereKey($tenant)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
        return true;
    }
}
