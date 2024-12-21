<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;

enum ExpenseType: string implements HasLabel, HasColor, HasIcon
{
    case GATE_IN = 'gate_in';
    case THC = 'thc';
    case MANIFEST_OPENING = 'manifest_opening';
    case GUARANTEE = 'guarantee';
    case LIABILITY_LETTER = 'liability_letter';
    case BL_ISSUANCE = 'bl_issuance';
    case DEMURRAGE = 'demurrage';
    case CONTAINER_MOVEMENT = 'container_movement';
    case CRANES = 'cranes';
    case UNLOADING = 'unloading';
    case FREIGHT = 'freight';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::GATE_IN => 'Entrada',
            self::THC => 'THC',
            self::MANIFEST_OPENING => 'Apertura de Manifiesto',
            self::GUARANTEE => 'Garantía',
            self::LIABILITY_LETTER => 'Carta de Responsabilidad',
            self::BL_ISSUANCE => 'Emisión de BL',
            self::DEMURRAGE => 'Demora',
            self::CONTAINER_MOVEMENT => 'Movimiento de Contenedor',
            self::CRANES => 'Grúas',
            self::UNLOADING => 'Descarga',
            self::FREIGHT => 'Flete',
            self::OTHER => 'Otros',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::GATE_IN => 'success',
            self::THC => 'warning',
            self::MANIFEST_OPENING => 'info',
            self::GUARANTEE => 'danger',
            self::LIABILITY_LETTER => 'primary',
            self::BL_ISSUANCE => 'success',
            self::DEMURRAGE => 'danger',
            self::CONTAINER_MOVEMENT => 'warning',
            self::CRANES => 'info',
            self::UNLOADING => 'primary',
            self::FREIGHT => 'gray',
            self::OTHER => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::GATE_IN => 'heroicon-o-arrow-right-circle',
            self::THC => 'heroicon-o-building-office',
            self::MANIFEST_OPENING => 'heroicon-o-document-text',
            self::GUARANTEE => 'heroicon-o-shield-check',
            self::LIABILITY_LETTER => 'heroicon-o-document-check',
            self::BL_ISSUANCE => 'heroicon-o-document-plus',
            self::DEMURRAGE => 'heroicon-o-clock',
            self::CONTAINER_MOVEMENT => 'heroicon-o-truck',
            self::CRANES => 'heroicon-o-arrow-up',
            self::UNLOADING => 'heroicon-o-arrow-down',
            self::FREIGHT => 'heroicon-o-currency-dollar',
            self::OTHER => 'heroicon-o-document',
        };
    }
}
