<?php

namespace App\Enums;

enum ImportOrderStatus: string implements \Filament\Support\Contracts\HasLabel, \Filament\Support\Contracts\HasColor
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case IN_TRANSIT = 'in_transit';
    case IN_CUSTOMS = 'in_customs';
    case IN_ZOFRI = 'in_zofri';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::CONFIRMED => 'Confirmado',
            self::IN_TRANSIT => 'En Tránsito',
            self::IN_CUSTOMS => 'En Aduana',
            self::IN_ZOFRI => 'En ZOFRI',
            self::RECEIVED => 'Recibido',
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
            self::IN_ZOFRI => 'warning',
            self::RECEIVED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
