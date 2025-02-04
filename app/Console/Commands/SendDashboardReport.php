<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exports\DashboardExport;
use App\Mail\DashboardReport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendDashboardReport extends Command
{
    protected $signature = 'dashboard:send-report {--email=} {--fecha=}';
    protected $description = 'Genera y envía el reporte del dashboard por correo';

    public function handle()
    {
        $fecha = $this->option('fecha') ?? now()->format('Y-m-d');
        $email = $this->option('email') ?? config('mail.dashboard_report.to');

        // Recolectar datos de los componentes
        $totalVentas = app()->make('App\Livewire\Dashboard\TotalVentasCard');
        $srf = app()->make('App\Livewire\Dashboard\SrfCard');
        $facturas = app()->make('App\Livewire\Dashboard\FacturasCard');
        $boletas = app()->make('App\Livewire\Dashboard\BoletasCard');

        // Actualizar datos
        $filtros = ['tipoFiltro' => 'fecha', 'fecha' => $fecha];
        $totalVentas->actualizarDatos($filtros);
        $srf->actualizarDatos($filtros);
        $facturas->actualizarDatos($filtros);
        $boletas->actualizarDatos($filtros);

        $data = [
            'total' => $totalVentas->data,
            'srf' => $srf->data,
            'facturas' => $facturas->data,
            'boletas' => $boletas->data,
        ];

        // Generar Excel
        $fileName = "dashboard_report_{$fecha}.xlsx";
        $filePath = storage_path("app/temp/{$fileName}");
        Excel::store(new DashboardExport($data), "temp/{$fileName}");

        // Enviar correo
        Mail::to($email)->send(new DashboardReport($data, $fecha, $filePath));

        // Limpiar archivo temporal
        Storage::delete("temp/{$fileName}");

        $this->info('Reporte enviado exitosamente');
    }
}
