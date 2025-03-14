<?php

namespace App\Exports;

use App\Enums\ExpenseType;
use App\Models\ComexImportOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class ComexImportOrderBulkExport implements WithMultipleSheets
{
    protected $selectedOrders;

    public function __construct(?array $selectedOrders = null)
    {
        $this->selectedOrders = $selectedOrders;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        $tenant = Filament::getTenant();

        // Construir la consulta base
        $query = ComexImportOrder::query()
            ->where('store_id', $tenant->id)
            ->with([
                'store',
                'provider',
                'originCountry',
                'items.product',
                'comexShippingLineContainers.shippingLine',
                'comexShippingLineContainers.containers',
                'documents',
                'expenses'
            ]);

        // Filtrar por órdenes seleccionadas si se proporciona el parámetro
        if (!empty($this->selectedOrders)) {
            $query->whereIn('id', $this->selectedOrders);
        }

        // Obtener órdenes
        $orders = $query->get();

        // Crear una hoja para el resumen de todas las órdenes
        $sheets['Resumen'] = new OrdersSummarySheet($orders);

        // Crear una hoja para cada orden individual
        foreach ($orders as $order) {
            $sheetName = substr('Orden_' . $order->reference_number, 0, 31); // Excel limita el nombre de la hoja a 31 caracteres
            $sheets[$sheetName] = new SingleOrderSheet($order);
        }

        return $sheets;
    }
}
