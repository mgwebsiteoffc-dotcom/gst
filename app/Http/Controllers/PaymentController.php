<?php
namespace App\Http\Controllers;
use App\Models\Document;
use App\Models\Party;
use App\Models\Payment;
use App\Services\Gst\DocumentService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PaymentController extends Controller
{
    public function __construct(protected DocumentService $service) {}
    /**
     * List all invoices with payment status — the main Payments Received dashboard.
     */
    public function index(Request $request)
    {
        $tenant = TenantContext::get();
        $query = Document::where('tenant_id', $tenant->id)
            ->whereIn('doc_type', ['invoice', 'bill_of_supply'])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->with(['party', 'payments' => fn ($q) => $q->latest('paid_on')]);
        // Filters
        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->where('issue_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('issue_date', '<=', $request->to);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('number', 'like', "%{$q}%")
                    ->orWhereHas('party', fn ($p) => $p->where('name', 'like', "%{$q}%"));
            });
        }
        $documents = $query->latest('issue_date')->latest('id')->paginate(25)->withQueryString();
        // Summary stats
        $allDocs = Document::where('tenant_id', $tenant->id)
            ->whereIn('doc_type', ['invoice', 'bill_of_supply'])
            ->whereNotIn('status', ['draft', 'cancelled']);
        $totalInvoiced = (clone $allDocs)->sum('grand_total');
        $totalReceived = (clone $allDocs)->sum('amount_paid');
        $totalOutstanding = (clone $allDocs)->sum('balance_due');
        $overdueCount = (clone $allDocs)->where('status', 'overdue')->count();
        $overdueAmount = (clone $allDocs)->where('status', 'overdue')->sum('balance_due');
        // Parties for filter dropdown
        $parties = Party::where('tenant_id', $tenant->id)
            ->whereHas('documents', fn ($q) => $q->whereIn('doc_type', ['invoice', 'bill_of_supply']))
            ->orderBy('name')->get(['id', 'name']);
        // Recent payments (last 10)
        $recentPayments = Payment::where('tenant_id', $tenant->id)
            ->where('direction', 'in')
            ->with(['document', 'party'])
            ->latest('paid_on')->latest('id')
            ->limit(10)->get();
        return view('payments.index', compact(
            'documents', 'parties', 'recentPayments',
            'totalInvoiced', 'totalReceived', 'totalOutstanding', 'overdueCount', 'overdueAmount'
        ));
    }
    /**
     * Show payment history for a specific invoice.
     */
    public function show(Document $document)
    {
        $document->load(['party', 'payments' => fn ($q) => $q->latest('paid_on')]);
        return view('payments.show', compact('document'));
    }
    /**
     * Record a payment against an invoice.
     */
    public function store(Request $request, Document $document)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:' . max(0.01, (float) $document->balance_due)],
            'paid_on' => ['required', 'date'],
            'method' => ['required', 'string', 'in:cash,bank,upi,card,cheque,gateway'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $this->service->recordPayment($document, (float) $data['amount'], $data);
        return back()->with('success', 'Payment of ₹' . number_format((float) $data['amount'], 2) . ' recorded against ' . $document->number . '.');
    }
    /**
     * Delete a payment and recalculate the invoice balance.
     */
    public function destroy(Document $document, Payment $payment)
    {
        if ($payment->document_id !== $document->id) {
            abort(403, 'Payment does not belong to this invoice.');
        }
        DB::transaction(function () use ($document, $payment) {
            $payment->delete();
            $paid = (float) $document->payments()->sum('amount');
            $document->amount_paid = $paid;
            $document->balance_due = max(0, (float) $document->grand_total - $paid);
            $document->status = $document->balance_due <= 0.009
                ? 'paid'
                : ($paid > 0 ? 'partially_paid' : 'sent');
            $document->save();
        });
        return back()->with('success', 'Payment deleted. Invoice balance recalculated.');
    }
}