<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Services\Gst\DocumentService;
use App\Services\Share\ShareService;
use App\Support\Gstin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $service,
        protected ShareService $share,
    ) {}

    public function index(Request $request, string $type = 'invoice')
    {
        $documents = Document::with('party', 'einvoice')
            ->ofType($type)
            ->when($request->q, fn ($q, $s) => $q->where('number', 'like', "%$s%")
                ->orWhereHas('party', fn ($p) => $p->where('name', 'like', "%$s%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->from && $request->to, fn ($q) => $q->between($request->from, $request->to))
            ->latest('issue_date')->latest('id')
            ->paginate(25)->withQueryString();

        $summary = [
            'total' => (clone $documents)->getCollection()->sum('grand_total'),
            'outstanding' => Document::ofType($type)->whereNotIn('status', ['draft', 'cancelled', 'paid'])->sum('balance_due'),
        ];

        return view('invoices.index', compact('documents', 'type', 'summary'));
    }

    public function create(Request $request, string $type = 'invoice')
    {
        $tenant = \App\Support\TenantContext::get();
        $isPurchase = in_array($type, ['purchase_order', 'purchase_bill'], true);

        return view('invoices.form', [
            'document' => null,
            'type' => $type,
            'taxMode' => $request->get('tax_mode', $tenant->defaultTaxMode()),
            'tenant' => $tenant,
            'parties' => ($isPurchase ? Party::vendors() : Party::customers())->orderBy('name')->get(),
            'items' => Item::orderBy('name')->get(['id', 'name', 'sku', 'hsn_sac', 'uqc', 'sale_price', 'purchase_price', 'gst_rate', 'cess_rate', 'price_inclusive_tax', 'type']),
            'states' => Gstin::STATES,
        ]);
    }

    public function edit(Document $document)
    {
        abort_if($document->einvoice?->isGenerated(), 422, 'Cannot edit a document with a generated IRN.');

        $tenant = \App\Support\TenantContext::get();
        $isPurchase = in_array($document->doc_type, ['purchase_order', 'purchase_bill'], true);
        $document->load('lines.item', 'party');

        return view('invoices.form', [
            'document' => $document,
            'type' => $document->doc_type,
            'taxMode' => $document->tax_mode,
            'tenant' => $tenant,
            'parties' => ($isPurchase ? Party::vendors() : Party::customers())->orderBy('name')->get(),
            'items' => Item::orderBy('name')->get(['id', 'name', 'sku', 'hsn_sac', 'uqc', 'sale_price', 'purchase_price', 'gst_rate', 'cess_rate', 'price_inclusive_tax', 'type']),
            'states' => Gstin::STATES,
        ]);
    }

    public function store(DocumentRequest $request)
    {
        $document = $this->service->create($request->validated());

        return redirect()->route('documents.show', $document)->with('success', $document->title.' '.$document->number.' created.');
    }

    public function show(Document $document)
    {
        $document->load('lines.item', 'party', 'payments', 'einvoice', 'ewayBill');

        return view('invoices.show', [
            'document' => $document,
            'share' => [
                'whatsapp' => $this->share->whatsappUrl($document),
                'email' => $this->share->mailtoUrl($document),
                'upi' => $this->share->upiUri($document),
                'public' => $this->share->publicUrl($document),
            ],
        ]);
    }

    /** Signed public link — lets a customer view the invoice without an account. */
    public function publicView(Document $document)
    {
        $document->load('lines.item', 'party', 'einvoice', 'tenant');

        return view('invoices.public', [
            'document' => $document,
            'upi' => $this->share->upiUri($document),
        ]);
    }

    public function update(DocumentRequest $request, Document $document)
    {
        abort_if($document->einvoice?->isGenerated(), 422, 'Cannot edit a document with a generated IRN. Issue a credit note.');
        $this->service->update($document, $request->validated());

        return redirect()->route('documents.show', $document)->with('success', 'Document updated.');
    }

    public function pdf(Document $document, Request $request)
    {
        $document->load('lines.item', 'party', 'einvoice', 'tenant');
        $pdf = Pdf::loadView('invoices.pdf', [
            'document' => $document,
            'template' => $request->get('template', $document->tenant->setting('pdf_template', 'classic')),
        ])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download(str_replace('/', '-', $document->number).'.pdf')
            : $pdf->stream();
    }

    public function convert(Document $document, string $target)
    {
        $new = $this->service->convert($document, $target);

        return redirect()->route('documents.show', $new)->with('success', 'Converted to '.$new->title.' '.$new->number);
    }

    public function payment(Request $request, Document $document)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:'.max(0.01, (float) $document->balance_due)],
            'paid_on' => ['required', 'date'],
            'method' => ['required', 'string'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $this->service->recordPayment($document, (float) $data['amount'], $data);

        return back()->with('success', 'Payment recorded.');
    }

    public function cancel(Request $request, Document $document)
    {
        $this->service->cancel($document, $request->get('reason'));

        return back()->with('success', 'Document cancelled.');
    }
}
