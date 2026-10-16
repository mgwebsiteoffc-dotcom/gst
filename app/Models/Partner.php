<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Partner extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'distributor' => 'Distributor',
        'channel' => 'Channel Partner',
        'affiliate' => 'Influencer / Affiliate',
    ];
    public const TIERS = ['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold'];

    protected $guarded = [];

    protected $attributes = [
        'type' => 'affiliate',
        'status' => 'pending',
        'tier' => 'bronze',
        'audience_size' => 0,
    ];

    protected function casts(): array
    {
        return ['social' => 'array', 'approved_at' => 'datetime'];
    }

    public static function booted(): void
    {
        static::creating(function (self $partner) {
            $partner->referral_code ??= self::generateCode($partner->name);
        });
    }

    /** Readable, unambiguous code — no O/0 or I/1 confusion when read aloud. */
    public static function generateCode(string $name): string
    {
        $base = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'PART', 0, 4));
        $base = str_pad($base, 4, 'X');

        do {
            $code = $base.Str::upper(Str::random(4));
            $code = strtr($code, ['O' => 'Q', '0' => '9', 'I' => 'J', '1' => '7']);
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    public function user() { return $this->belongsTo(User::class); }
    public function commissionPlan() { return $this->belongsTo(CommissionPlan::class); }
    public function referrals() { return $this->hasMany(Referral::class); }
    public function commissions() { return $this->hasMany(Commission::class); }
    public function payouts() { return $this->hasMany(Payout::class); }
    public function clicks() { return $this->hasMany(ReferralClick::class); }

    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopeChannel($q) { return $q->where('type', 'channel'); }
    public function scopeAffiliate($q) { return $q->where('type', 'affiliate'); }

    public function isApproved(): bool { return $this->status === 'approved'; }
    public function typeLabel(): string { return self::TYPES[$this->type] ?? $this->type; }

    public function referralUrl(): string
    {
        return url('/').'?ref='.$this->referral_code;
    }

    /** Commission cleared its hold period and is not yet paid. */
    public function availableBalance(): int
    {
        return (int) $this->commissions()
            ->where('status', 'approved')
            ->whereNull('payout_id')
            ->where('available_at', '<=', now())
            ->sum('amount_paise');
    }

    public function pendingBalance(): int
    {
        return (int) $this->commissions()
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('payout_id')
            ->where(fn ($q) => $q->whereNull('available_at')->orWhere('available_at', '>', now()))
            ->sum('amount_paise');
    }

    public function lifetimeEarned(): int
    {
        return (int) $this->commissions()->whereNot('status', 'reversed')->sum('amount_paise');
    }

    public function totalPaid(): int
    {
        return (int) $this->payouts()->where('status', 'paid')->sum('amount_paise');
    }

    public function conversionRate(): float
    {
        $clicks = $this->clicks()->count();

        return $clicks > 0 ? round($this->referrals()->count() / $clicks * 100, 1) : 0.0;
    }
}
