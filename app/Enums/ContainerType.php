<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ContainerType: string implements HasLabel, HasColor, HasIcon
{
    case TYPE_20GP = '20GP';
    case TYPE_40GP = '40GP';
    case TYPE_40HC = '40HC';
    case TYPE_LCL = 'LCL';
    case TYPE_REEFER = 'REEFER';
    case TYPE_OPEN_TOP = 'OPEN_TOP';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TYPE_20GP => '20\' Standard (20GP)',
            self::TYPE_40GP => '40\' Standard (40GP)',
            self::TYPE_40HC => '40\' High Cube (40HC)',
            self::TYPE_LCL => 'Carga Consolidada (LCL)',
            self::TYPE_REEFER => 'Refrigerado (REEFER)',
            self::TYPE_OPEN_TOP => 'Techo Abierto (OPEN TOP)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TYPE_20GP => 'info',
            self::TYPE_40GP => 'warning',
            self::TYPE_40HC => 'success',
            self::TYPE_LCL => 'gray',
            self::TYPE_REEFER => 'blue',
            self::TYPE_OPEN_TOP => 'purple',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::TYPE_20GP => 'heroicon-o-rectangle-stack',
            self::TYPE_40GP => 'heroicon-o-squares-2x2',
            self::TYPE_40HC => 'heroicon-o-cube-transparent',
            self::TYPE_LCL => 'heroicon-o-archive-box',
            self::TYPE_REEFER => 'heroicon-o-sparkles',
            self::TYPE_OPEN_TOP => 'heroicon-o-arrows-pointing-out',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::TYPE_20GP => 'Contenedor estándar de 20 pies para carga seca',
            self::TYPE_40GP => 'Contenedor estándar de 40 pies para carga seca',
            self::TYPE_40HC => 'Contenedor de 40 pies de altura extra para cargas voluminosas',
            self::TYPE_LCL => 'Contenedor de carga consolidada para envíos pequeños',
            self::TYPE_REEFER => 'Contenedor refrigerado para cargas perecederas',
            self::TYPE_OPEN_TOP => 'Contenedor con techo abierto para cargas especiales',
        };
    }
}