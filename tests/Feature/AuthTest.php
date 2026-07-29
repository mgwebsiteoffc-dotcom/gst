<?php

use App\Models\Tenant;
use App\Models\User;

beforeEach(fn () => $this->seed());

it('shows the login and register pages to guests', function () {
    $this->get('/login')->assertOk()->assertSee('Welcome back');
    $this->get('/register')->assertOk()->assertSee('Start invoicing in minutes');
    $this->get('/forgot-password')->assertOk();
});

it('signs a user in and out', function () {
    $this->post('/login', ['email' => 'owner@demo.test', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $this->post('/logout')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('rejects bad credentials', function () {
    $this->post('/login', ['email' => 'owner@demo.test', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('sends a super admin to the platform dashboard', function () {
    $this->post('/login', ['email' => 'superadmin@bikribook.test', 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->get('/admin')->assertOk()->assertSee('Platform Admin');
});

it('blocks normal users from the admin area', function () {
    $this->actingAs(User::where('email', 'owner@demo.test')->first())
        ->get('/admin')->assertForbidden();
});

it('redirects guests to the login page', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('registers a GST business and creates the owner', function () {
    $this->post('/register', [
        'name' => 'Vikram Rao',
        'email' => 'vikram@newco.test',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
        'business_name' => 'Rao Exports',
        'tax_mode' => 'gst',
        'gstin' => '33AABCU9603R1ZU',
        'terms' => '1',
    ])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'vikram@newco.test')->firstOrFail();
    $tenant = $user->tenant;

    expect($tenant->name)->toBe('Rao Exports')
        ->and($tenant->gstin)->toBe('33AABCU9603R1ZU')
        // State and PAN are derived from the GSTIN.
        ->and($tenant->state_code)->toBe('33')
        ->and($tenant->pan)->toBe('AABCU9603R')
        ->and($tenant->tax_mode)->toBe('gst')
        ->and($tenant->trial_ends_at)->not->toBeNull();

    setPermissionsTeamId($tenant->id);
    expect($user->hasRole('owner'))->toBeTrue()
        ->and($user->can('subscription.manage'))->toBeTrue();
});

it('registers a non-GST business without a GSTIN', function () {
    $this->post('/register', [
        'name' => 'Meera', 'email' => 'meera@studio.test',
        'password' => 'secret1234', 'password_confirmation' => 'secret1234',
        'business_name' => 'Meera Studio', 'tax_mode' => 'non_gst',
        'state_code' => '09', 'terms' => '1',
    ])->assertRedirect(route('dashboard'));

    $tenant = User::where('email', 'meera@studio.test')->firstOrFail()->tenant;

    expect($tenant->gstin)->toBeNull()
        ->and($tenant->tax_mode)->toBe('non_gst')
        ->and($tenant->state_code)->toBe('09');
});

it('requires a GSTIN when registering in GST mode', function () {
    $this->post('/register', [
        'name' => 'X', 'email' => 'x@y.test',
        'password' => 'secret1234', 'password_confirmation' => 'secret1234',
        'business_name' => 'Y', 'tax_mode' => 'gst', 'terms' => '1',
    ])->assertSessionHasErrors('gstin');
});

it('rejects an invalid GSTIN at registration', function () {
    $this->post('/register', [
        'name' => 'X', 'email' => 'x@y.test',
        'password' => 'secret1234', 'password_confirmation' => 'secret1234',
        'business_name' => 'Y', 'tax_mode' => 'gst',
        'gstin' => '33AABCU9603R1ZM', 'terms' => '1',
    ])->assertSessionHasErrors('gstin');
});

it('gives each new business its own isolated role set', function () {
    $before = Tenant::count();

    $this->post('/register', [
        'name' => 'A', 'email' => 'a@b.test',
        'password' => 'secret1234', 'password_confirmation' => 'secret1234',
        'business_name' => 'B Co', 'tax_mode' => 'non_gst',
        'state_code' => '27', 'terms' => '1',
    ]);

    expect(Tenant::count())->toBe($before + 1);

    $tenant = Tenant::where('slug', 'b-co')->firstOrFail();
    setPermissionsTeamId($tenant->id);

    expect(\Spatie\Permission\Models\Role::where('tenant_id', $tenant->id)->pluck('name')->sort()->values()->all())
        ->toBe(['accountant', 'admin', 'operator', 'owner', 'viewer']);
});
