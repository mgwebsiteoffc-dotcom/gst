<?php

namespace App\Console\Commands;

use App\Services\Gst\RecurringInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'recurring:generate {--date= : Run as if today were this date (YYYY-MM-DD, for testing)}';

    protected $description = 'Generate invoices for every recurring schedule that is due.';

    public function handle(RecurringInvoiceService $service): int
    {
        $asOf = $this->option('date')
            ? Carbon::parse($this->option('date'))->endOfDay()
            : now();

        $created = $service->generateDue($asOf);

        $this->info("Generated {$created} recurring invoice(s).");

        return self::SUCCESS;
    }
}
