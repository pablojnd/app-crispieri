<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class FilterControls extends Component
{
    public $sucursal = 'GALPON';
    public $tipoFiltro = 'fecha';
    public $fecha;
    public $fechaInicio;
    public $fechaFin;

    protected $listeners = ['refreshData'];

    public function mount()
    {
        $this->fecha = now()->format('Y-m-d');
        $this->fechaInicio = $this->fecha;
        $this->fechaFin = $this->fecha;
    }

    public function actualizarDatos()
    {
        $this->dispatch('filtrosActualizados', [
            'sucursal' => $this->sucursal,
            'tipoFiltro' => $this->tipoFiltro,
            'fecha' => $this->fecha,
            'fechaInicio' => $this->fechaInicio,
            'fechaFin' => $this->fechaFin,
        ]);
    }

    public function render()
    {
        return view('livewire.dashboard.filter-controls');
    }
}
