<x-filament-panels::page>
    <div class="space-y-4">
        <livewire:dashboard.filter-controls />

        <div class="flex flex-wrap gap-4">
            <livewire:dashboard.facturas-card />
            <livewire:dashboard.boletas-card />
            <livewire:dashboard.srf-card />
            <livewire:dashboard.total-ventas-card />
        </div>

        <livewire:dashboard.ventas-table />
    </div>
</x-filament-panels::page>
