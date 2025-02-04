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
        $emails = $this->getRecipients();

        if (empty($emails)) {
            $this->error('No hay destinatarios configurados para el envío del reporte.');
            return 1;
        }

        try {
            // Crear directorio temporal si no existe
            if (!Storage::exists('temp')) {
                Storage::makeDirectory('temp');
            }

            // Recolectar datos
            $data = $this->collectData($fecha);

            // Generar Excel
            $fileName = "dashboard_report_{$fecha}.xlsx";
            $relativePath = "temp/{$fileName}";

            // Generar el archivo
            Excel::store(new DashboardExport($data), $relativePath);

            // Obtener la ruta completa del archivo
            $fullPath = Storage::disk('local')->path($relativePath);

            // Verificar que el archivo existe
            if (!file_exists($fullPath)) {
                throw new \Exception("Error al generar el archivo Excel");
            }

            // Debug info
            $this->info("Archivo generado en: {$fullPath}");

            // Enviar correo
            Mail::to($emails)->send(new DashboardReport($data, $fecha, $fullPath));

            $this->info('Reporte enviado exitosamente a: ' . implode(', ', $emails));
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        } finally {
            // Limpiar archivo temporal si existe
            if (isset($relativePath) && Storage::exists($relativePath)) {
                Storage::delete($relativePath);
            }
        }

        return 0;
    }

    private function getRecipients(): array
    {
        // Primero intentar obtener email del comando
        if ($email = $this->option('email')) {
            return array_filter(explode(',', $email));
        }

        // Si no hay email en el comando, obtener de la configuración
        $configEmails = config('dashboard.report.emails', []);

        // Si no hay emails configurados, usar email por defecto
        if (empty($configEmails)) {
            $defaultEmail = config('dashboard.report.email', 'crispieri94@gmail.com');
            return [$defaultEmail];
        }

        return $configEmails;
    }

    private function collectData($fecha): array
    {
        // Inicializar componentes
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

        return [
            'total' => $totalVentas->data,
            'srf' => $srf->data,
            'facturas' => $facturas->data,
            'boletas' => $boletas->data,
        ];
    }
}
