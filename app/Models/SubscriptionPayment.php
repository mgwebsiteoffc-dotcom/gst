<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function plan() { return $this->belongsTo(Plan::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }

    public function amountLabel(): string { return '₹'.number_format($this->total_paise / 100, 2); }

    /**
     * GST split for the invoice: inter-state (IGST) when the buyer's state
     * differs from the seller's, else CGST + SGST.
     */
    public function taxSplit(): array
    {
        $sellerState = config('billing.seller.state_code');
        $buyerState = $this->tenant?->state_code;

        $interstate = $sellerState && $buyerState && $sellerState !== $buyerState;

        return [
            'interstate' => $interstate,
            'igst' => $interstate ? $this->gst_paise : 0,
            'cgst' => $interstate ? 0 : intdiv($this->gst_paise, 2),
            'sgst' => $interstate ? 0 : $this->gst_paise - intdiv($this->gst_paise, 2),
        ];
    }

    /** Invoice numbers read to the customer in Indian FY format. */
    public static function nextInvoiceNumber(int $tenantId): string
    {
        $fy = \App\Services\Gst\DocumentNumberGenerator::financialYear(now());

        $count = static::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('invoice_number', 'like', 'BB/'.$fy.'/%')
            ->count();

        return 'BB/'.$fy.'/'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
