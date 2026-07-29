<?php

use App\Models\Tenant;
use App\Services\Einvoice\EinvoiceGatewayResolver;
use App\Services\Einvoice\NicEinvoiceGateway;
use App\Services\Einvoice\SandboxEinvoiceGateway;

it('falls back to the sandbox gateway without credentials', function () {
    config(['einvoice.driver' => 'sandbox']);
    $tenant = Tenant::factory()->make(['gstin' => '09AAACD1234F1Z5']);

    expect(app(EinvoiceGatewayResolver::class)->for($tenant))->toBeInstanceOf(SandboxEinvoiceGateway::class);
});

it('uses the live NIC gateway when the tenant supplies credentials', function () {
    $tenant = Tenant::factory()->make([
        'gstin' => '09AAACD1234F1Z5',
        'einvoice_driver' => 'nic',
        'nic_sandbox' => false,
        'nic_username' => 'u', 'nic_password' => 'p',
        'nic_client_id' => 'c', 'nic_client_secret' => 's',
    ]);

    expect(app(EinvoiceGatewayResolver::class)->for($tenant))->toBeInstanceOf(NicEinvoiceGateway::class)
        ->and($tenant->nicConfig()['base_url'])->toBe(config('einvoice.production_url'));
});

it('honours a tenant sandbox toggle even with credentials present', function () {
    $tenant = Tenant::factory()->make([
        'gstin' => '09AAACD1234F1Z5',
        'einvoice_driver' => 'sandbox',
        'nic_username' => 'u', 'nic_password' => 'p',
        'nic_client_id' => 'c', 'nic_client_secret' => 's',
    ]);

    expect(app(EinvoiceGatewayResolver::class)->for($tenant))->toBeInstanceOf(SandboxEinvoiceGateway::class);
});
