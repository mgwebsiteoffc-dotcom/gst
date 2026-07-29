<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.leads.index', [
            'leads' => Lead::when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $lead->update($request->validate(['status' => ['required', 'in:new,contacted,closed']]));

        return back()->with('success', 'Lead updated.');
    }
}
