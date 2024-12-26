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
use Illuminate\Support\Collection;
use Filament\Facades\Filament;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
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
                if (!$this->isValidRow($row)) {
                    continue;
                }

                $importOrder = $this->createImportOrder($row);

                if (!empty($row['documento_numero'])) {
                    $document = $this->createDocument($importOrder, $row);

                    if (!empty($row['pago_monto'])) {
                        $this->createPayment($document, $row);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function isValidRow($row): bool
    {
        return !empty($row['proveedor']) && !empty($row['pais_origen']);
    }

    private function createImportOrder($row): ComexImportOrder
    {
        $provider = Provider::firstOrCreate(
            ['name' => trim($row['proveedor'])],
            ['store_id' => $this->tenant->id]
        );

        $country = Country::firstOrCreate(
            ['country_name' => trim($row['pais_origen'])]
        );

        return ComexImportOrder::create([
            'store_id' => $this->tenant->id,
            'provider_id' => $provider->id,
            'origin_country_id' => $country->id,
            'reference_number' => ComexImportOrder::generateReferenceNumber(),
            'external_reference' => $row['referencia_externa'] ?? null,
            'sve_registration_number' => $row['numero_registro_sve'] ?? null,
            'type' => TransportType::from($row['tipo_transporte'] ?? 'sea'),
            'status' => ImportOrderStatus::DRAFT,
            'order_date' => $this->parseDate($row['fecha_orden']),
            'estimated_departure' => $this->parseDate($row['fecha_salida_estimada']),
            'estimated_arrival' => $this->parseDate($row['fecha_llegada_estimada']),
        ]);
    }

    private function createDocument(ComexImportOrder $importOrder, $row): ComexDocument
    {
        return ComexDocument::create([
            'store_id' => $this->tenant->id,
            'import_order_id' => $importOrder->id,
            'document_number' => $row['documento_numero'],
            'document_type' => DocumentType::from($row['documento_tipo'] ?? 'invoice'),
            'document_clause' => DocumentClauseType::from($row['documento_clausula'] ?? 'cif'),
            'document_date' => $this->parseDate($row['documento_fecha']),
            'fob_total' => $row['documento_fob'] ?? 0,
            'freight_total' => $row['documento_flete'] ?? 0,
            'insurance_total' => $row['documento_seguro'] ?? 0,
            'currency_code' => $row['documento_moneda'] ?? 'USD',
            'notes' => $row['documento_notas'] ?? null
        ]);
    }

    private function createPayment(ComexDocument $document, $row): ComexDocumentPayment
    {
        $bank = null;
        if (!empty($row['pago_cuenta'])) {
            $accountNumber = trim($row['pago_cuenta']);

            // Extraer los primeros 4 caracteres para el código del banco
            $bankCode = BankCode::firstOrCreate(
                ['code' => substr($accountNumber, 0, 4)],
                ['bank_name' => 'BANCO ' . substr($accountNumber, 0, 4)]
            );

            $currency = Currency::where('code', 'USD')->firstOrFail();

            $bank = Bank::firstOrCreate(
                [
                    'store_id' => $this->tenant->id,
                    'bank_code_id' => $bankCode->id,
                    'account_number' => $accountNumber,
                ],
                [
                    'currency_id' => $currency->id,
                    'account_type' => 'checking',
                    'is_active' => true,
                ]
            );
        }

        return ComexDocumentPayment::create([
            'store_id' => $this->tenant->id,
            'document_id' => $document->id,
            'bank_id' => $bank?->id,
            'amount' => $row['pago_monto'],
            'exchange_rate' => $row['pago_tasa_cambio'] ?? 1,
            'payment_status' => PaymentStatus::from($row['pago_estado'] ?? 'completed'),
            'payment_date' => $this->parseDate($row['pago_fecha']),
            'reference_number' => $row['pago_referencia'] ?? null,
            'notes' => $row['pago_notas'] ?? null
        ]);
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
}
