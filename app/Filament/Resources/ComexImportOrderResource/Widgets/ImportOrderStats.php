<?php

namespace App\Filament\Resources\ComexImportOrderResource\Widgets;

use App\Models\Bank;
use Filament\Facades\Filament;
use App\Models\ComexImportOrder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ImportOrderStats extends BaseWidget
{
    protected static ?string $pollingInterval = '50s';

    private const CHART_POINTS = 6;
    private const CURRENCY_FORMAT = 2;
    private const CHART_DATA = [7, 3, 4, 5, 6];

    protected function getStats(): array
    {
        return [
            $this->getOrdersStat(),
            $this->getBankBalanceStat(),
            $this->getExpensesStat(),
        ];
    }

    private function getOrdersStat(): Stat
    {
        $totalOrders = ComexImportOrder::getActiveOrdersCount();

        return Stat::make(
            label: 'Total Órdenes',
            value: $totalOrders
        )
            ->description('Órdenes de importación activas')
            ->descriptionIcon('heroicon-m-truck')
            ->chart($this->generateChartData($totalOrders))
            ->color('primary');
    }

    private function getBankBalanceStat(): Stat
    {
        $balance = Bank::getTotalUsdBalance();

        return Stat::make(
            label: 'Balance en Banco USD',
            value: '$' . $this->formatCurrency($balance)
        )
            ->description('Último balance registrado')
            ->descriptionIcon('heroicon-m-building-library')
            ->chart($this->generateChartData($balance))
            ->color($this->getBalanceColor($balance));
    }

    private function getExpensesStat(): Stat
    {
        $expenses = ComexImportOrder::getTotalExpenses();

        return Stat::make(
            label: 'Total Gastos',
            value: '$' . $this->formatCurrency($expenses)
        )
            ->description('USD en gastos')
            ->descriptionIcon('heroicon-m-banknotes')
            ->chart($this->generateChartData($expenses))
            ->color('danger');
    }

    private function formatCurrency(float $amount): string
    {
        return number_format($amount, self::CURRENCY_FORMAT, ',', '.');
    }

    private function generateChartData($finalValue): array
    {
        return array_merge(self::CHART_DATA, [$finalValue]);
    }

    private function getBalanceColor(float $balance): string
    {
        if ($balance <= 0) return 'danger';
        if ($balance < 1000) return 'warning';
        return 'success';
    }
}
