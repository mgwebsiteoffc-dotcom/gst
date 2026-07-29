<?php

use App\Support\SiteMap;

beforeEach(fn () => $this->seed());

it('renders every solution page', function () {
    foreach (array_keys(SiteMap::SOLUTIONS) as $slug) {
        $this->get("/solutions/{$slug}")->assertOk();
    }
})->group('site');

it('renders the tools hub and every tool', function () {
    $this->get('/tools')->assertOk();

    foreach (array_keys(SiteMap::TOOLS) as $slug) {
        $this->get("/tools/{$slug}")->assertOk();
    }
});

it('renders the remaining marketing pages', function () {
    foreach (['/invoice-formats', '/download', '/integrations',
              '/page/careers', '/page/product-updates'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('404s on an unknown solution or tool', function () {
    $this->get('/solutions/does-not-exist')->assertNotFound();
    $this->get('/tools/does-not-exist')->assertNotFound();
    $this->get('/page/does-not-exist')->assertNotFound();
});

/**
 * The footer is the main internal-linking surface for SEO, so a dead link there
 * is worse than a dead link anywhere else. Crawl every href it renders.
 */
it('has no broken links anywhere in the footer', function () {
    $html = $this->get('/')->assertOk()->getContent();

    $footer = substr($html, strpos($html, '<footer'));
    preg_match_all('/href="(https?:\/\/[^"]*|\/[^"]*)"/', $footer, $m);

    $internal = collect($m[1])
        ->filter(fn ($h) => ! str_starts_with($h, 'http') || str_contains($h, 'localhost'))
        ->map(fn ($h) => parse_url($h, PHP_URL_PATH) ?: '/')
        ->unique()->values();

    expect($internal->count())->toBeGreaterThan(20);

    foreach ($internal as $path) {
        $status = $this->get($path)->getStatusCode();
        expect($status)->toBeIn([200, 302], "Footer link {$path} returned {$status}");
    }
});

it('lists the new pages in the sitemap', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect(simplexml_load_string($xml))->not->toBeFalse();

    foreach (array_keys(SiteMap::SOLUTIONS) as $slug) {
        expect($xml)->toContain("/solutions/{$slug}");
    }
    foreach (array_keys(SiteMap::TOOLS) as $slug) {
        expect($xml)->toContain("/tools/{$slug}");
    }
});

it('ships the hero image referenced by the homepage', function () {
    $this->get('/')->assertOk()->assertSee('images/hero-invoice.png', false);

    expect(file_exists(public_path('images/hero-invoice.png')))->toBeTrue();
});

it('exposes the product dropdown in the header', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('/solutions/gst-billing')
        ->and($html)->toContain('/solutions/e-invoicing');
});

it('keeps solution pages indexable with structured data', function () {
    $html = $this->get('/solutions/e-invoicing')->assertOk()->getContent();

    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
    $types = collect($m[1])->map(fn ($j) => json_decode($j, true)['@type'])->all();

    expect($types)->toContain('BreadcrumbList')->toContain('SoftwareApplication')
        ->and($html)->toContain('rel="canonical"');
});
