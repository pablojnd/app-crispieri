<?php

namespace App\Enums;

enum DocumentStatus: string implements \Filament\Support\Contracts\HasLabel, \Filament\Support\Contracts\HasColor
{
    case IN_TRANSIT = 'in_transit';
    case RECEIVED = 'received';
    case FINISH = 'finish';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::IN_TRANSIT => 'En Tránsito',
            self::RECEIVED => 'Recibido',
            self::FINISH => 'Finalizado',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::IN_TRANSIT => 'warning',
            self::RECEIVED => 'success',
            self::FINISH => 'success',
        };
    }
}
