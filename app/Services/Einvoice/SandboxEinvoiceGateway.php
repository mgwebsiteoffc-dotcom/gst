<?php

namespace App\Services\Einvoice;

use App\Models\Document;
use App\Services\Einvoice\Contracts\EinvoiceGateway;
use Illuminate\Support\Str;

/** Deterministic fake IRP for local dev, demos and tests — no network calls. */
class SandboxEinvoiceGateway implements EinvoiceGateway
{
    public function __construct(protected EinvoicePayloadBuilder $builder) {}

    public function generateIrn(Document $document): array
    {
        $payload = $this->builder->build($document);
        $irn = hash('sha256', $document->tenant->gstin.$document->number.$document->doc_type.$document->issue_date);

        $qr = base64_encode(json_encode([
            'SellerGstin' => $document->tenant->gstin,
            'BuyerGstin' => $document->party?->gstin ?: 'URP',
            'DocNo' => $document->number,
            'DocTyp' => 'INV',
            'DocDt' => $document->issue_date->format('d/m/Y'),
            'TotInvVal' => (float) $document->grand_total,
            'ItemCnt' => $document->lines->count(),
            'MainHsnCode' => $document->lines->first()?->hsn_sac,
            'Irn' => $irn,
            'IrnDt' => now()->format('Y-m-d H:i:s'),
        ]));

        return [
            'success' => true,
            'request' => $payload,
            'response' => ['Status' => 1, 'sandbox' => true],
            'irn' => $irn,
            'ack_no' => (string) random_int(100000000000000, 999999999999999),
            'ack_date' => now()->format('Y-m-d H:i:s'),
            'signed_invoice' => 'SANDBOX.'.Str::random(40),
            'signed_qr_code' => $qr,
        ];
    }

    public function cancelIrn(string $irn, string $reasonCode, string $remarks): array
    {
        return ['success' => true, 'response' => ['Status' => 1, 'Irn' => $irn, 'CancelDate' => now()->toDateTimeString()]];
    }

    public function generateEwayBill(Document $document, array $transport): array
    {
        return [
            'success' => true,
            'response' => ['Status' => 1, 'sandbox' => true],
            'ewb_no' => (string) random_int(100000000000, 999999999999),
            'ewb_date' => now()->format('Y-m-d H:i:s'),
            'valid_until' => now()->addDays(max(1, (int) ceil(((float) ($transport['distance_km'] ?? 100)) / 200)))->format('Y-m-d H:i:s'),
        ];
    }

    public function cancelEwayBill(string $ewbNo, string $reasonCode, string $remarks): array
    {
        return ['success' => true, 'response' => ['Status' => 1, 'ewbNo' => $ewbNo]];
    }
}
