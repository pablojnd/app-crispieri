<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentClauseType: string implements HasLabel
{
    case FOB = 'fob';
    case COST_AND_FREIGHT = 'cost_and_freight';
    case CIF = 'cif';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FOB => 'FOB',
            self::COST_AND_FREIGHT => 'Costo y Flete',
            self::CIF => 'CIF',
        };
    }
}
