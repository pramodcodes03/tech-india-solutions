<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkImportLog;
use App\Services\Import\BulkImportService;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BulkImportController extends Controller
{
    public function __construct(private BulkImportService $service)
    {
    }

    private function bid(): int
    {
        return app(CurrentBusiness::class)->id();
    }

    private function gate(string $key)
    {
        $importer = $this->service->importer($key);
        abort_if($importer === null, 404);
        abort_unless(Auth::guard('admin')->user()->can('bulk_imports.run')
            && Auth::guard('admin')->user()->can($importer->permission()), 403);

        return $importer;
    }

    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('bulk_imports.run'), 403);
        $importers = $this->service->all();
        $logs = BulkImportLog::with('admin')->latest()->limit(20)->get();

        return view('admin.imports.index', compact('importers', 'logs'));
    }

    public function form(string $key)
    {
        $importer = $this->gate($key);

        return view('admin.imports.form', compact('importer'));
    }

    /**
     * Step 1: upload → validate → show preview (errors + valid count).
     */
    public function preview(Request $request, string $key)
    {
        $importer = $this->gate($key);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $parsed = $this->service->parse($request->file('file'));
        if ($parsed['error']) {
            return back()->withErrors(['file' => $parsed['error']]);
        }

        $result = $this->service->validate($parsed['rows'], $importer, $this->bid());

        // Stash the valid rows for the confirm step.
        session(["import.{$key}.valid" => $result['valid'], "import.{$key}.file" => $request->file('file')->getClientOriginalName()]);

        return view('admin.imports.preview', [
            'importer' => $importer,
            'valid' => $result['valid'],
            'errors' => $result['errors'],
            'total' => count($parsed['rows']),
        ]);
    }

    /**
     * Step 2: confirm → import the previously-validated rows.
     */
    public function confirm(string $key)
    {
        $importer = $this->gate($key);
        $valid = session("import.{$key}.valid", []);
        $fileName = session("import.{$key}.file");

        if (empty($valid)) {
            return redirect()->route('admin.imports.form', $key)->withErrors(['file' => 'No validated rows found — please upload again.']);
        }

        $result = $this->service->import($valid, $importer, $this->bid(), $fileName);
        session()->forget(["import.{$key}.valid", "import.{$key}.file"]);

        $msg = "Imported {$result['imported']} {$importer->label()}";
        if ($result['failed'] > 0) {
            $msg .= ", {$result['failed']} failed";
        }

        return redirect()->route('admin.imports.index')
            ->with($result['failed'] > 0 ? 'warning' : 'success', $msg);
    }

    public function template(string $key)
    {
        $importer = $this->gate($key);
        $callback = function () use ($importer) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $importer->templateHeaders());
            fputcsv($out, $importer->sampleRow());
            fclose($out);
        };

        return response()->streamDownload($callback, "{$key}-import-template.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * Download the error report from a past import log.
     */
    public function errorReport(BulkImportLog $log)
    {
        abort_unless(Auth::guard('admin')->user()->can('bulk_imports.run'), 403);
        $callback = function () use ($log) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Row', 'Errors']);
            foreach (($log->errors ?? []) as $err) {
                fputcsv($out, [$err['row'] ?? '', implode('; ', $err['messages'] ?? [])]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, "import-errors-{$log->id}.csv", ['Content-Type' => 'text/csv']);
    }
}
