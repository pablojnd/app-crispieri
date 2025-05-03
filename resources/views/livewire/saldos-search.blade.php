<div>
    <div class="mb-6">
        @if ($error)
            <div class="relative px-4 py-3 mb-4 border rounded bg-danger-100 border-danger-300 text-danger-700" role="alert">
                <span class="block sm:inline">{{ $error }}</span>
            </div>
        @endif

        <div class="relative">
            <div class="flex items-center gap-2 mb-2">
                <div class="relative flex-grow">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="search"
                            wire:keydown.enter="loadData"
                            placeholder="Buscar por código de artículo"
                            class="w-full"
                        />
                        @if($loading)
                            <div class="absolute right-3 top-2.5">
                                <x-filament::loading-indicator class="w-5 h-5" />
                            </div>
                        @endif
                    </x-filament::input.wrapper>

                    @if(!empty($suggestions))
                        <div class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-lg shadow-lg max-h-60">
                            @foreach($suggestions as $suggestion)
                                <div
                                    wire:click="selectSuggestion('{{ $suggestion }}')"
                                    class="px-4 py-2 text-sm cursor-pointer hover:bg-gray-100"
                                >
                                    {{ $suggestion }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <x-filament::button
                    wire:click="loadData"
                    wire:loading.attr="disabled"
                >
                    Buscar
                </x-filament::button>

                <x-filament::button
                    wire:click="createNewOrder"
                    wire:loading.attr="disabled"
                    color="success"
                    icon="heroicon-m-plus-circle"
                >
                    Crear Orden
                </x-filament::button>
            </div>

            <!-- Opciones de búsqueda - Solo mostrar tipo de búsqueda, eliminar selector de bodega -->
            <div class="flex flex-wrap items-center gap-3 mt-2 mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">Buscar por:</span>
                    <x-filament::button
                        size="xs"
                        color="{{ $searchType === 'cod_art' ? 'primary' : 'gray' }}"
                        wire:click="setSearchType('cod_art')"
                    >
                        Código artículo
                    </x-filament::button>
                    <x-filament::button
                        size="xs"
                        color="{{ $searchType === 'codigo' ? 'primary' : 'gray' }}"
                        wire:click="setSearchType('codigo')"
                    >
                        Código zeta
                    </x-filament::button>
                </div>

                <x-filament::button
                    wire:click="testApiConnection"
                    wire:loading.attr="disabled"
                    color="gray"
                    icon="heroicon-o-bug-ant"
                    size="xs"
                >
                    Diagnosticar API
                </x-filament::button>
            </div>

            <div class="mt-1 text-xs text-gray-500">
                API: {{ env('API_SALDOS_URL') }} (Bodega: 01)
            </div>
        </div>
    </div>

    @if (count($results) > 0)
        <div class="flex items-center justify-between mb-2">
            <div class="text-sm text-gray-700">
                Mostrando {{ count($results) }} de {{ $totalResults }} resultados
            </div>
        </div>

        <div x-data="{}" x-on:scroll-to-top.window="$el.scrollIntoView({ behavior: 'smooth' })" class="overflow-hidden bg-white border border-gray-300 shadow-sm rounded-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-2">Código</th>
                            <th scope="col" class="px-3 py-2">Zeta</th>
                            <th scope="col" class="px-3 py-2">Descripción</th>
                            <th scope="col" class="px-3 py-2 text-center">Bod</th>
                            <th scope="col" class="px-3 py-2 text-center">Saldo</th>
                            <th scope="col" class="px-3 py-2 text-center">Costo</th>
                            <th scope="col" class="px-3 py-2 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $index => $item)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $item['Código_Artículo'] }}</td>
                                <td class="px-3 py-2 text-xs text-gray-600">{{ $item['Zeta_Articulo'] }}</td>
                                <td class="max-w-xs px-3 py-2 text-gray-600 truncate">{{ $item['Descripción_Artículo'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $item['Bod'] }}</td>
                                <td class="px-3 py-2 text-center font-medium {{ $item['Saldo_Disponible'] <= 0 ? 'text-danger-600' : 'text-primary-600' }}">
                                    {{ number_format($item['Saldo_Disponible'], 2) }}
                                </td>
                                <td class="px-3 py-2 text-center">${{ number_format($item['Costo'], 2) }}</td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center space-x-1">
                                        <x-filament::button
                                            size="xs"
                                            color="gray"
                                            wire:click="viewDetails({{ $index }})"
                                        >
                                            Ver detalles
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between mt-4">
            <div>
                @if ($totalResults > 0)
                    <span class="text-sm text-gray-700">
                        Página {{ $currentPage }} de {{ ceil($totalResults / $perPage) }}
                    </span>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <x-filament::button
                    size="sm"
                    color="gray"
                    wire:click="previousPage"
                    icon="heroicon-m-chevron-left"
                    :disabled="$currentPage <= 1"
                >
                    Anterior
                </x-filament::button>

                <x-filament::button
                    size="sm"
                    color="gray"
                    wire:click="loadNextPage"
                    icon="heroicon-m-chevron-right"
                    icon-position="after"
                    :disabled="!$hasMorePages"
                >
                    Siguiente
                </x-filament::button>
            </div>
        </div>
    @elseif (!empty($search) && !$loading)
        <div class="p-6 text-center bg-white border rounded-lg">
            <p class="text-gray-500">No se encontraron resultados para "{{ $search }}"</p>
        </div>
    @endif

    @if ($selectedItem)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-gray-500 bg-opacity-75">
            <div class="w-full max-w-4xl mx-4 overflow-hidden bg-white rounded-lg shadow-xl">
                <div class="flex items-center justify-between px-6 py-4 border-b bg-primary-50">
                    <h3 class="text-lg font-medium text-primary-900">
                        Detalles del Artículo
                    </h3>
                    <button wire:click="clearSelection" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="px-6 py-4">
                    <h4 class="mb-3 text-lg font-semibold text-gray-900">{{ $selectedItem['Descripción_Artículo'] }}</h4>

                    <div class="grid grid-cols-2 gap-3 mb-4 md:grid-cols-4">
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Código</p>
                            <p class="font-medium text-gray-800">{{ $selectedItem['Código_Artículo'] }}</p>
                        </div>
                        <div class="col-span-1 p-3 rounded-md bg-gray-50 md:col-span-3">
                            <div class="flex justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Zeta</p>
                                    <p class="font-medium text-gray-800">{{ $selectedItem['Zeta_Articulo'] }}</p>
                                </div>
                                @if($zetaCount > 0)
                                    <div class="ml-4">
                                        <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">
                                            {{ $zetaCount }} zeta{{ $zetaCount != 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Modificar la visualización para incluir los 9 campos -->
                    <div class="grid grid-cols-2 gap-3 mb-3 sm:grid-cols-3 md:grid-cols-9">
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Bodega</p>
                            <p class="font-medium">{{ $selectedItem['Bod'] }}</p>
                        </div>
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Año</p>
                            <p class="font-medium">{{ $selectedItem['Año'] }}</p>
                        </div>
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Packing</p>
                            <p class="font-medium">{{ $selectedItem['U_C'] }}</p>
                        </div>
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">U. Medida</p>
                            <p class="font-medium">{{ $selectedItem['U_M'] }}</p>
                        </div>
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">CIF</p>
                            <p class="font-medium">${{ number_format($selectedItem['Cif'], 4) }}</p>
                        </div>
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Costo</p>
                            <p class="font-medium">${{ number_format($selectedItem['Costo'], 2) }}</p>
                        </div>
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Precio Venta</p>
                            <p class="font-medium">${{ number_format($selectedItem['Precio_Vta'], 2) }}</p>
                        </div>
                        <div class="p-3 rounded-md bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Saldo Zeta</p>
                            <p class="font-medium">{{ number_format($selectedItem['Saldo_Disponible'], 2) }}</p>
                        </div>
                        <div class="p-3 border rounded-md bg-primary-50 border-primary-100">
                            <p class="text-xs font-semibold uppercase text-primary-500">Saldo Total</p>
                            <p class="font-medium text-primary-700">{{ number_format($totalProductStock, 2) }}</p>
                        </div>
                    </div>

                    <!-- Mostrar valores ponderados si están disponibles -->
                    @if(isset($selectedItem['Cif_Prom_Ponderado']) || $cifPonderado > 0)
                    <div class="mt-4 p-4 border border-blue-100 rounded-lg bg-blue-50">
                        <h5 class="text-sm font-semibold text-blue-700 mb-2">Valores Ponderados</h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-blue-600 uppercase">CIF Ponderado</p>
                                <p class="font-medium">${{ number_format(isset($selectedItem['Cif_Prom_Ponderado']) ? $selectedItem['Cif_Prom_Ponderado'] : $cifPonderado, 4) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-blue-600 uppercase">Precio Venta Ponderado</p>
                                <p class="font-medium">${{ number_format(isset($selectedItem['Precio_Vta_Ponderado']) ? $selectedItem['Precio_Vta_Ponderado'] : $precioVtaPonderado, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if(count($relatedItems) > 1)
                    <div class="mt-6">
                        <h5 class="mb-2 text-sm font-medium text-gray-700">Todos los saldos para este código:</h5>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-full text-sm divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Zeta</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bodega</th>
                                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                        <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Costo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($relatedItems as $item)
                                    <tr class="{{ $item['Zeta_Articulo'] === $selectedItem['Zeta_Articulo'] ? 'bg-blue-50' : '' }}">
                                        <td class="px-3 py-2 text-xs">{{ $item['Zeta_Articulo'] }}</td>
                                        <td class="px-3 py-2 text-xs">{{ $item['Bod'] }}</td>
                                        <td class="px-3 py-2 text-xs text-center">{{ number_format($item['Saldo_Disponible'], 2) }}</td>
                                        <td class="px-3 py-2 text-xs text-right">${{ number_format($item['Costo'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="flex justify-end px-6 py-3 bg-gray-50">
                    <x-filament::button
                        wire:click="clearSelection"
                    >
                        Cerrar
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
