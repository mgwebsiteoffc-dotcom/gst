<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

/**
 * Central SEO/AEO helper.
 *
 * SEO = meta tags, canonical, Open Graph, Twitter cards.
 * AEO = structured data (JSON-LD) so answer engines and AI assistants can quote
 *       the page directly: Organization, SoftwareApplication, FAQPage, Article,
 *       BreadcrumbList and HowTo.
 */
class Seo
{
    protected array $tags = [];
    protected array $schemas = [];

    public function for(array $data): static
    {
        $this->tags = array_merge([
            'title' => config('app.name'),
            'description' => null,
            'canonical' => url()->current(),
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'noindex' => false,
        ], $data);

        return $this;
    }

    public function schema(array $schema): static
    {
        $this->schemas[] = $schema;

        return $this;
    }

    public function get(string $key)
    {
        return $this->tags[$key] ?? null;
    }

    public function title(): string
    {
        $t = $this->tags['title'] ?? config('app.name');

        return Str::contains($t, config('app.name')) ? $t : $t.' | '.config('app.name');
    }

    public function schemas(): array
    {
        return $this->schemas;
    }

    // ---------------------------------------------------------------
    // Reusable schema builders
    // ---------------------------------------------------------------

    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => config('app.url'),
            'logo' => asset('images/logo.png'),
            'description' => 'GST billing, e-invoicing and inventory software for Indian small businesses.',
            'foundingDate' => '2026',
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'IN',
                'addressRegion' => 'Uttar Pradesh',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => 'support@bikribook.com',
                'availableLanguage' => ['English', 'Hindi'],
                'areaServed' => 'IN',
            ],
        ];
    }

    /** Tells search and answer engines exactly what the product is and what it costs. */
    public static function softwareApplication(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => config('app.name'),
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'Accounting & Invoicing Software',
            'operatingSystem' => 'Web, Android, iOS',
            'url' => config('app.url'),
            'description' => 'GST billing software for Indian MSMEs with e-invoice (IRN), e-way bill, GSTR-1/3B reports, inventory and Shopify sync.',
            'featureList' => [
                'GST and non-GST invoicing',
                'e-Invoice (IRN) generation via NIC',
                'e-Way Bill generation',
                'GSTR-1 and GSTR-3B reports',
                'Inventory and stock tracking',
                'Shopify order sync',
                'Quotations, proforma and delivery challans',
                'Multi-user access with roles',
            ],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'INR',
                'lowPrice' => '499',
                'highPrice' => '2499',
                'offerCount' => '3',
            ],
            'aggregateRating' => null, // populate only with genuine reviews
            'inLanguage' => ['en-IN', 'hi-IN'],
        ];
    }

    /** FAQPage markup is what earns the "People also ask" and AI answer citations. */
    public static function faqPage(iterable $faqs): ?array
    {
        $items = collect($faqs)
            ->map(fn ($f) => [
                '@type' => 'Question',
                'name' => is_array($f) ? $f['question'] : $f->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags(is_array($f) ? $f['answer'] : $f->answer),
                ],
            ])->values()->all();

        if (! $items) {
            return null;
        }

        return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items];
    }

    public static function breadcrumbs(array $trail): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($trail)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    public static function article($post): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => Str::limit($post->title, 110, ''),
            'description' => $post->seoDescription(),
            'image' => $post->cover_image ? asset('storage/'.$post->cover_image) : asset('images/og-default.png'),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name ?? config('app.name').' Team',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.png')],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('blog.show', $post->slug)],
            'wordCount' => str_word_count(strip_tags((string) $post->body)),
            'inLanguage' => 'en-IN',
        ]);
    }
}
