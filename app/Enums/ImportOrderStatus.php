<?php

namespace App\Enums;

enum ImportOrderStatus: string implements \Filament\Support\Contracts\HasLabel, \Filament\Support\Contracts\HasColor
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case IN_TRANSIT = 'in_transit';
    case IN_CUSTOMS = 'in_customs';
    case GALPON = 'galpon';
    case RECEIVED = 'received';
    case FINISH = 'finish';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::CONFIRMED => 'Confirmado',
            self::IN_TRANSIT => 'En Tránsito',
            self::IN_CUSTOMS => 'En Aduana',
            self::GALPON => 'En Galpón',
            self::RECEIVED => 'Recibido',
            self::FINISH => 'Finalizado',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::CONFIRMED => 'info',
            self::IN_TRANSIT => 'warning',
            self::IN_CUSTOMS => 'warning',
            self::GALPON => 'warning',
            self::RECEIVED => 'success',
            self::FINISH => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
