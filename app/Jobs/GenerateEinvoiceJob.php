<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\Einvoice\EinvoiceService;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateEinvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120, 600];

    public function __construct(public int $documentId) {}

    public function handle(EinvoiceService $service): void
    {
        $document = Document::withoutGlobalScope('tenant')->with('tenant', 'party', 'lines.item')->findOrFail($this->documentId);

        TenantContext::run($document->tenant, fn () => $service->generate($document));
    }
}
