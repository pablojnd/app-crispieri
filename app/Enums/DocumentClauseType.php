<?php

namespace App\Enums;

enum DocumentClauseType: string
{
    case FOB = 'fob';
    case COST_AND_FREIGHT = 'cost_and_freight';
    case CIF = 'cif';

    public function label(): string
    {
        return match ($this) {
            self::FOB => 'FOB',
            self::COST_AND_FREIGHT => 'Cost and Freight',
            self::CIF => 'CIF',
        };
    }
}
