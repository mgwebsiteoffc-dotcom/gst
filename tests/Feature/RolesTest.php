<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed());

function actor(string $email): User
{
    $u = User::where('email', $email)->firstOrFail();
    setPermissionsTeamId($u->tenant_id);

    return $u;
}

it('seeds every demo account with the right role', function () {
    foreach ([
        'superadmin@bikribook.test' => 'Super Admin',
        'owner@demo.test' => 'Owner',
        'admin@demo.test' => 'Admin',
        'accountant@demo.test' => 'Accountant',
        'operator@demo.test' => 'Operator',
        'viewer@demo.test' => 'Viewer',
    ] as $email => $label) {
        expect(actor($email)->roleLabel())->toBe($label);
    }
});

it('grants the super admin every ability, even outside a tenant', function () {
    $su = actor('superadmin@bikribook.test');

    expect($su->tenant_id)->toBeNull();

    foreach (['invoice.create', 'subscription.manage', 'user.manage', 'einvoice.cancel'] as $p) {
        expect($su->can($p))->toBeTrue();
    }
});

it('gives the owner full control of their own business', function () {
    $owner = actor('owner@demo.test');

    foreach (collect(RolesAndPermissionsSeeder::PERMISSIONS)->flatten() as $p) {
        expect($owner->can($p))->toBeTrue();
    }
});

it('stops an admin from touching billing or the team', function () {
    $admin = actor('admin@demo.test');

    expect($admin->can('invoice.create'))->toBeTrue()
        ->and($admin->can('einvoice.generate'))->toBeTrue()
        ->and($admin->can('subscription.manage'))->toBeFalse()
        ->and($admin->can('user.manage'))->toBeFalse()
        ->and($admin->can('settings.update'))->toBeFalse();
});

it('lets an accountant file returns but not reconfigure the company', function () {
    $ca = actor('accountant@demo.test');

    expect($ca->can('gstr.export'))->toBeTrue()
        ->and($ca->can('payment.record'))->toBeTrue()
        ->and($ca->can('branding.update'))->toBeFalse()
        ->and($ca->can('shopify.manage'))->toBeFalse();
});

it('limits an operator to counter billing', function () {
    $op = actor('operator@demo.test');

    expect($op->can('invoice.create'))->toBeTrue()
        ->and($op->can('payment.record'))->toBeTrue()
        ->and($op->can('invoice.delete'))->toBeFalse()
        ->and($op->can('einvoice.generate'))->toBeFalse()
        ->and($op->can('gstr.export'))->toBeFalse();
});

it('keeps a viewer strictly read-only', function () {
    $v = actor('viewer@demo.test');

    expect($v->can('invoice.view'))->toBeTrue()
        ->and($v->can('report.view'))->toBeTrue()
        ->and($v->can('invoice.create'))->toBeFalse()
        ->and($v->can('party.manage'))->toBeFalse()
        ->and($v->can('payment.record'))->toBeFalse();
});

it('scopes roles per business so tenants never leak', function () {
    $demoOwner = actor('owner@demo.test');
    $anita = User::where('email', 'anita@demo.test')->firstOrFail();

    expect($demoOwner->tenant_id)->not->toBe($anita->tenant_id);

    // Anita owns her own business only.
    setPermissionsTeamId($anita->tenant_id);
    expect($anita->hasRole('owner'))->toBeTrue();

    // Under the other tenant's scope she holds no role at all.
    setPermissionsTeamId($demoOwner->tenant_id);
    expect($anita->fresh()->hasRole('owner'))->toBeFalse();
});
