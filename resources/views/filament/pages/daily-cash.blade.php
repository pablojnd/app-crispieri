<x-filament-panels::page>
    <div class="space-y-4">
        <!-- Header con controles en línea -->
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap gap-4">
                <!-- Selector de Sucursal -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block mb-1 text-xs font-medium text-gray-700">Sucursal</label>
                    <select wire:model.live="sucursal" class="block w-full border-gray-300 rounded-lg shadow-sm">
                        <option value="GALPON">Galpón</option>
                        <option value="VICTORIA">Victoria</option>
                        <option value="LA_TORRE">La Torre</option>
                        <option value="WEB">Web</option>
                    </select>
                </div>

                <!-- Selector de Tipo de Consulta -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block mb-1 text-xs font-medium text-gray-700">Tipo de Consulta</label>
                    <select wire:model.live="tipoFiltro" class="block w-full border-gray-300 rounded-lg shadow-sm">
                        <option value="fecha">Fecha Específica</option>
                        <option value="rango">Rango de Fechas</option>
                    </select>
                </div>

                <!-- Selectores de Fecha -->
                <div class="flex-1 min-w-[200px]">
                    @if($tipoFiltro === 'fecha')
                        <label class="block mb-1 text-xs font-medium text-gray-700">Fecha</label>
                        <input type="date" wire:model.live="fecha" class="block w-full border-gray-300 rounded-lg shadow-sm">
                    @else
                        <label class="block mb-1 text-xs font-medium text-gray-700">Rango de Fechas</label>
                        <div class="flex gap-2">
                            <input type="date" wire:model.live="fechaInicio" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <input type="date" wire:model.live="fechaFin" class="w-full border-gray-300 rounded-lg shadow-sm">
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Cards en fila -->
        <div class="flex flex-wrap gap-4">
            <!-- Card Facturas -->
            <div class="flex-1 min-w-[250px] bg-white rounded-lg shadow">
                <div class="flex items-center justify-between p-2 border-b">
                    <h3 class="text-sm font-medium text-gray-500">Facturas</h3>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">
                        {{ $ventasData['facturas']['CantidadFacturas'] ?? 0 }} docs
                    </span>
                </div>
                <div class="p-3 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Total:</span>
                        <span class="text-lg font-bold text-primary-600">${{ number_format($ventasData['facturas']['MontoTotalFacturas'] ?? 0, 0, ',', '.') }}</span>
                        <span class="text-lg font-bold text-primary-600">${{ number_format($ventasData['facturas']['MontoTotalFacturas'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Nulas:</span>
                        <span class="text-sm text-red-600 font-medium">{{ $resumen['general']['cantidades']['facturasAnuladas'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center border-t pt-2">
                        <span class="text-xs text-gray-500">Promedio:</span>
                        <span class="text-sm text-gray-600">${{ number_format(($ventasData['facturas']['MontoTotalFacturas'] ?? 0) / max(($ventasData['facturas']['CantidadFacturas'] ?? 1), 1), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Card Boletas -->
            <div class="flex-1 min-w-[250px] bg-white rounded-lg shadow">
                <div class="flex items-center justify-between p-2 border-b">
                    <h3 class="text-sm font-medium text-gray-500">Boletas</h3>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-purple-700 bg-purple-100 rounded-full">
                        {{ $ventasData['boletas']['CantidadBoletas'] ?? 0 }} docs
                    </span>
                </div>
                <div class="p-3 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Total:</span>
                        <span class="text-lg font-bold text-primary-600">${{ number_format($ventasData['boletas']['MontoTotalBoletas'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Nulas:</span>
                        <span class="text-sm text-red-600 font-medium">{{ $resumen['general']['cantidades']['boletasAnuladas'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center border-t pt-2">
                        <span class="text-xs text-gray-500">Promedio:</span>
                        <span class="text-sm text-gray-600">${{ number_format(($ventasData['boletas']['MontoTotalBoletas'] ?? 0) / max(($ventasData['boletas']['CantidadBoletas'] ?? 1), 1), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Card Total Ventas -->
            <div class="flex-1 min-w-[250px] bg-white rounded-lg shadow">
                <div class="flex items-center justify-between p-2 border-b">
                    <h3 class="text-sm font-medium text-gray-500">Total Ventas</h3>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 rounded-full">
                        {{ $ventasData['totales']['cantidadDocumentos'] ?? 0 }} docs
                    </span>
                </div>
                <div class="p-3 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Total:</span>
                        <span class="text-lg font-bold text-primary-600">${{ number_format($ventasData['totales']['montoTotal'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Nulas:</span>
                        <span class="text-sm text-red-600 font-medium">{{ $resumen['general']['cantidades']['anulados'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center border-t pt-2">
                        <span class="text-xs text-gray-500">Promedio:</span>
                        <span class="text-sm text-gray-600">${{ number_format(($ventasData['totales']['montoTotal'] ?? 0) / max(($ventasData['totales']['cantidadDocumentos'] ?? 1), 1), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de detalles mejorada -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Detalle de Ventas</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200" x-data="{ expandedRows: {} }">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-10"></th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">N° Doc
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Cliente
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Vendedor
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Total
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Pago
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->paginatedDocumentos() as $index => $doc)
                        <tr class="transition-colors hover:bg-gray-50 {{ $doc['EstadoDocumento'] === 'ANULADA' ? 'bg-red-50' : '' }}">
                            <td class="px-3">
                                <button
                                    @click="expandedRows[{{ $index }}] = !expandedRows[{{ $index }}]"
                                    class="p-2 transition-transform duration-200 rounded-full hover:bg-gray-100 focus:outline-none {{ $doc['EstadoDocumento'] === 'ANULADA' ? 'text-red-600' : '' }}"
                                    :class="{ 'rotate-90 transform': expandedRows[{{ $index }}] }"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $doc['TipoDocumento'] === 'BOLETA' ?
                                        ($doc['EstadoDocumento'] === 'ANULADA' ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700') :
                                        ($doc['EstadoDocumento'] === 'ANULADA' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')
                                    }}">
                                    {{ $doc['TipoDocumento'] }}
                                    {{ $doc['EstadoDocumento'] === 'ANULADA' ? '(ANULADA)' : '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium {{ $doc['EstadoDocumento'] === 'ANULADA' ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $doc['NumeroDocumento'] }}
                            </td>
                            <td class="px-6 py-4 text-sm {{ $doc['EstadoDocumento'] === 'ANULADA' ? 'text-red-600' : 'text-gray-500' }}">
                                {{ $doc['Cliente'] }}
                            </td>
                            <td class="px-6 py-4 text-sm {{ $doc['EstadoDocumento'] === 'ANULADA' ? 'text-red-600' : 'text-gray-500' }}">
                                {{ $doc['Vendedor'] }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-right {{ $doc['EstadoDocumento'] === 'ANULADA' ? 'text-red-600' : 'text-gray-900' }}">
                                ${{ number_format($doc['TotalDocumento'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $doc['EstadoDocumento'] === 'ANULADA' ?
                                        'bg-red-100 text-red-700' :
                                        (str_contains($doc['FormaPagoUnificada'], 'TARJETA') ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700')
                                    }}">
                                    {{ $doc['FormaPagoUnificada'] }}
                                </span>
                            </td>
                        </tr>
                        <tr x-show="expandedRows[{{ $index }}]" x-cloak class="bg-gray-50" x-transition>
                            <td colspan="7" class="p-0">
                                <div class="overflow-hidden">
                                    <table class="w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th
                                                    class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-500">
                                                    Producto</th>
                                                <th
                                                    class="px-6 py-2 text-xs font-medium tracking-wider text-center text-gray-500">
                                                    Cantidad</th>
                                                <th
                                                    class="px-6 py-2 text-xs font-medium tracking-wider text-right text-gray-500">
                                                    Precio Unit.</th>
                                                <th
                                                    class="px-6 py-2 text-xs font-medium tracking-wider text-right text-gray-500">
                                                    Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($doc['Productos'] as $producto)
                                            <tr class="hover:bg-gray-100">
                                                <td class="px-6 py-2 text-sm text-gray-900">{{ $producto['Producto'] }}</td>
                                                <td class="px-6 py-2 text-sm text-center text-gray-900">{{
                                                    $producto['CantidadVendida'] }}</td>
                                                <td class="px-6 py-2 text-sm text-right text-gray-900">${{
                                                    number_format($producto['Precio'], 0, ',', '.') }}</td>
                                                <td class="px-6 py-2 text-sm font-medium text-right text-gray-900">${{
                                                    number_format($producto['Subtotal'], 0, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-sm text-center text-gray-500">No hay ventas registradas para
                                esta fecha</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($this->totalPages() > 1)
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="previousPage"
                                class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                                {{ $currentPage <= 1 ? 'disabled' : '' }}
                            >
                                Anterior
                            </button>
                            @for ($i = 1; $i <= $this->totalPages(); $i++)
                                <button
                                    wire:click="goToPage({{ $i }})"
                                    class="px-3 py-1 text-sm font-medium rounded-md {{ $currentPage === $i ? 'bg-primary-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50' }}"
                                >
                                    {{ $i }}
                                </button>
                            @endfor
                            <button
                                wire:click="nextPage"
                                class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                                {{ $currentPage >= $this->totalPages() ? 'disabled' : '' }}
                            >
                                Siguiente
                            </button>
                        </div>
                        <div class="text-sm text-gray-500">
                            Página {{ $currentPage }} de {{ $this->totalPages() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
    <style>
        [x-cloak] { display: none !important; }
        .rotate-90 { transform: rotate(90deg); }
    </style>
    @endpush
</x-filament-panels::page>
