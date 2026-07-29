<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $guarded = [];
    protected $attributes = ['status' => 'pending', 'month_number' => 1];

    protected function casts(): array { return ['available_at' => 'datetime']; }

    public function partner() { return $this->belongsTo(Partner::class); }
    public function referral() { return $this->belongsTo(Referral::class); }
    public function payout() { return $this->belongsTo(Payout::class); }

    public function scopePayable($q)
    {
        return $q->where('status', 'approved')->whereNull('payout_id')
            ->where('available_at', '<=', now());
    }

    public function getAmountAttribute(): float { return $this->amount_paise / 100; }
}
