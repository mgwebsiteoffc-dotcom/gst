<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionPlan extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'model' => 'percentage',
        'partner_type' => 'affiliate',
        'percentage' => 20,
        'flat_paise' => 0,
        'recurring_months' => 12,
        'cookie_days' => 60,
        'active' => true,
    ];

    protected function casts(): array { return ['active' => 'boolean']; }

    public function partners() { return $this->hasMany(Partner::class); }

    /**
     * Commission on one payment.
     *
     * $monthNumber is which recurring payment this is (1 = first). Returning 0
     * past `recurring_months` is what stops commissions running forever.
     */
    public function commissionOn(int $basePaise, int $monthNumber = 1): int
    {
        if ($this->recurring_months > 0 && $monthNumber > $this->recurring_months) {
            return 0;
        }

        return match ($this->model) {
            'flat' => $monthNumber === 1 ? (int) $this->flat_paise : 0,
            'hybrid' => (int) round($basePaise * $this->percentage / 100)
                + ($monthNumber === 1 ? (int) $this->flat_paise : 0),
            default => (int) round($basePaise * $this->percentage / 100),
        };
    }

    public function describe(): string
    {
        return match ($this->model) {
            'flat' => '₹'.number_format($this->flat_paise / 100).' per signup',
            'hybrid' => $this->percentage.'% + ₹'.number_format($this->flat_paise / 100).' bonus',
            default => $this->percentage.'% of every payment',
        };
    }
}
