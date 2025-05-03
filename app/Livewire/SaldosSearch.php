<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class SaldosSearch extends Component
{
    public $search = '';
    public $results = [];
    public $selectedItem = null;
    public $suggestions = [];
    public $loading = false;
    public $error = null;
    public $perPage = 20; // Actualizado según documentación
    public $currentPage = 1;
    public $totalResults = 0;
    public $totalPages = 0;
    public $hasMorePages = true;
    public $searchType = 'cod_art'; // Modificado: Ahora usa 'cod_art' como valor predeterminado

    // Propiedades adicionales para manejar información agrupada
    public $productSummary = null;
    public $relatedItems = [];
    public $totalProductStock = 0;
    public $zetaCount = 0;

    // Agregar propiedades para valores ponderados
    public $cifPonderado = 0;
    public $precioVtaPonderado = 0;

    public function mount()
    {
        if (!empty($this->search)) {
            $this->loadData();
        }
    }

    public function render()
    {
        return view('livewire.saldos-search');
    }

    public function updatedSearch()
    {
        if (strlen($this->search) >= 3) {
            $this->loadSuggestions();
        } else {
            $this->suggestions = [];
        }
    }

    public function selectSuggestion($code)
    {
        $this->search = $code;
        $this->suggestions = [];
        $this->loadData();
    }

    public function loadSuggestions()
    {
        if (strlen($this->search) < 3) {
            $this->suggestions = [];
            return;
        }

        try {
            // Configurar parámetros según documentación
            $params = [
                $this->searchType => $this->search,
                'cod_bod' => '01', // Bodega 01 por defecto
            ];

            // Registrar la URL completa para debugging
            $apiUrl = env('API_SALDOS_URL');
            $fullUrl = $apiUrl . '?' . http_build_query($params);

            Log::info('Solicitando sugerencias', [
                'url' => $fullUrl,
                'params' => $params
            ]);

            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get($apiUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? [];

                // Obtener sugerencias basadas en el tipo de búsqueda
                $field = $this->searchType === 'cod_art' ? 'Código_Artículo' : 'Zeta_Articulo';

                $this->suggestions = collect($items)
                    ->pluck($field)
                    ->unique()
                    ->take(10)
                    ->toArray();
            } else {
                $this->error = 'Error al cargar sugerencias';
                $this->suggestions = [];
            }
        } catch (\Exception $e) {
            $this->error = 'Error de conexión';
            $this->suggestions = [];
        }
    }

    public function loadData()
    {
        $this->loading = true;
        $this->error = null;
        $this->suggestions = [];
        $this->results = [];

        if (empty($this->search)) {
            $this->loading = false;
            return;
        }

        try {
            // Configurar parámetros según documentación actualizada
            $params = [
                'page' => $this->currentPage,
                'pageSize' => $this->perPage,
                'cod_bod' => '01', // Siempre usar bodega 01
            ];

            // Agregar filtro por código de artículo o zeta según selección
            if (!empty($this->search)) {
                $params[$this->searchType] = $this->search;
            }

            $apiUrl = env('API_SALDOS_URL');
            $fullUrl = $apiUrl . '?' . http_build_query($params);

            Log::info('Solicitando datos de saldos', [
                'url' => $fullUrl,
                'params' => $params
            ]);

            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get($apiUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $this->results = $data['data'] ?? [];
                $this->totalResults = $data['total'] ?? count($this->results);
                $this->totalPages = $data['pages'] ?? ceil($this->totalResults / $this->perPage);
                $this->hasMorePages = $this->currentPage < $this->totalPages;

                // Capturar valores ponderados si están disponibles
                if (!empty($this->results) && isset($this->results[0]['Cif_Prom_Ponderado'])) {
                    $this->cifPonderado = $this->results[0]['Cif_Prom_Ponderado'];
                    $this->precioVtaPonderado = $this->results[0]['Precio_Vta_Ponderado'] ?? 0;
                }

                Log::info('Respuesta API saldos', [
                    'total' => $this->totalResults,
                    'pages' => $this->totalPages,
                    'current' => $this->currentPage,
                    'count' => count($this->results)
                ]);
            } else {
                $statusCode = $response->status();
                $errorBody = $response->body();

                Log::error('Error en respuesta de API', [
                    'status' => $statusCode,
                    'body' => $errorBody
                ]);

                $this->error = "Error al cargar datos: Código $statusCode";
                $this->results = [];
            }
        } catch (\Exception $e) {
            Log::error('Excepción en consulta a API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error = 'Error de conexión: ' . $e->getMessage();
            $this->results = [];
        }

        $this->loading = false;
    }

    public function loadNextPage()
    {
        if ($this->hasMorePages) {
            $this->selectedItem = null;
            $this->currentPage++;
            $this->loadData();
            $this->dispatch('scrollToTop');
        } else {
            Notification::make()
                ->title('No hay más páginas disponibles')
                ->warning()
                ->send();
        }
    }

    public function nextPage()
    {
        $this->loadNextPage();
    }

    public function previousPage()
    {
        $this->selectedItem = null;
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadData();
            $this->dispatch('scrollToTop');
        }
    }

    public function viewDetails($index)
    {
        // Almacenar el item seleccionado para mostrar en el modal
        $this->selectedItem = $this->results[$index];

        // Buscar todos los items con el mismo código de artículo
        $codigo = $this->selectedItem['Código_Artículo'];

        // Si no estamos en modo paginación, buscar todos los resultados para este código
        $this->fetchAllItemsForCode($codigo);
    }

    /**
     * Obtiene todos los items con el mismo código de artículo para calcular saldos totales
     */
    protected function fetchAllItemsForCode($codigo)
    {
        try {
            // Parámetros para buscar todos los registros con este código
            $params = [
                'cod_art' => $codigo,
                'cod_bod' => '01', // Bodega 01 por defecto
            ];

            $apiUrl = env('API_SALDOS_URL');
            $fullUrl = $apiUrl . '?' . http_build_query($params);

            Log::info('Obteniendo todos los items para código', [
                'url' => $fullUrl,
                'codigo' => $codigo
            ]);

            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get($apiUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $this->relatedItems = $data['data'] ?? [];

                // Calcular la suma total de saldos
                $this->totalProductStock = collect($this->relatedItems)->sum('Saldo_Disponible');

                // Contar el número de zetas únicos
                $this->zetaCount = collect($this->relatedItems)->pluck('Zeta_Articulo')->unique()->count();

                // Capturar valores ponderados si están disponibles
                if (!empty($this->relatedItems) && isset($this->relatedItems[0]['Cif_Prom_Ponderado'])) {
                    $this->cifPonderado = $this->relatedItems[0]['Cif_Prom_Ponderado'];
                    $this->precioVtaPonderado = $this->relatedItems[0]['Precio_Vta_Ponderado'] ?? 0;
                }

                // Crear resumen del producto
                $this->productSummary = [
                    'Código_Artículo' => $codigo,
                    'Descripción_Artículo' => $this->selectedItem['Descripción_Artículo'],
                    'Cantidad_Zetas' => $this->zetaCount,
                    'Total_Saldo' => $this->totalProductStock
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener items relacionados', [
                'error' => $e->getMessage(),
                'codigo' => $codigo
            ]);
        }
    }

    /**
     * Limpia la selección y los datos relacionados
     */
    public function clearSelection()
    {
        $this->selectedItem = null;
        $this->relatedItems = [];
        $this->productSummary = null;
        $this->totalProductStock = 0;
        $this->zetaCount = 0;
    }

    public function createOrder($index)
    {
        if (!isset($this->results[$index])) {
            return;
        }

        $item = $this->results[$index];

        session()->flash('saldos_product', [
            'codigo' => $item['Código_Artículo'],
            'descripcion' => $item['Descripción_Artículo'],
            'costo' => $item['Costo'],
            'saldo' => $item['Saldo_Disponible']
        ]);

        Notification::make()
            ->title('Producto seleccionado')
            ->body('Ahora puede crear una orden de importación con este producto.')
            ->success()
            ->send();

        return redirect()->to(route('filament.admin.resources.comex-import-orders.create', [
            'producto' => $item['Código_Artículo']
        ]));
    }

    public function createNewOrder()
    {
        // Obtener el tenant actual
        $tenant = Filament::getTenant();

        if (!$tenant) {
            Notification::make()
                ->title('Error de configuración')
                ->body('No se pudo determinar la tienda actual.')
                ->danger()
                ->send();
            return;
        }

        // Construir la URL correctamente incluyendo el tenant
        $url = route('filament.admin.resources.comex-import-orders.create', [
            'tenant' => $tenant->id
        ]);

        return redirect()->to($url);
    }

    public function setSearchType($type)
    {
        $this->searchType = $type;
        $this->resetSearch();
    }

    public function resetSearch()
    {
        $this->search = '';
        $this->currentPage = 1;
        $this->results = [];
        $this->suggestions = [];
    }

    public function testApiConnection()
    {
        $this->loading = true;
        $this->error = null;
        $this->results = [];

        try {
            $apiUrl = env('API_SALDOS_URL');
            $params = [
                'page' => 1,
                'pageSize' => 5,
                'cod_bod' => '01' // Bodega 01 por defecto
            ];

            $fullUrl = $apiUrl . '?' . http_build_query($params);

            Log::info('Probando conexión a API', [
                'url' => $fullUrl
            ]);

            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get($apiUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $totalResults = isset($data['total']) ? $data['total'] : 0;
                $this->error = "Conexión exitosa a la API. Se encontraron $totalResults registros disponibles.";

                // Mostrar algunos datos de muestra
                if (!empty($data['data'])) {
                    $this->results = array_slice($data['data'], 0, 5);
                }
            } else {
                $this->error = "Error conectando a la API: Código " . $response->status();
            }
        } catch (\Exception $e) {
            $this->error = "Excepción al conectar con la API: " . $e->getMessage();
        }

        $this->loading = false;
    }
}
