<?php

namespace App\Jobs;

use App\Models\ShopifyStore;
use App\Models\ShopifySyncLog;
use App\Services\Shopify\ShopifyOrderImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportShopifyOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $storeId, public array $payload, public ?int $logId = null) {}

    public function handle(ShopifyOrderImporter $importer): void
    {
        $store = ShopifyStore::withoutGlobalScope('tenant')->with('tenant')->findOrFail($this->storeId);
        $log = $this->logId ? ShopifySyncLog::find($this->logId) : null;

        try {
            $document = $importer->import($store, $this->payload);
            $log?->update(['status' => 'processed', 'message' => 'Invoice '.$document->number]);
        } catch (\Throwable $e) {
            $log?->update(['status' => 'failed', 'message' => $e->getMessage()]);
            throw $e;
        }
    }
}
