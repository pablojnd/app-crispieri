<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\{
    Bank,
    Country,
    Provider,
    ComexDocument,
    ComexImportOrder,
    ComexDocumentPayment,
    BankCode,
    Currency
};
use App\Enums\{
    TransportType,
    ImportOrderStatus,
    DocumentType,
    DocumentClauseType,
    PaymentStatus
};
use App\Services\DocumentProcessingService;
use Illuminate\Support\Collection;
use Filament\Facades\Filament;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, SkipsEmptyRows};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComexOrderImportOrderImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private $tenant;
    private $documentService;
    private $errors = [];

    public function __construct()
    {
        $this->tenant = Filament::getTenant();
        $this->documentService = new DocumentProcessingService();
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            $groupedRows = $this->groupRowsByOrder($rows);

            foreach ($groupedRows as $orderReference => $orderRows) {
                $this->processOrderGroup($orderReference, $orderRows);
            }

            if (!empty($this->errors)) {
                throw new \Exception("Errores durante la importación: " . json_encode($this->errors));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en importación: ' . $e->getMessage());
            throw $e;
        }
    }

    private function groupRowsByOrder(Collection $rows): Collection
    {
        return $rows->groupBy('referencia_externa');
    }

    private function processOrderGroup(string $orderReference, Collection $rows)
    {
        try {
            $firstRow = $rows->first();
            if (!$this->isValidRow($firstRow)) {
                $this->errors[] = "Fila inválida para referencia: {$orderReference}";
                return;
            }

            $importOrder = $this->createOrUpdateImportOrder($firstRow);

            foreach ($rows as $row) {
                if (!empty($row['documento_numero'])) {
                    $document = $this->documentService->createOrUpdateDocument($importOrder, $row);

                    if (!empty($row['pago_monto'])) {
                        $this->documentService->createOrUpdatePayment($document, $row);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = "Error procesando orden {$orderReference}: " . $e->getMessage();
        }
    }

    private function createOrUpdateImportOrder($row): ComexImportOrder
    {
        $provider = $this->findOrCreateProvider($row['proveedor']);
        $country = $this->findOrCreateCountry($row['pais_origen']);

        return ComexImportOrder::updateOrCreate(
            [
                'store_id' => $this->tenant->id,
                'external_reference' => $row['referencia_externa']
            ],
            [
                'provider_id' => $provider->id,
                'origin_country_id' => $country->id,
                'reference_number' => ComexImportOrder::generateReferenceNumber(),
                'sve_registration_number' => $row['numero_registro_sve'] ?? null,
                'type' => TransportType::from($row['tipo_transporte'] ?? 'sea'),
                'status' => ImportOrderStatus::DRAFT,
                'order_date' => $this->parseDate($row['fecha_orden']),
                'estimated_departure' => $this->parseDate($row['fecha_salida_estimada']),
                'estimated_arrival' => $this->parseDate($row['fecha_llegada_estimada']),
            ]
        );
    }

    private function findOrCreateProvider(string $name)
    {
        return Provider::firstOrCreate(
            ['name' => trim($name)],
            ['store_id' => $this->tenant->id]
        );
    }

    private function findOrCreateCountry(string $name)
    {
        return Country::firstOrCreate(['country_name' => trim($name)]);
    }

    private function isValidRow($row): bool
    {
        return !empty($row['proveedor']) &&
            !empty($row['pais_origen']) &&
            !empty($row['referencia_externa']);
    }

    private function parseDate($date)
    {
        if (!$date) return now();
        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Exception $e) {
            return now();
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
