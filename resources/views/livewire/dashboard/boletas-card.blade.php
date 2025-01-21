<div class="flex-1 min-w-[250px] bg-white rounded-lg shadow">
    <div class="flex items-center justify-between p-2 border-b">
        <h3 class="text-sm font-medium text-gray-500">Boletas</h3>
        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-purple-700 bg-purple-100 rounded-full">
            {{ $data['CantidadBoletas'] ?? 0 }} docs
        </span>
    </div>
    <div class="p-3 space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Total:</span>
            <span class="text-lg font-bold text-primary-600">${{ number_format($data['MontoTotalBoletas'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Galpón:</span>
            <span class="text-sm text-primary-600">${{ number_format($data['BoletasGalpon'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Otros:</span>
            <span class="text-sm text-primary-600">${{ number_format($data['BoletasOtros'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Nulas:</span>
            <span class="text-sm font-medium text-red-600">{{ $data['BoletasAnuladas'] ?? 0 }}</span>
        </div>
        <div class="flex items-center justify-between pt-2 border-t">
            <span class="text-xs text-gray-500">Promedio:</span>
            <span class="text-sm text-gray-600">${{ number_format(($data['MontoTotalBoletas'] ?? 0) / max(($data['CantidadBoletas'] ?? 1), 1), 0, ',', '.') }}</span>
        </div>
    </div>
</div>
