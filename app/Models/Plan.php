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
}
