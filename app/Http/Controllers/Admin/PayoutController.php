<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\Partner\PayoutService;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(protected PayoutService $payouts) {}

    public function index(Request $request)
    {
        return view('admin.payouts.index', [
            'payouts' => Payout::with('partner')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest('id')->paginate(20)->withQueryString(),
            'pendingTotal' => Payout::whereIn('status', ['pending', 'processing'])->sum('amount_paise') / 100,
        ]);
    }

    public function update(Request $request, Payout $payout)
    {
        $data = $request->validate([
            'action' => ['required', 'in:paid,failed,processing'],
            'utr' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        match ($data['action']) {
            'paid' => $this->payouts->markPaid($payout, $data['utr'] ?? null),
            'failed' => $this->payouts->markFailed($payout, $data['note'] ?? null),
            'processing' => $payout->update(['status' => 'processing']),
        };

        return back()->with('success', 'Payout '.$payout->reference.' marked '.$data['action'].'.');
    }

    /** Approve every commission that has cleared its hold window. */
    public function approveMatured()
    {
        $count = $this->payouts->approveMatured();

        return back()->with('success', $count.' commission(s) approved and ready to pay.');
    }
}
