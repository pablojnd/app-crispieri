<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;

enum DocumentType: string implements HasLabel, HasColor, HasIcon
{
    case INVOICE = 'invoice';
    case PACKING_LIST = 'packing_list';
    case BL = 'bl';
    case INSURANCE = 'insurance';
    case CERTIFICATE = 'certificate';
    case OTHER = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INVOICE => 'Factura',
            self::PACKING_LIST => 'Lista de Empaque',
            self::BL => 'B/L',
            self::INSURANCE => 'Seguro',
            self::CERTIFICATE => 'Certificado',
            self::OTHER => 'Otro',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::INVOICE => 'success',
            self::PACKING_LIST => 'info',
            self::BL => 'warning',
            self::INSURANCE => 'danger',
            self::CERTIFICATE => 'primary',
            self::OTHER => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::INVOICE => 'heroicon-o-document-text',
            self::PACKING_LIST => 'heroicon-o-clipboard-document-list',
            self::BL => 'heroicon-o-truck',
            self::INSURANCE => 'heroicon-o-shield-check',
            self::CERTIFICATE => 'heroicon-o-document-check',
            self::OTHER => 'heroicon-o-document',
        };
    }
}
