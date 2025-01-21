<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DailyCash extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.daily-cash';
    protected static ?string $title = 'Panel de Control de Ventas';

    public $fecha;
    public $tipoFiltro = 'fecha';
    public $fechaInicio;
    public $fechaFin;

    public function mount()
    {
        $this->fecha = Carbon::today()->format('Y-m-d');
        $this->fechaInicio = $this->fecha;
        $this->fechaFin = $this->fecha;
        $this->dispatch('filtrosActualizados', [
            'tipoFiltro' => $this->tipoFiltro,
            'fecha' => $this->fecha
        ]);
    }

    public function updatedFecha()
    {
        $this->actualizarFiltros();
    }

    public function updatedTipoFiltro()
    {
        $this->actualizarFiltros();
    }

    public function updatedFechaInicio()
    {
        if ($this->tipoFiltro === 'rango') {
            $this->actualizarFiltros();
        }
    }

    public function updatedFechaFin()
    {
        if ($this->tipoFiltro === 'rango') {
            $this->actualizarFiltros();
        }
    }

    protected function actualizarFiltros()
    {
        $filtros = [
            'tipoFiltro' => $this->tipoFiltro
        ];

        if ($this->tipoFiltro === 'fecha') {
            $filtros['fecha'] = $this->fecha;
        } else {
            $filtros['fechaInicio'] = $this->fechaInicio;
            $filtros['fechaFin'] = $this->fechaFin;
        }

        $this->dispatch('filtrosActualizados', $filtros);
    }
}
