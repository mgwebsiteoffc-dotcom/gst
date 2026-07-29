<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\Seo\Seo;
use App\Support\SiteMap;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request, Seo $seo)
    {
        $posts = Post::published()->with('category', 'author')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%$s%")->orWhere('excerpt', 'like', "%$s%")))
            ->when($request->category, fn ($q, $c) => $q->whereHas('category', fn ($x) => $x->where('slug', $c)))
            ->latest('published_at')
            ->paginate(9)->withQueryString();

        $seo->for([
            'title' => 'GST & Business Blog for Indian Small Business',
            'description' => 'Practical guides on GST invoicing, e-invoicing, e-way bills, GSTR filing and running a small business in India.',
        ])->schema(Seo::breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
        ]));

        return view('marketing.blog.index', [
            'seo' => $seo,
            'posts' => $posts,
            'categories' => PostCategory::withCount(['posts' => fn ($q) => $q->published()])->get(),
        ]);
    }

    public function show(Seo $seo, string $slug)
    {
        $post = Post::with('category', 'author')->where('slug', $slug)->firstOrFail();

        abort_unless($post->isPublished() || auth()->user()?->is_super_admin, 404);

        $post->incrementQuietly('views');

        $seo->for([
            'title' => $post->seoTitle(),
            'description' => $post->seoDescription(),
            'canonical' => $post->canonical_url ?: route('blog.show', $post->slug),
            'image' => $post->cover_image ? asset('storage/'.$post->cover_image) : asset('images/og-default.png'),
            'type' => 'article',
            'noindex' => $post->noindex,
        ])
            ->schema(Seo::article($post))
            ->schema(Seo::breadcrumbs(array_filter([
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Blog', 'url' => route('blog.index')],
                $post->category ? ['name' => $post->category->name, 'url' => route('blog.index', ['category' => $post->category->slug])] : null,
                ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
            ])));

        // Post-level FAQs feed the FAQPage schema that answer engines quote.
        if ($post->faqs && ($f = Seo::faqPage($post->faqs))) {
            $seo->schema($f);
        }

        return view('marketing.blog.show', [
            'seo' => $seo,
            'post' => $post,
            'related' => Post::published()->with('category')->where('id', '!=', $post->id)
                ->when($post->post_category_id, fn ($q) => $q->where('post_category_id', $post->post_category_id))
                ->latest('published_at')->take(3)->get(),
        ]);
    }

    /** XML sitemap covering marketing pages, blog posts and categories. */
    public function sitemap()
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0', 'freq' => 'weekly'],
            ['loc' => route('features'), 'priority' => '0.9', 'freq' => 'monthly'],
            ['loc' => route('pricing'), 'priority' => '0.9', 'freq' => 'monthly'],
            ['loc' => route('about'), 'priority' => '0.6', 'freq' => 'yearly'],
            ['loc' => route('contact'), 'priority' => '0.6', 'freq' => 'yearly'],
            ['loc' => route('blog.index'), 'priority' => '0.8', 'freq' => 'daily'],
        ]);

        foreach (['privacy-policy', 'terms-of-service', 'refund-policy', 'shipping-policy'] as $p) {
            $urls->push(['loc' => route('legal', $p), 'priority' => '0.3', 'freq' => 'yearly']);
        }

        foreach (['templates', 'download', 'integrations', 'tools',
                  'partners.channel', 'partners.affiliate'] as $r) {
            $urls->push(['loc' => route($r), 'priority' => '0.7', 'freq' => 'monthly']);
        }

        foreach (array_keys(SiteMap::SOLUTIONS) as $slug) {
            $urls->push(['loc' => route('solution', $slug), 'priority' => '0.8', 'freq' => 'monthly']);
        }

        foreach (array_keys(SiteMap::TOOLS) as $slug) {
            $urls->push(['loc' => route('tool', $slug), 'priority' => '0.6', 'freq' => 'monthly']);
        }

        foreach (PostCategory::all() as $c) {
            $urls->push(['loc' => route('blog.index', ['category' => $c->slug]), 'priority' => '0.6', 'freq' => 'weekly']);
        }

        foreach (Post::published()->where('noindex', false)->get() as $post) {
            $urls->push([
                'loc' => route('blog.show', $post->slug),
                'lastmod' => $post->updated_at->toAtomString(),
                'priority' => '0.7',
                'freq' => 'monthly',
            ]);
        }

        return response()
            ->view('marketing.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /settings',
            'Disallow: /documents',
            'Disallow: /contacts',
            'Disallow: /items',
            '',
            // Answer engines are welcomed explicitly.
            'User-agent: GPTBot',
            'Allow: /',
            '',
            'User-agent: PerplexityBot',
            'Allow: /',
            '',
            'User-agent: ClaudeBot',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines))->header('Content-Type', 'text/plain');
    }
}
