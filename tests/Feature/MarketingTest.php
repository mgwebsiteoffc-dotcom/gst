<?php

use App\Models\Faq;
use App\Models\Lead;
use App\Models\Post;
use App\Models\User;

beforeEach(fn () => $this->seed());

it('renders every public marketing page', function () {
    foreach (['/', '/features', '/pricing', '/about', '/contact', '/blog'] as $url) {
        $this->get($url)->assertOk();
    }

    foreach (['privacy-policy', 'terms-of-service', 'refund-policy', 'shipping-policy'] as $p) {
        $this->get("/legal/$p")->assertOk();
    }
});

it('serves a valid xml sitemap listing published posts', function () {
    $res = $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    $xml = $res->getContent();
    expect(simplexml_load_string($xml))->not->toBeFalse();

    foreach (Post::published()->get() as $post) {
        expect($xml)->toContain(route('blog.show', $post->slug));
    }

    // Private app routes must never be advertised.
    expect($xml)->not->toContain('/dashboard');
});

it('serves robots.txt that blocks the app and allows answer engines', function () {
    $body = $this->get('/robots.txt')->assertOk()->getContent();

    expect($body)->toContain('Disallow: /dashboard')
        ->and($body)->toContain('Disallow: /admin')
        ->and($body)->toContain('GPTBot')
        ->and($body)->toContain('Sitemap: ');
});

it('outputs SEO meta tags and canonical on the home page', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<meta name="description"')
        ->and($html)->toContain('rel="canonical"')
        ->and($html)->toContain('property="og:title"')
        ->and($html)->toContain('name="twitter:card"');
});

it('emits AEO structured data that parses as valid JSON', function () {
    $html = $this->get('/')->assertOk()->getContent();

    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
    expect($m[1])->not->toBeEmpty();

    $types = [];
    foreach ($m[1] as $json) {
        $data = json_decode($json, true);
        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        $types[] = $data['@type'];
    }

    expect($types)->toContain('Organization')
        ->toContain('SoftwareApplication')
        ->toContain('FAQPage');
});

it('publishes FAQPage schema with real question and answer pairs', function () {
    $html = $this->get('/')->getContent();
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);

    $faqSchema = collect($m[1])->map(fn ($j) => json_decode($j, true))
        ->firstWhere('@type', 'FAQPage');

    expect($faqSchema['mainEntity'])->not->toBeEmpty()
        ->and($faqSchema['mainEntity'][0]['@type'])->toBe('Question')
        ->and($faqSchema['mainEntity'][0]['acceptedAnswer']['text'])->not->toBeEmpty();
});

it('shows a published post with Article and breadcrumb schema', function () {
    $post = Post::published()->firstOrFail();

    $html = $this->get("/blog/{$post->slug}")->assertOk()
        ->assertSee($post->title, false)
        ->getContent();

    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
    $types = collect($m[1])->map(fn ($j) => json_decode($j, true)['@type'])->all();

    expect($types)->toContain('BlogPosting')->toContain('BreadcrumbList');
});

it('hides draft posts from the public', function () {
    $draft = Post::create(['title' => 'Secret', 'slug' => 'secret-draft', 'status' => 'draft', 'body' => '<p>x</p>']);

    $this->get('/blog/secret-draft')->assertNotFound();
    $this->get('/blog')->assertDontSee('Secret');
});

it('counts a view when a post is read', function () {
    $post = Post::published()->firstOrFail();
    $before = $post->views;

    $this->get("/blog/{$post->slug}")->assertOk();

    expect($post->fresh()->views)->toBe($before + 1);
});

it('accepts a contact enquiry and stores it as a lead', function () {
    $this->post('/contact', [
        'name' => 'Ramesh Gupta',
        'email' => 'ramesh@shop.test',
        'phone' => '9876543210',
        'message' => 'Do you support Tally import?',
    ])->assertRedirect();

    $lead = Lead::latest('id')->first();
    expect($lead->name)->toBe('Ramesh Gupta')->and($lead->status)->toBe('new');
});

it('rejects a contact submission that fills the honeypot', function () {
    $this->post('/contact', [
        'name' => 'Bot', 'email' => 'bot@spam.test',
        'message' => 'spam', 'website' => 'http://spam.test',
    ])->assertSessionHasErrors('website');

    expect(Lead::where('email', 'bot@spam.test')->exists())->toBeFalse();
});

it('generates a table of contents from H2 headings', function () {
    $post = Post::published()->firstOrFail();

    expect($post->tableOfContents())->not->toBeEmpty()
        ->and($post->bodyWithAnchors())->toContain('id="');
});
