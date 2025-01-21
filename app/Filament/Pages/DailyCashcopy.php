<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DailyCashcopy extends Page
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
        $this->loadNotasVentaClientes();
    }

    private function loadNotasVentaClientes()
    {
        try {
            $params = [];
            if ($this->tipoFiltro === 'fecha') {
                $params['date'] = $this->fecha;
            } else {
                $params['startDate'] = $this->fechaInicio;
                $params['endDate'] = $this->fechaFin;
            }

            $response = Http::get("{$this->apiBaseUrl}/notas-venta-clientes", $params);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success']) {
                    $this->ventasData['srf'] = $data['data'];
                }
            }
        } catch (\Exception $e) {
            $this->ventasData['srf'] = [
                'clientes' => [],
                'totalGeneral' => 0
            ];
        }
    }

    public function actualizarDatos()
    {
        $this->loadDashboardData();
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

        // Obtener datos de la sucursal actual (211 para GALPON)
        $sucursalData = $this->resumen['porSucursal']['211'] ?? null;

        // Preservar los datos originales del SRF antes de resetear ventasData
        $srfData = $this->ventasData['srf'] ?? null;
        $montoSRF = $srfData['totalGeneral'] ?? 0;

        // Obtener montos base
        $montoTotalVentas = $sucursalData['totales']['general'] ?? 0;
        $montoGalpon = $sucursalData['totales']['sinNotasVenta'] ?? 0;
        $montoOtros = $sucursalData['totales']['conNotasVenta'] ?? 0;

        // Calcular el total general incluyendo SRF
        $totalConSRF = $montoTotalVentas + $montoSRF;

        $ventasDataTemp = [
            'totales' => [
                'montoTotal' => $montoTotalVentas,
                'montoGalpon' => $montoGalpon,
                'montoOtros' => $montoOtros,
                'montoSRF' => $montoSRF,
                'totalConSRF' => $totalConSRF,
                'cantidadDocumentos' => $this->resumen['general']['cantidades']['total'],
                'documentosAnulados' => $totalAnulados,
            ],
            'facturas' => [
                'CantidadFacturas' => $this->resumen['general']['cantidades']['facturas'],
                'MontoTotalFacturas' => $sucursalData['facturas']['total'] ?? 0,
                'FacturasGalpon' => $sucursalData['facturas']['totalSinNV'] ?? 0,
                'FacturasOtros' => $sucursalData['facturas']['conNotasVenta']['total'] ?? 0,
                'FacturasAnuladas' => $facturasAnuladas,
            ],
            'boletas' => [
                'CantidadBoletas' => $this->resumen['general']['cantidades']['boletas'],
                'MontoTotalBoletas' => $sucursalData['boletas']['total'] ?? 0,
                'BoletasGalpon' => $sucursalData['boletas']['totalSinNV'] ?? 0,
                'BoletasOtros' => $sucursalData['boletas']['conNotasVenta']['total'] ?? 0,
                'BoletasAnuladas' => $boletasAnuladas,
            ],
        ];

        // Mantener los datos originales del SRF si existen
        if ($srfData) {
            $ventasDataTemp['srf'] = $srfData;
        }

        $this->ventasData = $ventasDataTemp;
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
