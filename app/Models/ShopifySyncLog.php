<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifySyncLog extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['payload' => 'array']; }

    public function store() { return $this->belongsTo(ShopifyStore::class, 'shopify_store_id'); }
}
