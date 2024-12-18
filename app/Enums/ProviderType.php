<?php
namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ProviderType: string implements HasLabel, HasColor, HasIcon
{
    case MANUFACTURER = 'manufacturer';
    case DISTRIBUTOR = 'distributor';
    case WHOLESALER = 'wholesaler';
    case RETAILER = 'retailer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MANUFACTURER => 'Fabricante',
            self::DISTRIBUTOR => 'Distribuidor',
            self::WHOLESALER => 'Mayorista',
            self::RETAILER => 'Minorista',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MANUFACTURER => 'success',
            self::DISTRIBUTOR => 'warning',
            self::WHOLESALER => 'info',
            self::RETAILER => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::MANUFACTURER => 'heroicon-o-building-office-2',
            self::DISTRIBUTOR => 'heroicon-o-building-storefront',
            self::WHOLESALER => 'heroicon-o-shopping-bag',
            self::RETAILER => 'heroicon-o-shopping-cart',
        };
    }
}