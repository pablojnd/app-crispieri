<div class="flex-1 min-w-[250px] bg-white rounded-lg shadow">
    <div class="flex items-center justify-between p-2 border-b">
        <h3 class="text-sm font-medium text-gray-500">SRF</h3>
        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-orange-700 bg-orange-100 rounded-full">
            {{ count($data['clientes'] ?? []) }} clientes
        </span>
    </div>
    <div class="p-3 space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Total:</span>
            <span class="text-lg font-bold text-primary-600">${{ number_format($data['totalGeneral'] ?? 0, 0, ',', '.') }}</span>
        </div>
        @foreach($data['clientes'] ?? [] as $cliente)
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ $cliente['cliente'] }}:</span>
                <span class="text-sm text-primary-600">${{ number_format($cliente['total'], 0, ',', '.') }}</span>
            </div>
        @endforeach
    </div>
</div>
