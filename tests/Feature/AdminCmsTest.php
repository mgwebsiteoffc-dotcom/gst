<?php

use App\Models\Faq;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('is_super_admin', true)->firstOrFail();
    $this->owner = User::where('email', 'owner@demo.test')->firstOrFail();
});

it('renders the admin CMS screens for a super admin', function () {
    $post = Post::first();

    foreach (['/admin', '/admin/posts', '/admin/posts/create', "/admin/posts/{$post->id}/edit",
              '/admin/faqs', '/admin/leads'] as $url) {
        $this->actingAs($this->admin)->get($url)->assertOk();
    }
});

it('keeps tenant users out of the CMS', function () {
    foreach (['/admin/posts', '/admin/faqs', '/admin/leads'] as $url) {
        $this->actingAs($this->owner)->get($url)->assertForbidden();
    }
});

it('creates a post and auto-generates slug and reading time', function () {
    $this->actingAs($this->admin)->post('/admin/posts', [
        'title' => 'How To Register For GST In India',
        'status' => 'published',
        'body' => '<h2>Step one</h2><p>'.str_repeat('word ', 400).'</p>',
        'excerpt' => 'A short guide.',
    ])->assertRedirect();

    $post = Post::where('title', 'How To Register For GST In India')->firstOrFail();

    expect($post->slug)->toBe('how-to-register-for-gst-in-india')
        ->and($post->reading_minutes)->toBe(3)   // ~401 words / 200
        ->and($post->published_at)->not->toBeNull()
        ->and($post->author_id)->toBe($this->admin->id);
});

it('saves FAQs on a post and drops blank rows', function () {
    $this->actingAs($this->admin)->post('/admin/posts', [
        'title' => 'FAQ Test Post',
        'status' => 'draft',
        'body' => '<p>Body</p>',
        'faqs' => [
            ['question' => 'Is it free?', 'answer' => 'Yes, for 14 days.'],
            ['question' => '', 'answer' => ''],              // blank -> dropped
            ['question' => 'Only a question', 'answer' => ''], // incomplete -> dropped
        ],
    ])->assertRedirect();

    $post = Post::where('slug', 'faq-test-post')->firstOrFail();

    expect($post->faqs)->toHaveCount(1)
        ->and($post->faqs[0]['question'])->toBe('Is it free?');
});

it('does not publish a draft to the blog until it is published', function () {
    $this->actingAs($this->admin)->post('/admin/posts', [
        'title' => 'Unpublished Draft', 'status' => 'draft', 'body' => '<p>x</p>',
    ]);

    // A super admin may preview a draft, but the public must not see it.
    $this->actingAs($this->admin)->get('/blog/unpublished-draft')->assertOk();
    auth()->logout();
    $this->get('/blog/unpublished-draft')->assertNotFound();

    $post = Post::where('slug', 'unpublished-draft')->firstOrFail();
    $this->actingAs($this->admin)->put("/admin/posts/{$post->id}", [
        'title' => 'Unpublished Draft', 'slug' => 'unpublished-draft',
        'status' => 'published', 'body' => '<p>x</p>',
    ])->assertRedirect();

    auth()->logout();
    $this->get('/blog/unpublished-draft')->assertOk();
});

it('rejects a duplicate slug', function () {
    $existing = Post::first();

    $this->actingAs($this->admin)->post('/admin/posts', [
        'title' => 'Another', 'slug' => $existing->slug, 'status' => 'draft', 'body' => '<p>x</p>',
    ])->assertSessionHasErrors('slug');
});

it('uploads an inline editor image and returns its url', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post('/admin/posts/upload', ['file' => UploadedFile::fake()->image('chart.png')])
        ->assertOk()
        ->assertJsonStructure(['url', 'location']);
});

it('deletes a post and removes it from the sitemap', function () {
    $post = Post::published()->firstOrFail();
    $slug = $post->slug;

    expect($this->get('/sitemap.xml')->getContent())->toContain($slug);

    $this->actingAs($this->admin)->delete("/admin/posts/{$post->id}")->assertRedirect();

    auth()->logout();
    expect($this->get('/sitemap.xml')->getContent())->not->toContain('/blog/'.$slug);
});

it('manages marketing FAQs and reflects them on the public page', function () {
    $this->actingAs($this->admin)->post('/admin/faqs', [
        'page' => 'home',
        'question' => 'Does BikriBook work offline?',
        'answer' => 'BikriBook is cloud-based and needs an internet connection.',
    ])->assertRedirect();

    auth()->logout();
    $this->get('/')->assertSee('Does BikriBook work offline?', false);

    $faq = Faq::where('question', 'Does BikriBook work offline?')->firstOrFail();

    // Hiding an FAQ removes it from the public page.
    $this->actingAs($this->admin)->put("/admin/faqs/{$faq->id}", [
        'question' => $faq->question, 'answer' => $faq->answer, 'active' => false,
    ])->assertRedirect();

    auth()->logout();
    $this->get('/')->assertDontSee('Does BikriBook work offline?', false);
});

it('lets an admin triage a contact enquiry', function () {
    $this->post('/contact', [
        'name' => 'Sita', 'email' => 'sita@shop.test', 'message' => 'Need a demo please.',
    ]);

    $lead = \App\Models\Lead::latest('id')->firstOrFail();

    $this->actingAs($this->admin)->get('/admin/leads')->assertOk()->assertSee('Sita');
    $this->actingAs($this->admin)->put("/admin/leads/{$lead->id}", ['status' => 'contacted'])->assertRedirect();

    expect($lead->fresh()->status)->toBe('contacted');
});
