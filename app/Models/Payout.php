<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payout extends Model
{
    protected $guarded = [];
    protected $attributes = ['status' => 'pending', 'method' => 'bank'];

    protected function casts(): array { return ['paid_at' => 'datetime']; }

    public static function booted(): void
    {
        static::creating(fn (self $p) => $p->reference ??= 'PO-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)));
    }

    public function partner() { return $this->belongsTo(Partner::class); }
    public function commissions() { return $this->hasMany(Commission::class); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }

    public function getAmountAttribute(): float { return $this->amount_paise / 100; }
}
