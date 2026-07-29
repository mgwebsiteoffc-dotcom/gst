<?php

namespace App\Services\Einvoice;

use App\Models\Document;
use App\Models\Einvoice;
use App\Models\EwayBill;
use App\Services\Einvoice\Contracts\EinvoiceGateway;
use Illuminate\Support\Carbon;
use RuntimeException;

class EinvoiceService
{
    public function __construct(protected EinvoiceGatewayResolver $resolver) {}

    /** Gateway is resolved per document so each tenant uses its own NIC credentials. */
    protected function gatewayFor(Document $document): EinvoiceGateway
    {
        return $this->resolver->for($document->tenant);
    }

    public function generate(Document $document): Einvoice
    {
        if (! $document->isGst()) {
            throw new RuntimeException('Non-GST invoices cannot be reported to the IRP.');
        }
        if (! $document->isEinvoiceApplicable()) {
            throw new RuntimeException('e-Invoice is not applicable for this document.');
        }

        // The IRP rejects documents older than the reporting window.
        $age = $document->issue_date->diffInDays(now());
        if ($age > config('einvoice.reporting_window_days')) {
            throw new RuntimeException('This document is '.$age.' days old and is outside the '
                .config('einvoice.reporting_window_days').'-day IRP reporting window.');
        }
        if ($document->einvoice?->isGenerated()) {
            return $document->einvoice;
        }

        $document->loadMissing(['tenant', 'party', 'lines.item']);

        $result = $this->gatewayFor($document)->generateIrn($document);

        $einvoice = Einvoice::updateOrCreate(
            ['document_id' => $document->id],
            [
                'tenant_id' => $document->tenant_id,
                'status' => $result['success'] ? 'generated' : 'failed',
                'irn' => $result['irn'] ?? null,
                'ack_no' => $result['ack_no'] ?? null,
                'ack_date' => isset($result['ack_date']) ? Carbon::parse($result['ack_date']) : null,
                'signed_invoice' => $result['signed_invoice'] ?? null,
                'signed_qr_code' => $result['signed_qr_code'] ?? null,
                'request_payload' => $result['request'] ?? null,
                'response_payload' => $result['response'] ?? ($result['errors'] ?? null),
            ]
        );

        if (! $result['success']) {
            throw new RuntimeException('IRP rejected the invoice: '.json_encode($result['errors'] ?? []));
        }

        return $einvoice;
    }

    /** IRN cancellation is only allowed within 24 hours of generation. */
    public function cancel(Document $document, string $reasonCode = '2', string $remarks = 'Data entry mistake'): Einvoice
    {
        $einvoice = $document->einvoice;
        if (! $einvoice?->irn) {
            throw new RuntimeException('No IRN found for this document.');
        }
        if ($einvoice->ack_date && $einvoice->ack_date->diffInHours(now()) > 24) {
            throw new RuntimeException('IRN can only be cancelled within 24 hours. Issue a credit note instead.');
        }

        $result = $this->gatewayFor($document)->cancelIrn($einvoice->irn, $reasonCode, $remarks);
        if ($result['success']) {
            $einvoice->update(['status' => 'cancelled', 'cancel_reason' => $remarks]);
        }

        return $einvoice->refresh();
    }

    public function generateEwayBill(Document $document, array $transport): EwayBill
    {
        $document->loadMissing(['tenant', 'party', 'lines', 'einvoice']);

        $result = $this->gatewayFor($document)->generateEwayBill($document, $transport);

        return EwayBill::updateOrCreate(
            ['document_id' => $document->id],
            [
                'tenant_id' => $document->tenant_id,
                'status' => $result['success'] ? 'generated' : 'failed',
                'ewb_no' => $result['ewb_no'] ?? null,
                'ewb_date' => isset($result['ewb_date']) ? Carbon::parse($result['ewb_date']) : null,
                'valid_until' => isset($result['valid_until']) ? Carbon::parse($result['valid_until']) : null,
                'transport_mode' => $transport['transport_mode'] ?? null,
                'vehicle_no' => $transport['vehicle_no'] ?? null,
                'transporter_id' => $transport['transporter_id'] ?? null,
                'transporter_name' => $transport['transporter_name'] ?? null,
                'distance_km' => $transport['distance_km'] ?? 0,
                'response_payload' => $result['response'] ?? null,
            ]
        );
    }
}
