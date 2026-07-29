<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%$s%")
                ->orWhere('sku', 'like', "%$s%")
                ->orWhere('hsn_sac', 'like', "%$s%")))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('items.index', compact('items'));
    }

    public function create()
    {
        return view('items.form', ['item' => new Item(['type' => 'goods', 'uqc' => 'NOS', 'gst_rate' => 18, 'track_inventory' => true])]);
    }

    public function store(Request $request)
    {
        $item = Item::create($this->validated($request));

        if ($request->wantsJson()) {
            return response()->json($item);
        }

        return redirect()->route('items.index')->with('success', $item->name.' saved.');
    }

    public function edit(Item $item)
    {
        return view('items.form', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        $item->update($this->validated($request, $item));

        return redirect()->route('items.index')->with('success', $item->name.' updated.');
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return back()->with('success', 'Item deleted.');
    }

    protected function validated(Request $request, ?Item $item = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('items', 'sku')
                ->where('tenant_id', $request->user()->tenant_id)->ignore($item?->id)],
            'barcode' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['goods', 'service'])],
            'hsn_sac' => ['nullable', 'string', 'max:8'],
            'uqc' => ['required', 'string', 'max:8'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'gst_rate' => ['required', 'numeric', Rule::in(config('gst.slabs'))],
            'cess_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'price_inclusive_tax' => ['boolean'],
            'track_inventory' => ['boolean'],
            'opening_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Services are never stock-tracked.
        if ($data['type'] === 'service') {
            $data['track_inventory'] = false;
            $data['opening_stock'] = 0;
        }

        foreach (['price_inclusive_tax', 'track_inventory'] as $flag) {
            $data[$flag] = (bool) ($data[$flag] ?? false);
        }

        return $data;
    }
}
