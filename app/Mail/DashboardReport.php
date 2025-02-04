<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DashboardReport extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;
    protected $fecha;
    protected $excelFile;

    public function __construct($data, $fecha, $excelFile)
    {
        $this->data = $data;
        $this->fecha = $fecha;
        $this->excelFile = $excelFile;
    }

    public function build()
    {
        if (!file_exists($this->excelFile)) {
            throw new \Exception('El archivo Excel no existe en: ' . $this->excelFile);
        }

        return $this->view('emails.dashboard-report')
            ->subject("Reporte Dashboard - {$this->fecha}")
            ->with([
                'data' => $this->data,
                'fecha' => $this->fecha
            ])
            ->attach($this->excelFile, [
                'as' => "reporte_dashboard_{$this->fecha}.xlsx",
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]);
    }
}
