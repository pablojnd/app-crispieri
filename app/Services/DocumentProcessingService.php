<?php

namespace App\Services;

use App\Models\{
    Bank,
    BankCode,
    Currency,
    ComexDocument,
    ComexDocumentPayment,
    ComexImportOrder
};
use App\Enums\{DocumentType, DocumentClauseType, PaymentStatus};
use Filament\Facades\Filament;
use Carbon\Carbon;

class DocumentProcessingService
{
    private $tenant;

    public function __construct()
    {
        $this->tenant = Filament::getTenant();
    }

    public function createOrUpdateDocument(ComexImportOrder $importOrder, array $row): ComexDocument
    {
        return ComexDocument::updateOrCreate(
            [
                'store_id' => $this->tenant->id,
                'import_order_id' => $importOrder->id,
                'document_number' => $row['documento_numero'],
            ],
            [
                'document_type' => DocumentType::from($row['documento_tipo'] ?? 'invoice'),
                'document_clause' => DocumentClauseType::from($row['documento_clausula'] ?? 'cif'),
                'document_date' => $this->parseDate($row['documento_fecha']),
                'fob_total' => $row['documento_fob'] ?? 0,
                'freight_total' => $row['documento_flete'] ?? 0,
                'insurance_total' => $row['documento_seguro'] ?? 0,
                'currency_code' => $row['documento_moneda'] ?? 'USD',
                'notes' => $row['documento_notas'] ?? null
            ]
        );
    }

    public function createOrUpdatePayment(ComexDocument $document, array $row): ComexDocumentPayment
    {
        $bank = $this->findOrCreateBank($row);

        return ComexDocumentPayment::updateOrCreate(
            [
                'store_id' => $this->tenant->id,
                'document_id' => $document->id,
                'reference_number' => $row['pago_referencia'] ?? null,
            ],
            [
                'bank_id' => $bank?->id,
                'amount' => $row['pago_monto'],
                'exchange_rate' => $row['pago_tasa_cambio'] ?? 1,
                'payment_status' => PaymentStatus::from($row['pago_estado'] ?? 'completed'),
                'payment_date' => $this->parseDate($row['pago_fecha']),
                'notes' => $row['pago_notas'] ?? null
            ]
        );
    }

    private function findOrCreateBank(array $row): ?Bank
    {
        if (empty($row['pago_cuenta'])) {
            return null;
        }

        $accountNumber = trim($row['pago_cuenta']);
        $bankCode = $this->findOrCreateBankCode(substr($accountNumber, 0, 4));
        $currency = Currency::where('code', 'USD')->firstOrFail();

        return Bank::firstOrCreate(
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

    private function findOrCreateBankCode(string $code): BankCode
    {
        return BankCode::firstOrCreate(
            ['code' => $code],
            ['bank_name' => 'BANCO ' . $code]
        );
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
