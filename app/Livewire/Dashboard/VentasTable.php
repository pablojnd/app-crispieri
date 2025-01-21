<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;

class VentasTable extends Component
{
    use WithPagination;

    public $documentos = [];
    public $expandedRows = [];
    public $perPage = 10;
    public $currentPage = 1;
    protected $apiBaseUrl = 'http://localhost:3000/api';

    protected $listeners = ['filtrosActualizados' => 'actualizarDatos'];

    public function mount()
    {
        $this->actualizarDatos([
            'sucursal' => 'GALPON',
            'tipoFiltro' => 'fecha',
            'fecha' => now()->format('Y-m-d')
        ]);
    }

    public function actualizarDatos($filtros)
    {
        try {
            $params = [
                'sucursal' => $filtros['sucursal']
            ];

            if ($filtros['tipoFiltro'] === 'fecha') {
                $params['fecha'] = $filtros['fecha'];
            } else {
                $params['fechaInicio'] = $filtros['fechaInicio'];
                $params['fechaFin'] = $filtros['fechaFin'];
            }

            $response = Http::get("{$this->apiBaseUrl}/documentos-consolidados", $params);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success']) {
                    $this->documentos = collect($data['data'])
                        ->sortByDesc('NumeroDocumento')
                        ->values()
                        ->all();
                    $this->expandedRows = []; // Reset expanded rows on data update
                    $this->resetPage(); // Reset pagination
                }
            }
        } catch (\Exception $e) {
            $this->documentos = [];
        }
    }

    public function toggleRow($index)
    {
        if (isset($this->expandedRows[$index])) {
            unset($this->expandedRows[$index]);
        } else {
            $this->expandedRows[$index] = true;
        }
    }

    public function isRowExpanded($index)
    {
        return isset($this->expandedRows[$index]);
    }

    public function setPage($page)
    {
        $this->currentPage = $page;
    }

    public function getPaginatedDocumentos()
    {
        return collect($this->documentos)
            ->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)
            ->values();
    }

    public function getTotalPages()
    {
        return ceil(count($this->documentos) / $this->perPage);
    }

    public function render()
    {
        return view('livewire.dashboard.ventas-table', [
            'paginatedDocumentos' => $this->getPaginatedDocumentos(),
            'totalPages' => $this->getTotalPages()
        ]);
    }
}
