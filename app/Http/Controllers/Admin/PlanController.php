<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        return view('admin.plans.index', [
            'plans' => Plan::withCount('subscriptions')->orderBy('price_paise')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Plan::create($this->validated($request));

        return back()->with('success', 'Plan created.');
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request, $plan));

        return back()->with('success', $plan->name.' updated.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Businesses are subscribed to this plan. Deactivate it instead.');
        }

        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }

    protected function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'price_rupees' => ['required', 'numeric', 'min:0'],
            'yearly_price_rupees' => ['nullable', 'numeric', 'min:0'],
            'invoice_limit' => ['nullable', 'integer', 'min:1'],
            'user_limit' => ['required', 'integer', 'min:1', 'max:500'],
            'einvoice' => ['boolean'],
            'shopify' => ['boolean'],
            'multi_branch' => ['boolean'],
            'active' => ['boolean'],
        ]);

        $data['price_paise'] = (int) round($data['price_rupees'] * 100);
        $data['yearly_price_paise'] = $data['yearly_price_rupees']
            ? (int) round($data['yearly_price_rupees'] * 100) : null;

        foreach (['einvoice', 'shopify', 'multi_branch', 'active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $data['slug'] = $plan?->slug ?? Str::slug($data['name']);

        unset($data['price_rupees'], $data['yearly_price_rupees']);

        return $data;
    }
}
