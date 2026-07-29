<?php

namespace App\Services\Partner;

use App\Models\Commission;
use App\Models\Partner;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayoutService
{
    /** Bundle every cleared commission for a partner into one payout. */
    public function create(Partner $partner, ?int $processedBy = null): Payout
    {
        return DB::transaction(function () use ($partner, $processedBy) {
            $commissions = Commission::payable()
                ->where('partner_id', $partner->id)
                ->lockForUpdate()
                ->get();

            if ($commissions->isEmpty()) {
                throw new RuntimeException('This partner has no cleared commissions to pay.');
            }

            $total = (int) $commissions->sum('amount_paise');
            $minimum = $partner->commissionPlan?->min_payout_paise ?? 0;

            if ($total < $minimum) {
                throw new RuntimeException(
                    'Balance ₹'.number_format($total / 100, 2).
                    ' is below the ₹'.number_format($minimum / 100, 2).' minimum payout.'
                );
            }

            $payout = Payout::create([
                'partner_id' => $partner->id,
                'amount_paise' => $total,
                'commission_count' => $commissions->count(),
                'method' => $partner->upi ? 'upi' : 'bank',
                'status' => 'pending',
                'processed_by' => $processedBy,
            ]);

            Commission::whereIn('id', $commissions->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            return $payout;
        });
    }

    public function markPaid(Payout $payout, ?string $utr = null): Payout
    {
        DB::transaction(function () use ($payout, $utr) {
            $payout->update(['status' => 'paid', 'utr' => $utr, 'paid_at' => now()]);
            $payout->commissions()->update(['status' => 'paid']);
        });

        return $payout->refresh();
    }

    /** Release a failed payout so the commissions can be batched again. */
    public function markFailed(Payout $payout, ?string $note = null): Payout
    {
        DB::transaction(function () use ($payout, $note) {
            $payout->commissions()->update(['payout_id' => null]);
            $payout->update(['status' => 'failed', 'note' => $note]);
        });

        return $payout->refresh();
    }

    /** Approve commissions that have cleared their hold period. */
    public function approveMatured(): int
    {
        return Commission::where('status', 'pending')
            ->where('available_at', '<=', now())
            ->update(['status' => 'approved']);
    }
}
