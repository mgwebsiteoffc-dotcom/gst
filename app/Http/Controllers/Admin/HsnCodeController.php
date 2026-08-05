<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HsnCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HsnCodeController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.hsn-codes.index', [
            'hsnCodes' => HsnCode::query()
                ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                    ->where('code', 'like', "%$s%")
                    ->orWhere('description', 'like', "%$s%")))
                ->when($request->type, fn ($q, $t) => $q->where('type', $t))
                ->orderBy('code')
                ->paginate(25)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        HsnCode::create($this->validated($request));

        return back()->with('success', 'HSN/SAC code added.');
    }

    public function update(Request $request, HsnCode $hsnCode)
    {
        $hsnCode->update($this->validated($request, $hsnCode));

        return back()->with('success', $hsnCode->code.' updated.');
    }

    public function destroy(HsnCode $hsnCode)
    {
        $hsnCode->delete();

        return back()->with('success', 'HSN/SAC code deleted.');
    }

    protected function validated(Request $request, ?HsnCode $hsnCode = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:8', Rule::unique('hsn_codes', 'code')->ignore($hsnCode?->id)],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['goods', 'service'])],
            'gst_rate' => ['nullable', 'numeric', Rule::in(config('gst.slabs'))],
        ]);
    }
}
