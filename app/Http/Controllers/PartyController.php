<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartyRequest;
use App\Models\Party;
use App\Support\Gstin;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index(Request $request)
    {
        $parties = Party::query()
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
                ->orWhere('gstin', 'like', "%$s%")))
            ->withCount('documents')
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('parties.index', compact('parties'));
    }

    public function create()
    {
        return view('parties.form', ['party' => new Party(['type' => 'customer', 'gst_treatment' => 'unregistered']), 'states' => Gstin::STATES]);
    }

    public function store(PartyRequest $request)
    {
        $party = Party::create($this->prepare($request->validated()));

        if ($request->wantsJson()) {
            return response()->json($party);
        }

        return redirect()->route('parties.index')->with('success', __(':name saved.', ['name' => $party->name]));
    }

    public function edit(Party $party)
    {
        return view('parties.form', ['party' => $party, 'states' => Gstin::STATES]);
    }

    public function update(PartyRequest $request, Party $party)
    {
        $party->update($this->prepare($request->validated()));

        return redirect()->route('parties.index')->with('success', __(':name updated.', ['name' => $party->name]));
    }

    public function destroy(Party $party)
    {
        if ($party->documents()->exists()) {
            return back()->with('error', __('Cannot delete a contact that has documents. Archive it instead.'));
        }

        $party->delete();

        return back()->with('success', __('Contact deleted.'));
    }

    public function show(Party $party)
    {
        $party->load(['documents' => fn ($q) => $q->latest('issue_date')->limit(25)]);

        return view('parties.show', compact('party'));
    }

    /** Derive state and PAN from the GSTIN so the user types less. */
    protected function prepare(array $data): array
    {
        if (! empty($data['gstin'])) {
            $data['gstin'] = strtoupper($data['gstin']);
            $data['billing_state_code'] ??= Gstin::stateCode($data['gstin']);
            $data['billing_state'] ??= Gstin::stateName($data['billing_state_code'] ?? '');
            $data['pan'] ??= Gstin::pan($data['gstin']);
        }

        if (! empty($data['billing_state_code'])) {
            $data['billing_state'] = Gstin::stateName($data['billing_state_code']);
        }

        return $data;
    }
}
