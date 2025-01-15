<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DailyCash extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.daily-cash';
    protected static ?string $title = 'Panel de Control de Ventas';

    public $ventasData = [];
    public $encabezados = [];
    public $detalles = [];
    public $fecha;
    public $documentos = [];
    public $resumen = [];
    public $sucursal = 'GALPON';
    public $tipoFiltro = 'fecha'; // 'fecha' o 'rango'
    public $fechaInicio;
    public $fechaFin;
    public $perPage = 10; // Documentos por página
    public $currentPage = 1;

    protected $apiBaseUrl = 'http://localhost:3000/api';

    public function mount()

    {
        $this->fecha = Carbon::today()->format('Y-m-d');
        $this->fechaInicio = $this->fecha;
        $this->fechaFin = $this->fecha;
        $this->loadDashboardData();
    }

    public function updatedSucursal()
    {
        $this->loadDashboardData();
    }

    public function updatedFecha()
    {
        if ($this->tipoFiltro === 'fecha') {
            $this->loadDashboardData();
        }
    }

    public function updatedFechaInicio()
    {
        if ($this->tipoFiltro === 'rango') {
            $this->loadDashboardData();
        }
    }

    public function updatedFechaFin()
    {
        if ($this->tipoFiltro === 'rango') {
            $this->loadDashboardData();
        }
    }

    public function updatedTipoFiltro()
    {
        $this->loadDashboardData();
    }

    private function loadDashboardData()
    {
        $this->loadVentasData();
        $this->loadDocumentosConsolidados();
    }

    protected function getDateParams(): array
    {
        $params = [
            'sucursal' => $this->sucursal, // Siempre incluir sucursal
        ];

        if ($this->tipoFiltro === 'fecha') {
            $params['fecha'] = $this->fecha;
        } else {
            $params['fechaInicio'] = $this->fechaInicio;
            $params['fechaFin'] = $this->fechaFin;
        }

        return $params;
    }

    private function loadVentasData()
    {
        try {
            $response = Http::get("{$this->apiBaseUrl}/resumen/ventas", $this->getDateParams());

            if ($response->successful()) {
                $data = $response->json();
                $this->ventasData = $data['success'] ? $data['data'] : [];
            }
        } catch (\Exception $e) {
            $this->ventasData = [];
        }
    }

    private function loadDocumentosConsolidados()
    {
        try {
            $response = Http::get("{$this->apiBaseUrl}/documentos-consolidados", $this->getDateParams());

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success']) {
                    $this->documentos = collect($data['data']);
                    $this->resumen = $data['resumen'];

                    // Actualizamos los contadores de anulados en el resumen
                    $this->resumen['general']['cantidades']['facturasAnuladas'] = $this->documentos
                        ->where('TipoDocumento', 'FACTURA')
                        ->where('EstadoDocumento', 'ANULADA')
                        ->count();

                    $this->resumen['general']['cantidades']['boletasAnuladas'] = $this->documentos
                        ->where('TipoDocumento', 'BOLETA')
                        ->where('EstadoDocumento', 'ANULADA')
                        ->count();

                    $this->resumen['general']['cantidades']['anulados'] =
                        $this->resumen['general']['cantidades']['facturasAnuladas'] +
                        $this->resumen['general']['cantidades']['boletasAnuladas'];

                    $this->updateVentasData();
                }
            }
        } catch (\Exception $e) {
            $this->documentos = collect([]);
            $this->resumen = [];
        }
    }

    private function updateVentasData()
    {
        // Contamos documentos anulados
        $facturasAnuladas = $this->documentos->where('TipoDocumento', 'FACTURA')
            ->where('EstadoDocumento', 'ANULADA')->count();
        $boletasAnuladas = $this->documentos->where('TipoDocumento', 'BOLETA')
            ->where('EstadoDocumento', 'ANULADA')->count();
        $totalAnulados = $facturasAnuladas + $boletasAnuladas;

        $this->ventasData = [
            'totales' => [
                'montoTotal' => $this->resumen['general']['totales']['total'],
                'cantidadDocumentos' => $this->resumen['general']['cantidades']['total'],
                'documentosAnulados' => $totalAnulados,
            ],
            'facturas' => [
                'CantidadFacturas' => $this->resumen['general']['cantidades']['facturas'],
                'MontoTotalFacturas' => $this->resumen['general']['totales']['facturas'],
                'FacturasAnuladas' => $facturasAnuladas,
            ],
            'boletas' => [
                'CantidadBoletas' => $this->resumen['general']['cantidades']['boletas'],
                'MontoTotalBoletas' => $this->resumen['general']['totales']['boletas'],
                'BoletasAnuladas' => $boletasAnuladas,
            ],
        ];
    }

    public function paginatedDocumentos()
    {
        return $this->documentos
            ->sortByDesc('NumeroDocumento')
            ->slice(($this->currentPage - 1) * $this->perPage, $this->perPage);
    }

    public function totalPages()
    {
        return ceil($this->documentos->count() / $this->perPage);
    }

    public function nextPage()
    {
        if ($this->currentPage < $this->totalPages()) {
            $this->currentPage++;
        }
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function goToPage($page)
    {
        if ($page >= 1 && $page <= $this->totalPages()) {
            $this->currentPage = $page;
        }
    }
}
