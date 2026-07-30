<?php

use App\Support\SiteMap;

beforeEach(fn () => $this->seed());

/** Every public page should carry at least one illustration. */
dataset('public_pages', function () {
    $urls = ['/', '/features', '/pricing', '/about', '/invoice-formats',
             '/download', '/integrations', '/tools',
             '/partners/channel', '/partners/affiliate'];

    foreach (array_keys(SiteMap::SOLUTIONS) as $s) {
        $urls[] = "/solutions/{$s}";
    }
    foreach (array_keys(SiteMap::TOOLS) as $t) {
        $urls[] = "/tools/{$t}";
    }

    return $urls;
});

it('shows an image on every public page', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    expect($html)->toMatch('#<img[^>]+src="[^"]*/images/#');
})->with('public_pages');

it('serves every image the pages reference', function () {
    $urls = ['/', '/features', '/pricing', '/about', '/invoice-formats', '/download',
             '/integrations', '/tools', '/partners/channel', '/partners/affiliate'];

    foreach (array_keys(SiteMap::SOLUTIONS) as $s) { $urls[] = "/solutions/{$s}"; }
    foreach (array_keys(SiteMap::TOOLS) as $t) { $urls[] = "/tools/{$t}"; }

    $missing = [];

    foreach ($urls as $url) {
        $html = $this->get($url)->getContent();
        preg_match_all('#(?:src|srcset)="[^"]*?/images/([a-z0-9._-]+)"#i', $html, $m);

        foreach (array_unique($m[1]) as $file) {
            if (! file_exists(public_path("images/{$file}"))) {
                $missing[] = "{$url} -> {$file}";
            }
        }
    }

    expect($missing)->toBeEmpty(implode(', ', $missing));
});

it('sets width and height on illustrations to avoid layout shift', function () {
    $html = $this->get('/solutions/e-invoicing')->assertOk()->getContent();

    preg_match_all('#<img[^>]+/images/[^>]+>#', $html, $m);
    expect($m[0])->not->toBeEmpty();

    foreach ($m[0] as $tag) {
        expect($tag)->toContain('width=')->toContain('height=');
    }
});

it('gives every solution page steps and FAQs', function () {
    foreach (SiteMap::SOLUTIONS as $slug => $page) {
        expect($page['image'] ?? null)->not->toBeNull("{$slug} has no image")
            ->and($page['steps'] ?? [])->toHaveCount(4)
            ->and($page['faqs'] ?? [])->toHaveCount(3);

        $this->get("/solutions/{$slug}")->assertOk()
            ->assertSee('How it works', false)
            ->assertSee('Common questions', false);
    }
});

it('emits FAQ schema on solution pages', function () {
    $html = $this->get('/solutions/gst-billing')->getContent();

    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
    $types = collect($m[1])->map(fn ($j) => json_decode($j, true)['@type'])->all();

    expect($types)->toContain('FAQPage');
});
