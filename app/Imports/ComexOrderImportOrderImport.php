<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Country;
use App\Models\Provider;
use App\Enums\TransportType;
use Illuminate\Support\Collection;
use App\Models\ComexImportOrder;
use App\Enums\ImportOrderStatus;
use Filament\Facades\Filament;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\DB;

class ComexOrderImportOrderImport implements ToCollection, WithHeadingRow
{
    private $tenant;

    public function __construct()
    {
        $this->tenant = Filament::getTenant();
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (empty($row['proveedor']) || empty($row['pais_origen'])) {
                    continue;
                }

                $provider = Provider::firstOrCreate(
                    ['name' => trim($row['proveedor'])],
                    ['store_id' => $this->tenant->id]
                );

                $country = Country::firstOrCreate(
                    ['country_name' => trim($row['pais_origen'])]
                );

                ComexImportOrder::create([
                    'store_id' => $this->tenant->id,
                    'provider_id' => $provider->id,
                    'origin_country_id' => $country->id,
                    'reference_number' => ComexImportOrder::generateReferenceNumber(),
                    'external_reference' => $row['referencia_externa'] ?? null,
                    'sve_registration_number' => $row['numero_registro_sve'] ?? null,
                    'type' => TransportType::from($row['tipo_transporte'] ?? 'sea'),
                    'status' => ImportOrderStatus::DRAFT,
                    'order_date' => $row['fecha_orden'],
                    'estimated_departure' => $row['fecha_salida_estimada'],
                    'estimated_arrival' => $row['fecha_llegada_estimada'],
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // private function parseDate($date)
    // {
    //     if (!$date) return now();
    //     return Carbon::createFromFormat('d/m/Y', $date)->startOfDay();
    // }
}
