<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum PaymentStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case PARTIALLY_PAID = 'partially_paid';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PARTIALLY_PAID => 'Parcialmente pagado',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PARTIALLY_PAID => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::PARTIALLY_PAID => 'heroicon-o-banknotes',
            self::COMPLETED => 'heroicon-o-check-badge',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    public static function getPaymentOptions(): array
    {
        return [
            self::PENDING,
            self::PARTIALLY_PAID,
            self::COMPLETED,
        ];
    }

    public static function getSelectOptions(): array
    {
        return [
            self::PENDING->value => self::PENDING->getLabel(),
            self::PARTIALLY_PAID->value => self::PARTIALLY_PAID->getLabel(),
            self::COMPLETED->value => self::COMPLETED->getLabel(),
            self::CANCELLED->value => self::CANCELLED->getLabel(),
        ];
    }

    public static function isActive(string $status): bool
    {
        return in_array($status, [
            self::PENDING->value,
            self::PARTIALLY_PAID->value,
            self::COMPLETED->value
        ]);
    }
}
