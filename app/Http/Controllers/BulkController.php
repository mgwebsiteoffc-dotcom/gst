<?php

namespace App\Http\Controllers;

use App\Services\Bulk\BulkExcelService;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class BulkController extends Controller
{
    public function __construct(protected BulkExcelService $excel) {}

    /**
     * Main bulk import/export dashboard.
     */
    public function index()
    {
        $tenant = TenantContext::get();
        $definitions = $this->excel->definitions();

        // Get record counts for each entity
        $counts = [
            'parties' => \App\Models\Party::where('tenant_id', $tenant->id)->count(),
            'items' => \App\Models\Item::where('tenant_id', $tenant->id)->count(),
            'documents' => \App\Models\Document::where('tenant_id', $tenant->id)
                ->whereIn('doc_type', ['invoice', 'quotation', 'credit_note', 'debit_note', 'purchase_order', 'purchase_bill', 'proforma', 'delivery_challan', 'bill_of_supply'])
                ->count(),
            'payments' => \App\Models\Payment::where('tenant_id', $tenant->id)->count(),
        ];

        return view('bulk.index', [
            'definitions' => $definitions,
            'counts' => $counts,
        ]);
    }

    /**
     * Download a sample Excel file.
     */
    public function sample(string $entity)
    {
        return $this->excel->generateSample($entity);
    }

    /**
     * Export existing data to Excel.
     */
    public function export(string $entity)
    {
        return $this->excel->export($entity);
    }

    /**
     * Step 1: Upload and preview Excel data.
     */
    public function preview(Request $request, string $entity)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // 10MB
        ]);

        $result = $this->excel->parseUpload($request->file('file'), $entity);

        // Store parsed data in session for the execute step
        session(["bulk_import_{$entity}" => $result['rows']]);

        $def = $this->excel->definitions()[$entity];

        return view('bulk.preview', [
            'entity' => $entity,
            'definition' => $def,
            'result' => $result,
        ]);
    }

    /**
     * Step 2: Execute the import.
     */
    public function execute(Request $request, string $entity)
    {
        $rows = session("bulk_import_{$entity}");

        if (empty($rows)) {
            return redirect()->route('bulk.index')->with('error', 'No data to import. Please upload a file first.');
        }

        $mode = $request->input('mode', 'import'); // import or merge

        $result = $this->excel->executeImport($rows, $entity, $mode);

        // Clear session
        session()->forget("bulk_import_{$entity}");

        $stats = $result['stats'];
        $message = "Import complete: {$stats['imported']} created";
        if ($stats['updated'] > 0) $message .= ", {$stats['updated']} updated";
        if ($stats['skipped'] > 0) $message .= ", {$stats['skipped']} skipped";
        if ($stats['failed'] > 0) $message .= ", {$stats['failed']} failed";

        $flash = $stats['failed'] > 0 ? 'error' : 'success';

        return redirect()->route('bulk.index')->with($flash, $message . '.');
    }
}