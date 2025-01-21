<div class="overflow-hidden bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Detalle de Ventas</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-10"></th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">N° Doc</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Cliente</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Vendedor</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Pago</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($paginatedDocumentos as $index => $doc)
                    <tr class="transition-colors hover:bg-gray-50 {{ $doc['EstadoDocumento'] === 'ANULADA' ? 'bg-red-50' : '' }}">
                        <td class="px-3">
                            <button
                                wire:click="toggleRow({{ $index }})"
                                class="p-2 transition-transform duration-200 rounded-full hover:bg-gray-100 focus:outline-none {{ $doc['EstadoDocumento'] === 'ANULADA' ? 'text-red-600' : '' }}"
                            >
                                <svg
                                    class="w-5 h-5 transform transition-transform duration-200 ease-in-out {{ $this->isRowExpanded($index) ? 'rotate-90' : '' }}"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
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
                    @if($this->isRowExpanded($index))
                        <tr class="bg-gray-50">
                            <td colspan="7" class="p-0">
                                <div class="overflow-hidden">
                                    <table class="w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-500">Producto</th>
                                                <th class="px-6 py-2 text-xs font-medium tracking-wider text-center text-gray-500">Cantidad</th>
                                                <th class="px-6 py-2 text-xs font-medium tracking-wider text-right text-gray-500">Precio Unit.</th>
                                                <th class="px-6 py-2 text-xs font-medium tracking-wider text-right text-gray-500">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($doc['Productos'] as $producto)
                                                <tr class="hover:bg-gray-100">
                                                    <td class="px-6 py-2 text-sm text-gray-900">{{ $producto['Producto'] }}</td>
                                                    <td class="px-6 py-2 text-sm text-center text-gray-900">{{ $producto['CantidadVendida'] }}</td>
                                                    <td class="px-6 py-2 text-sm text-right text-gray-900">${{ number_format($producto['Precio'], 0, ',', '.') }}</td>
                                                    <td class="px-6 py-2 text-sm font-medium text-right text-gray-900">${{ number_format($producto['Subtotal'], 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-sm text-center text-gray-500">
                            No hay ventas registradas para esta fecha
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($totalPages > 1)
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button
                        wire:click="setPage({{ max(1, $currentPage - 1) }})"
                        class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                        {{ $currentPage <= 1 ? 'disabled' : '' }}
                    >
                        Anterior
                    </button>
                    @for ($i = 1; $i <= $totalPages; $i++)
                        <button
                            wire:click="setPage({{ $i }})"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ $currentPage === $i ? 'bg-primary-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50' }}"
                        >
                            {{ $i }}
                        </button>
                    @endfor
                    <button
                        wire:click="setPage({{ min($totalPages, $currentPage + 1) }})"
                        class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                        {{ $currentPage >= $totalPages ? 'disabled' : '' }}
                    >
                        Siguiente
                    </button>
                </div>
                <div class="text-sm text-gray-500">
                    Página {{ $currentPage }} de {{ $totalPages }}
                </div>
            </div>
        </div>
    @endif
</div>
