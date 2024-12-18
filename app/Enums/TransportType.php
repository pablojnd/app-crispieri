<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TransportType: string implements HasLabel
{
    case AIR = 'air';
    case SEA = 'sea';
    case LAND = 'land';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AIR => 'Aéreo',
            self::SEA => 'Marítimo',
            self::LAND => 'Terrestre',
        };
    }
}
