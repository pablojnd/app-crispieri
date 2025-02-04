<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentClauseType: string implements HasLabel
{
    case FOB = 'fob';
    case CIF = 'cif';
    case COST_AND_FREIGHT = 'cost_and_freight';
    case COST_AND_INSURANCE = 'cost_and_insurance';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FOB => 'FOB',
            self::CIF => 'CIF',
            self::COST_AND_FREIGHT => 'Costo y Flete',
            self::COST_AND_INSURANCE => 'Costo y Seguro',
        };
    }
}
