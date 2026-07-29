<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Einvoice\EinvoiceGatewayResolver;
use App\Services\Einvoice\NicEinvoiceGateway;
use Illuminate\Console\Command;

class EinvoiceTestCommand extends Command
{
    protected $signature = 'einvoice:test {tenant? : Tenant id or slug}';
    protected $description = 'Verify NIC / IRP credentials for one or all tenants';

    public function handle(EinvoiceGatewayResolver $resolver): int
    {
        $tenants = $this->argument('tenant')
            ? Tenant::where('id', $this->argument('tenant'))->orWhere('slug', $this->argument('tenant'))->get()
            : Tenant::where('einvoice_enabled', true)->get();

        if ($tenants->isEmpty()) {
            $this->warn('No matching tenants.');

            return self::FAILURE;
        }

        $rows = [];
        foreach ($tenants as $tenant) {
            $gateway = $resolver->for($tenant);
            $mode = $resolver->describe($tenant);

            if (! $gateway instanceof NicEinvoiceGateway) {
                $rows[] = [$tenant->slug, $tenant->gstin, $mode, '<comment>skipped (sandbox)</comment>'];
                continue;
            }

            $result = $gateway->testConnection($tenant->gstin);
            $rows[] = [$tenant->slug, $tenant->gstin, $mode,
                $result['success'] ? '<info>OK</info>' : '<error>'.$result['message'].'</error>'];

            if ($result['success']) {
                $tenant->update(['nic_verified_at' => now()]);
            }
        }

        $this->table(['Tenant', 'GSTIN', 'Gateway', 'Result'], $rows);

        return self::SUCCESS;
    }
}
