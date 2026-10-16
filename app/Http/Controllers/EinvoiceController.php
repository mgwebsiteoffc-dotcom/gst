<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Einvoice\EinvoiceService;
use Illuminate\Http\Request;

class EinvoiceController extends Controller
{
    public function __construct(protected EinvoiceService $service) {}

    public function generate(Document $document)
    {
        try {
            $einvoice = $this->service->generate($document);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', app()->isProduction()
                ? __('Could not reach the e-Invoice portal. Please try again.')
                : $e->getMessage());
        }

        return back()->with('success', 'IRN generated: '.$einvoice->irn);
    }

    public function cancel(Request $request, Document $document)
    {
        $data = $request->validate([
            'reason_code' => ['required', 'in:1,2,3,4'],
            'remarks' => ['required', 'string', 'max:100'],
        ]);

        try {
            $this->service->cancel($document, $data['reason_code'], $data['remarks']);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', app()->isProduction()
                ? __('Could not reach the e-Invoice portal. Please try again.')
                : $e->getMessage());
        }

        return back()->with('success', 'IRN cancelled.');
    }

    public function ewayBill(Request $request, Document $document)
    {
        $data = $request->validate([
            'transport_mode' => ['required', 'in:1,2,3,4'],
            'vehicle_no' => ['nullable', 'string', 'max:15'],
            'transporter_id' => ['nullable', 'string', 'size:15'],
            'transporter_name' => ['nullable', 'string', 'max:100'],
            'distance_km' => ['required', 'numeric', 'min:0', 'max:4000'],
        ]);

        $ewb = $this->service->generateEwayBill($document, $data);

        return back()->with($ewb->status === 'generated' ? 'success' : 'error',
            $ewb->status === 'generated' ? 'e-Way Bill generated: '.$ewb->ewb_no : 'e-Way Bill generation failed.');
    }
}
