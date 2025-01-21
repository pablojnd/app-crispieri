<div class="p-4 bg-white rounded-lg shadow" x-data="{ tipoFiltro: @entangle('tipoFiltro') }">
    <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block mb-1 text-xs font-medium text-gray-700">Sucursal</label>
            <select wire:model="sucursal" class="block w-full border-gray-300 rounded-lg shadow-sm">
                <option value="GALPON">Galpón</option>
                <option value="VICTORIA">Victoria</option>
                <option value="LA_TORRE">La Torre</option>
                <option value="WEB">Web</option>
            </select>
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block mb-1 text-xs font-medium text-gray-700">Tipo de Consulta</label>
            <select x-model="tipoFiltro" class="block w-full border-gray-300 rounded-lg shadow-sm">
                <option value="fecha">Fecha Específica</option>
                <option value="rango">Rango de Fechas</option>
            </select>
        </div>

        <div class="flex-1 min-w-[200px]">
            <template x-if="tipoFiltro === 'fecha'">
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700">Fecha</label>
                    <input type="date" wire:model="fecha" class="block w-full border-gray-300 rounded-lg shadow-sm">
                </div>
            </template>
            <template x-if="tipoFiltro === 'rango'">
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700">Rango de Fechas</label>
                    <div class="flex gap-2">
                        <input type="date" wire:model="fechaInicio" class="w-full border-gray-300 rounded-lg shadow-sm">
                        <input type="date" wire:model="fechaFin" class="w-full border-gray-300 rounded-lg shadow-sm">
                    </div>
                </div>
            </template>
        </div>

        <div class="flex items-end">
            <button
                wire:click="actualizarDatos"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                Actualizar
            </button>
        </div>
    </div>
</div>
