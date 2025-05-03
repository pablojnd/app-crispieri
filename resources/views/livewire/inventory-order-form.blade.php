<div>
    <div class="space-y-6">
        <div class="flex justify-between">
            <h1 class="text-2xl font-bold">{{ $orderId ? 'Editar Pedido' : 'Nuevo Pedido' }}</h1>

            <div class="flex space-x-2">
                <x-filament::button
                    wire:click="saveAsDraft"
                    color="gray"
                >
                    Guardar como borrador
                </x-filament::button>

                <x-filament::button
                    wire:click="savePending"
                    color="success"
                >
                    Guardar y enviar
                </x-filament::button>
            </div>
        </div>

        <!-- Sección de información del cliente -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Información del Cliente</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.label for="orderNumber">Número de Pedido</x-filament::input.label>
                        <x-filament::input
                            wire:model="orderNumber"
                            id="orderNumber"
                            readonly
                            disabled
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.label for="reference">Referencia (opcional)</x-filament::input.label>
                        <x-filament::input
                            wire:model="reference"
                            id="reference"
                            placeholder="Referencia interna o externa"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.label for="clientName" required>Nombre del Cliente</x-filament::input.label>
                        <x-filament::input
                            wire:model="clientName"
                            id="clientName"
                            placeholder="Nombre completo del cliente"
                            required
                        />
                        @error('clientName') <span class="text-danger-500 text-xs">{{ $message }}</span> @enderror
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.label for="clientEmail">Email</x-filament::input.label>
                        <x-filament::input
                            wire:model="clientEmail"
                            id="clientEmail"
                            type="email"
                            placeholder="correo@ejemplo.com"
                        />
                        @error('clientEmail') <span class="text-danger-500 text-xs">{{ $message }}</span> @enderror
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.label for="clientPhone">Teléfono</x-filament::input.label>
                        <x-filament::input
                            wire:model="clientPhone"
                            id="clientPhone"
                            placeholder="+56912345678"
                        />
                        @error('clientPhone') <span class="text-danger-500 text-xs">{{ $message }}</span> @enderror
                    </x-filament::input.wrapper>
                </div>

                <div class="md:col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input.label for="notes">Notas</x-filament::input.label>
                        <x-filament::textarea
                            wire:model="notes"
                            id="notes"
                            placeholder="Instrucciones especiales o información adicional"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>

        <!-- Sección de búsqueda de productos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Buscar Productos</h2>

            <div class="flex items-center space-x-2 mb-4">
                <div class="flex-grow">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            wire:model="search"
                            wire:keydown.enter="searchProducts"
                            placeholder="Buscar por código o zeta"
                        />
                        @if($loading)
                            <div class="absolute right-3 top-2.5">
                                <x-filament::loading-indicator class="h-5 w-5" />
                            </div>
                        @endif
                    </x-filament::input.wrapper>
                </div>

                <x-filament::button
                    wire:click="searchProducts"
                    wire:loading.attr="disabled"
                >
                    Buscar
                </x-filament::button>
            </div>

            @if(count($searchResults) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-2">Código</th>
                                <th scope="col" class="px-3 py-2">Zeta</th>
                                <th scope="col" class="px-3 py-2">Descripción</th>
                                <th scope="col" class="px-3 py-2 text-center">Saldo</th>
                                <th scope="col" class="px-3 py-2 text-center">Costo</th>
                                <th scope="col" class="px-3 py-2 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($searchResults as $index => $item)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $item['Código_Artículo'] }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $item['Zeta_Articulo'] }}</td>
                                    <td class="px-3 py-2 max-w-xs truncate">{{ $item['Descripción_Artículo'] }}</td>
                                    <td class="px-3 py-2 text-center font-medium {{ $item['Saldo_Disponible'] <= 0 ? 'text-danger-600' : 'text-primary-600' }}">
                                        {{ number_format($item['Saldo_Disponible'], 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-center">${{ number_format($item['Costo'], 2) }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <x-filament::button
                                            size="xs"
                                            wire:click="selectItem({{ $index }})"
                                            color="primary"
                                        >
                                            Seleccionar
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif(!empty($search) && !$loading)
                <div class="p-6 text-center bg-gray-50 rounded-lg">
                    <p class="text-gray-500">No se encontraron resultados para "{{ $search }}"</p>
                </div>
            @endif

            @if($selectedItem)
                <div class="mt-6 p-4 border rounded-lg bg-blue-50 border-blue-200">
                    <h3 class="font-medium text-blue-800 mb-2">Producto seleccionado</h3>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <span class="block text-xs text-blue-600">Código</span>
                            <span class="font-medium">{{ $selectedItem['Código_Artículo'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-blue-600">Descripción</span>
                            <span class="font-medium">{{ $selectedItem['Descripción_Artículo'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-blue-600">Saldo Disponible</span>
                            <span class="font-medium">{{ number_format($selectedItem['Saldo_Disponible'], 2) }}</span>
                        </div>
                    </div>

                    <div class="flex items-end space-x-4">
                        <div>
                            <x-filament::input.wrapper>
                                <x-filament::input.label for="quantity">Cantidad a pedir</x-filament::input.label>
                                <x-filament::input
                                    wire:model="quantity"
                                    id="quantity"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ $selectedItem['Saldo_Disponible'] }}"
                                />
                                @error('quantity') <span class="text-danger-500 text-xs">{{ $message }}</span> @enderror
                            </x-filament::input.wrapper>
                        </div>

                        <x-filament::button
                            wire:click="addToCart"
                        >
                            Agregar al pedido
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sección de items del pedido -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Detalle del Pedido</h2>

            @if(count($cartItems) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-2">Código</th>
                                <th scope="col" class="px-3 py-2">Zeta</th>
                                <th scope="col" class="px-3 py-2">Descripción</th>
                                <th scope="col" class="px-3 py-2 text-center">Cantidad</th>
                                <th scope="col" class="px-3 py-2 text-center">Precio Unit.</th>
                                <th scope="col" class="px-3 py-2 text-center">Total</th>
                                <th scope="col" class="px-3 py-2 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cartItems as $index => $item)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $item['product_code'] }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $item['zeta_code'] }}</td>
                                    <td class="px-3 py-2 max-w-xs truncate">{{ $item['description'] }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <x-filament::input
                                            type="number"
                                            wire:model.live="cartItems.{{ $index }}.requested_quantity"
                                            wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                            step="0.01"
                                            min="0.01"
                                            class="w-20 text-center"
                                        />
                                    </td>
                                    <td class="px-3 py-2 text-center">${{ number_format($item['unit_price'], 2) }}</td>
                                    <td class="px-3 py-2 text-center font-medium">${{ number_format($item['total_price'], 2) }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <x-filament::button
                                            size="xs"
                                            color="danger"
                                            wire:click="removeItem({{ $index }})"
                                        >
                                            Eliminar
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Fila de totales -->
                            <tr class="bg-gray-50 font-medium">
                                <td colspan="5" class="px-3 py-3 text-right">Total del Pedido:</td>
                                <td class="px-3 py-3 text-center">${{ number_format(collect($cartItems)->sum('total_price'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center bg-gray-50 rounded-lg">
                    <p class="text-gray-500">No hay productos en el pedido. Use la búsqueda para agregar productos.</p>
                </div>
            @endif

            @error('cartItems')
                <div class="mt-2 text-danger-500 text-sm">{{ $message }}</div>
            @enderror
        </div>

        <!-- Botones de acción -->
        <div class="flex justify-end space-x-2">
            <x-filament::button
                wire:click="saveAsDraft"
                color="gray"
            >
                Guardar como borrador
            </x-filament::button>

            <x-filament::button
                wire:click="savePending"
                color="success"
            >
                Guardar y enviar
            </x-filament::button>
        </div>
    </div>
</div>
