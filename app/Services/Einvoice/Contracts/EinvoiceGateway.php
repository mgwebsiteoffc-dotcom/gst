<?php

namespace App\Services\Einvoice\Contracts;

use App\Models\Document;

interface EinvoiceGateway
{
    public function generateIrn(Document $document): array;

    public function cancelIrn(string $irn, string $reasonCode, string $remarks): array;

    public function generateEwayBill(Document $document, array $transport): array;

    public function cancelEwayBill(string $ewbNo, string $reasonCode, string $remarks): array;
}
