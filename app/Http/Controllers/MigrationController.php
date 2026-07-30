<?php

namespace App\Http\Controllers;

use App\Models\ImportJob;
use App\Services\Migration\BackupParser;
use App\Services\Migration\BackupValidator;
use App\Services\Migration\DataImporter;
use App\Services\Migration\MigrationMapper;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MigrationController extends Controller
{
    public function __construct(
        protected BackupParser $parser,
        protected BackupValidator $validator,
        protected MigrationMapper $mapper,
        protected DataImporter $importer,
    ) {}

    /**
     * Show the migration dashboard / upload page.
     */
    public function index(Request $request)
    {
        $tenant = TenantContext::get();
        $history = ImportJob::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        return view('migration.index', [
            'history' => $history,
        ]);
    }

    /**
     * Step 1: Upload and parse the JSON backup.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json,txt', 'max:51200'], // 50MB max
        ]);

        $tenant = TenantContext::get();
        $file = $request->file('backup_file');

        try {
            // Create import job record
            $job = ImportJob::create([
                'tenant_id' => $tenant->id,
                'user_id' => Auth::id(),
                'filename' => $file->getClientOriginalName(),
                'status' => 'pending',
            ]);

            // Parse the file
            $result = $this->parser->parse($file, $job);

            // Build mapping for detected sections
            $mapping = $this->mapper->buildMapping($result['sections']);

            $job->update([
                'mapping' => $mapping,
                'status' => 'ready',
            ]);

            return redirect()->route('migration.review', $job);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to parse backup file: ' . $e->getMessage());
        }
    }

    /**
     * Step 2: Review detected sections and configure mapping.
     */
    public function review(ImportJob $job)
    {
        $this->authorizeJob($job);

        if (!$job->isReady() && !$job->isPending()) {
            return redirect()->route('migration.show', $job)
                ->with('info', 'This import has already been processed.');
        }

        // Run validation
        $data = $this->parser->loadData($job);
        $mapping = $job->mapping ?? [];

        $validation = $this->validator->validate($data, $job, array_map(
            fn ($m) => $m['action'] ?? 'import',
            $mapping
        ));

        return view('migration.review', [
            'job' => $job,
            'mapping' => $mapping,
            'validation' => $validation,
            'actions' => $this->mapper->availableActions(),
        ]);
    }

    /**
     * Step 3: Update mapping choices.
     */
    public function updateMapping(Request $request, ImportJob $job)
    {
        $this->authorizeJob($job);

        $request->validate([
            'actions' => ['required', 'array'],
            'actions.*' => ['required', 'in:import,merge,skip'],
        ]);

        $mapping = $job->mapping ?? [];

        foreach ($request->input('actions', []) as $section => $action) {
            if (isset($mapping[$section])) {
                $mapping[$section]['action'] = $action;
            }
        }

        $job->update(['mapping' => $mapping]);

        return redirect()->route('migration.review', $job);
    }

    /**
     * Step 4: Execute the import.
     */
    public function execute(ImportJob $job)
    {
        $this->authorizeJob($job);

        if (!$job->isReady()) {
            return redirect()->route('migration.show', $job)
                ->with('error', 'This import cannot be executed in its current state.');
        }

        // Mark as importing so double-clicks don't fire twice
        $job->markImporting();

        // Re-validate the mapping just in case
        $data = $this->parser->loadData($job);
        $mapping = $job->mapping ?? [];
        $this->validator->validate($data, $job, array_map(
            fn ($m) => $m['action'] ?? 'import',
            $mapping
        ));

        $result = $this->importer->import($job);

        return redirect()->route('migration.show', $job);
    }

    /**
     * Show import job details / results.
     */
    public function show(ImportJob $job)
    {
        $this->authorizeJob($job);

        return view('migration.show', [
            'job' => $job,
        ]);
    }

    /**
     * AJAX endpoint: get import progress.
     */
    public function progress(ImportJob $job)
    {
        $this->authorizeJob($job);

        return response()->json([
            'status' => $job->status,
            'current_section' => $job->current_section,
            'processed' => $job->processed_records,
            'total' => $job->total_records,
            'percent' => $job->total_records > 0
                ? round(($job->processed_records / $job->total_records) * 100)
                : 0,
            'summary' => $job->summary,
        ]);
    }

    /**
     * Delete an import job record.
     */
    public function destroy(ImportJob $job)
    {
        $this->authorizeJob($job);

        $this->parser->cleanup($job);
        $job->delete();

        return redirect()->route('migration.index')
            ->with('success', 'Import record deleted.');
    }

    /**
     * Verify the job belongs to the current tenant.
     */
    protected function authorizeJob(ImportJob $job): void
    {
        $tenant = TenantContext::get();

        if (!$tenant || $job->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this import job.');
        }
    }
}
