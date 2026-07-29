<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $attributes = [
        'status' => 'draft',
        'noindex' => false,
        'views' => 0,
        'reading_minutes' => 1,
    ];

    protected function casts(): array
    {
        return ['faqs' => 'array', 'noindex' => 'boolean', 'published_at' => 'datetime'];
    }

    public function category() { return $this->belongsTo(PostCategory::class, 'post_category_id'); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }

    /** Meta title falls back to the post title, trimmed to a sane SERP length. */
    public function seoTitle(): string
    {
        return Str::limit($this->meta_title ?: $this->title, 60, '');
    }

    public function seoDescription(): string
    {
        return Str::limit(
            $this->meta_description ?: $this->excerpt ?: strip_tags((string) $this->body),
            155
        );
    }

    /** Rough reading time from the rendered body. */
    public static function estimateReadingMinutes(?string $html): int
    {
        $words = str_word_count(strip_tags((string) $html));

        return max(1, (int) ceil($words / 200));
    }

    /** Headings become an on-page table of contents (helps AEO and snippets). */
    public function tableOfContents(): array
    {
        if (! $this->body || ! preg_match_all('/<h2[^>]*>(.*?)<\/h2>/is', $this->body, $m)) {
            return [];
        }

        return collect($m[1])->map(fn ($h) => [
            'title' => trim(strip_tags($h)),
            'anchor' => Str::slug(strip_tags($h)),
        ])->all();
    }

    /** Inject id attributes into H2s so the ToC can link to them. */
    public function bodyWithAnchors(): string
    {
        return preg_replace_callback('/<h2([^>]*)>(.*?)<\/h2>/is', function ($m) {
            $anchor = Str::slug(strip_tags($m[2]));

            return '<h2'.$m[1].' id="'.$anchor.'">'.$m[2].'</h2>';
        }, (string) $this->body) ?? (string) $this->body;
    }
}
