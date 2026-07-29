<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommissionPlanController extends Controller
{
    public function index()
    {
        return view('admin.commission-plans.index', [
            'plans' => CommissionPlan::withCount('partners')->get(),
        ]);
    }

    public function store(Request $request)
    {
        CommissionPlan::create($this->validated($request));

        return back()->with('success', 'Commission plan created.');
    }

    public function update(Request $request, CommissionPlan $commissionPlan)
    {
        $commissionPlan->update($this->validated($request, $commissionPlan));

        return back()->with('success', 'Commission plan updated.');
    }

    public function destroy(CommissionPlan $commissionPlan)
    {
        if ($commissionPlan->partners()->exists()) {
            return back()->with('error', 'Partners are on this plan. Deactivate it instead of deleting.');
        }

        $commissionPlan->delete();

        return back()->with('success', 'Commission plan deleted.');
    }

    protected function validated(Request $request, ?CommissionPlan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'partner_type' => ['required', Rule::in(['channel', 'affiliate'])],
            'model' => ['required', Rule::in(['percentage', 'flat', 'hybrid'])],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'flat_rupees' => ['nullable', 'numeric', 'min:0'],
            'recurring_months' => ['required', 'integer', 'min:0', 'max:120'],
            'cookie_days' => ['required', 'integer', 'min:1', 'max:365'],
            'min_payout_rupees' => ['required', 'numeric', 'min:0'],
            'hold_days' => ['required', 'integer', 'min:0', 'max:180'],
            'active' => ['boolean'],
        ]);

        // The UI works in rupees; storage is in paise to avoid float drift.
        $data['flat_paise'] = (int) round(($data['flat_rupees'] ?? 0) * 100);
        $data['min_payout_paise'] = (int) round($data['min_payout_rupees'] * 100);
        $data['active'] = $request->boolean('active');
        $data['slug'] = $plan?->slug ?? Str::slug($data['name']).'-'.Str::lower(Str::random(4));

        unset($data['flat_rupees'], $data['min_payout_rupees']);

        return $data;
    }
}
