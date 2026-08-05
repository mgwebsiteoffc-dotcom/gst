<?php

namespace App\Http\Controllers;

use App\Models\HsnCode;
use Illuminate\Http\Request;

class HsnCodeController extends Controller
{
    /** Search-as-you-type source for the HSN/SAC combobox on the item form. */
    public function search(Request $request)
    {
        return HsnCode::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('code', 'like', "$s%")
                ->orWhere('description', 'like', "%$s%")))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->orderBy('code')
            ->limit(20)
            ->get(['id', 'code', 'description', 'gst_rate']);
    }
}
