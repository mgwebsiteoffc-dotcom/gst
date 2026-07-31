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

    public function index(Request $request)
    {
        $tenant = TenantContext::get();
        $history = ImportJob::where('tenant_id', $tenant->id)
            ->with('user')->orderByDesc('created_at')->limit(25)->get();
        return view('migration.index', ['history' => $history]);
    }

    public function upload(Request $request)
    {
        $request->validate(['backup_file' => ['required', 'file', 'mimes:json,txt', 'max:51200']]);
        $tenant = TenantContext::get();
        $file = $request->file('backup_file');

        try {
            $job = ImportJob::create([
                'tenant_id' => $tenant->id, 'user_id' => Auth::id(),
                'filename' => $file->getClientOriginalName(), 'status' => 'pending',
            ]);
            $result = $this->parser->parse($file, $job);
            $mapping = $this->mapper->buildMapping($result['sections']);
            $job->update(['mapping' => $mapping, 'status' => 'ready']);
            return redirect()->route('migration.review', $job);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to parse backup file: ' . $e->getMessage());
        }
    }

    public function review(ImportJob $job)
    {
        $this->authorizeJob($job);
        if (!$job->isReady() && !$job->isPending()) {
            return redirect()->route('migration.show', $job)->with('info', 'This import has already been processed.');
        }
        $data = $this->parser->loadData($job);
        $mapping = $job->mapping ?? [];
        $validation = $this->validator->validate($data, $job, array_map(fn ($m) => $m['action'] ?? 'import', $mapping));
        return view('migration.review', ['job' => $job, 'mapping' => $mapping, 'validation' => $validation, 'actions' => $this->mapper->availableActions()]);
    }

    public function updateMapping(Request $request, ImportJob $job)
    {
        $this->authorizeJob($job);
        $request->validate(['actions' => ['required', 'array'], 'actions.*' => ['required', 'in:import,merge,skip']]);
        $mapping = $job->mapping ?? [];
        foreach ($request->input('actions', []) as $section => $action) {
            if (isset($mapping[$section])) $mapping[$section]['action'] = $action;
        }
        $job->update(['mapping' => $mapping]);
        return redirect()->route('migration.review', $job);
    }

    public function execute(ImportJob $job)
    {
        $this->authorizeJob($job);
        if (!$job->isReady()) return redirect()->route('migration.show', $job)->with('error', 'This import cannot be executed in its current state.');
        $job->markImporting();
        $data = $this->parser->loadData($job);
        $mapping = $job->mapping ?? [];
        $this->validator->validate($data, $job, array_map(fn ($m) => $m['action'] ?? 'import', $mapping));
        $this->importer->import($job);
        return redirect()->route('migration.show', $job);
    }

    public function show(ImportJob $job)
    {
        $this->authorizeJob($job);
        $job->load('user');
        return view('migration.show', ['job' => $job]);
    }

    public function progress(ImportJob $job)
    {
        $this->authorizeJob($job);
        return response()->json([
            'status' => $job->status, 'current_section' => $job->current_section,
            'processed' => $job->processed_records, 'total' => $job->total_records,
            'percent' => $job->total_records > 0 ? round(($job->processed_records / $job->total_records) * 100) : 0,
            'summary' => $job->summary,
        ]);
    }

    public function destroy(ImportJob $job)
    {
        $this->authorizeJob($job);
        $this->parser->cleanup($job);
        $job->delete();
        return redirect()->route('migration.index')->with('success', 'Import record deleted.');
    }

    public function rawData(Request $request)
    {
        $tenant = TenantContext::get();
        $query = \App\Models\MigrationRawData::where('tenant_id', $tenant->id);
        if ($section = $request->get('section')) $query->where('section', $section);
        if ($search = $request->get('q')) $query->search($search);
        if ($status = $request->get('status')) $query->where('status', $status);
        $records = $query->latest()->paginate(50)->withQueryString();
        $sections = \App\Models\MigrationRawData::where('tenant_id', $tenant->id)
            ->select('section')->selectRaw('count(*) as count')->groupBy('section')->orderByDesc('count')->pluck('count', 'section');
        return view('migration.raw-data', ['records' => $records, 'sections' => $sections, 'currentSection' => $section, 'search' => $search, 'currentStatus' => $status]);
    }

    protected function authorizeJob(ImportJob $job): void
    {
        $tenant = TenantContext::get();
        if (!$tenant || $job->tenant_id !== $tenant->id) abort(403, 'Unauthorized access to this import job.');
    }
}