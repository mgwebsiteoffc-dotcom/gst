<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'featured' => 'boolean',
            'rating' => 'integer',
        ];
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('active', true)->orderBy('position')->orderByDesc('id');
    }

    /** Average rating across live reviews — used for the trust strip. */
    public static function averageRating(): ?float
    {
        $avg = static::where('active', true)->avg('rating');

        return $avg ? round((float) $avg, 1) : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    public function typeLabel(): string
    {
        return [
            'retailer' => 'Retailer',
            'wholesaler' => 'Wholesaler',
            'distributor' => 'Distributor',
            'manufacturer' => 'Manufacturer',
            'ca' => 'Chartered Accountant',
            'service' => 'Service business',
            'shopify' => 'Online seller',
        ][$this->business_type] ?? 'Business owner';
    }
}
