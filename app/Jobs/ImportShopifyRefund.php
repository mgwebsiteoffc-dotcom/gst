<?php

namespace App\Jobs;

use App\Models\ShopifyStore;
use App\Models\ShopifySyncLog;
use App\Services\Shopify\ShopifyOrderImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportShopifyRefund implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $storeId, public array $payload, public ?int $logId = null) {}

    public function handle(ShopifyOrderImporter $importer): void
    {
        $store = ShopifyStore::withoutGlobalScope('tenant')->with('tenant')->findOrFail($this->storeId);
        $log = $this->logId ? ShopifySyncLog::find($this->logId) : null;

        $note = $importer->importRefund($store, $this->payload);
        $log?->update([
            'status' => $note ? 'processed' : 'skipped',
            'message' => $note ? 'Credit note '.$note->number : 'No matching invoice',
        ]);
    }
}
