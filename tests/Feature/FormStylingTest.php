<?php

use App\Models\User;

beforeEach(fn () => $this->seed());

/**
 * The compiled stylesheet is the single source of truth for form styling.
 * These assertions guard the exact regressions we hit: the forms plugin
 * missing entirely, and untyped <input> elements falling through the selector.
 */
function compiledCss(): string
{
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

    return file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
}

it('ships the tailwind forms reset', function () {
    // A distinctive selector the plugin emits.
    expect(compiledCss())->toContain('input:where([type=checkbox])');
});

it('styles inputs that have no type attribute', function () {
    // <x-input name="foo" /> renders without a type; without :not([type])
    // it loses every border, radius and padding rule.
    expect(compiledCss())->toContain('input:not([type])');
});

it('gives form controls a border, radius and padding', function () {
    $css = compiledCss();

    expect($css)->toMatch('/input:not\(\[type\]\)[^{]*\{[^}]*border-radius:\.5rem/')
        ->and($css)->toContain('--color-slate-300');
});

it('renders inputs with the name attribute intact', function () {
    // Regression: declaring `name` in @props stripped it from the markup.
    $html = $this->get('/login')->assertOk()->getContent();

    expect($html)->toContain('name="email"')->toContain('name="password"');
});

it('renders the plans screen with labelled fields', function () {
    $admin = User::where('is_super_admin', true)->firstOrFail();

    $this->actingAs($admin)->get('/admin/plans')->assertOk()
        ->assertSee('Monthly price', false)
        ->assertSee('Team members', false)
        ->assertSee('Included features', false);
});
