<?php

namespace App\Models\Concerns;

use App\Models\Store;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait HasStoreTenancy
{
    protected static function bootHasStoreTenancy(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            // Verificar si hay un tenant activo
            $tenant = Filament::getTenant();
            if (!$tenant) {
                return; // No aplicar scope global si no hay tenant activo
            }

            $builder->where('store_id', $tenant->id);
        });

        // Asignar automáticamente el tenant ID al crear un modelo
        static::creating(function (Model $model) {
            if (!$model->store_id && ($tenant = Filament::getTenant())) {
                $model->store_id = $tenant->id;
            }
        });

        // Prevenir cambios accidentales en el tenant ID
        static::updating(function (Model $model) {
            if ($model->isDirty('store_id')) {
                throw new \LogicException('Cannot modify the store_id of a model.');
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeForCurrentStore($query)
    {
        return $query->where('store_id', auth()->user()->store_id);
    }
}
