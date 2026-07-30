<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ShopifyStore extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
    protected $hidden = ['access_token'];

    protected function casts(): array
    {
        return [
            'auto_create_invoice' => 'boolean',
            'sync_products' => 'boolean',
            'prices_include_tax' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => ($v && $v !== '') ? Crypt::decryptString($v) : null,
            set: fn ($v) => ($v && $v !== '') ? Crypt::encryptString($v) : '',
        );
    }

    public function logs() { return $this->hasMany(ShopifySyncLog::class); }
}