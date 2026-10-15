<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['features' => 'array', 'einvoice' => 'boolean', 'shopify' => 'boolean', 'active' => 'boolean'];
    }

    public function subscriptions() { return $this->hasMany(Subscription::class); }

    public function getPriceAttribute(): float { return $this->price_paise / 100; }

    public function priceLabel(): string
    {
        return $this->price_paise ? '₹'.number_format($this->price_paise / 100) : __('Free');
    }

    /** Plans old enough to predate a feature column read as "not included". */
    public function includes(string $feature): bool
    {
        return (bool) ($this->{$feature} ?? false);
    }
}
