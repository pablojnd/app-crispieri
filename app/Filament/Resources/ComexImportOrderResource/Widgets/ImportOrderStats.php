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

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        $totalOrders = ComexImportOrder::whereBelongsTo($tenant)->count();

        $totalExpenses = ComexImportOrder::whereBelongsTo($tenant)
            ->withSum('expenses', 'expense_amount')
            ->get()
            ->sum('expenses_sum_expense_amount');

        $bankBalance = Bank::whereBelongsTo($tenant)
            ->whereHas('currency', fn($query) => $query->where('code', 'USD'))
            ->with('latestBalance')
            ->get()
            ->sum(function ($bank) {
                return $bank->latestBalance?->balance ?? 0;
            });

        return [
            Stat::make('Total Órdenes', $totalOrders)
                ->description('Órdenes de importación activas')
                ->descriptionIcon('heroicon-m-truck')
                ->chart([7, 3, 4, 5, 6, $totalOrders])
                ->color('primary'),

            Stat::make('Balance en Banco', number_format($bankBalance, 2))
                ->description('USD disponibles')
                ->descriptionIcon('heroicon-m-building-library')
                ->chart([4, 8, 3, 5, 6, $bankBalance])
                ->color('success'),

            Stat::make('Total Gastos', number_format($totalExpenses, 2))
                ->description('USD en gastos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([3, 5, 7, 4, 5, $totalExpenses])
                ->color('danger'),
        ];
    }
}
