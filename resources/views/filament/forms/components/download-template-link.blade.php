<div class="flex flex-col gap-2 py-2">
    <div class="text-sm text-gray-500">
        Descarga una plantilla con el formato correcto para importar productos. La primera fila contiene los nombres de columnas, la segunda indica si cada campo es obligatorio u opcional, y las siguientes filas muestran ejemplos.
    </div>
    <a href="{{ route('product.template.download') }}" class="flex items-center text-primary-600 hover:text-primary-500 font-medium" target="_blank">
        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Descargar plantilla de ejemplo
    </a>
</div>
