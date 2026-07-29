<?php

namespace App\Services\Einvoice;

use App\Models\Tenant;
use App\Services\Einvoice\Contracts\EinvoiceGateway;
use App\Support\TenantContext;

/**
 * Chooses the e-invoice gateway per tenant at call time.
 *
 * A tenant that has saved its own NIC/GSP credentials gets a live gateway bound to
 * those credentials (sandbox or production, per its own toggle). Everyone else falls
 * back to the application default from config/einvoice.php.
 */
class EinvoiceGatewayResolver
{
    public function __construct(protected EinvoicePayloadBuilder $builder) {}

    public function for(?Tenant $tenant = null): EinvoiceGateway
    {
        $tenant ??= TenantContext::get();

        if ($tenant?->hasNicCredentials()) {
            $config = $tenant->nicConfig();

            return $config['driver'] === 'sandbox'
                ? new SandboxEinvoiceGateway($this->builder)
                : new NicEinvoiceGateway($this->builder, $config);
        }

        $driver = $tenant?->einvoice_driver ?: config('einvoice.driver');

        if ($driver !== 'nic') {
            return new SandboxEinvoiceGateway($this->builder);
        }

        return new NicEinvoiceGateway($this->builder, array_merge(config('einvoice'), [
            'base_url' => config('einvoice.base_url'),
            'default_gstin' => $tenant?->gstin,
        ]));
    }

    /** Human-readable label for the settings screen. */
    public function describe(?Tenant $tenant = null): string
    {
        $tenant ??= TenantContext::get();

        if ($tenant?->hasNicCredentials()) {
            return $tenant->nic_sandbox ? 'NIC (sandbox, own credentials)' : 'NIC (production, own credentials)';
        }

        return ($tenant?->einvoice_driver ?: config('einvoice.driver')) === 'nic'
            ? 'NIC (shared credentials)'
            : 'Offline sandbox';
    }
}
