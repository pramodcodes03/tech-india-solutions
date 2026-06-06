<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Exports\CandidateReportExport;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\RecruitmentBatch;
use App\Services\RecruitmentImportService;
use App\Services\RecruitmentService;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RecruitmentReportController extends Controller
{
    public function __construct(private RecruitmentService $service)
    {
    }

    private function businessId(): int
    {
        return app(CurrentBusiness::class)->id();
    }

    /**
     * Recruitment reports: source-wise conversion + stage-wise funnel.
     */
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.view'), 403);
        $businessId = $this->businessId();
        $this->service->ensureStages($businessId);

        $filters = $request->only(['source', 'batch_id', 'from', 'to']);
        $funnel = $this->service->funnel($businessId, $filters);

        // Source-wise conversion: total / hired / rejected per source.
        $base = Candidate::where('business_id', $businessId)
            ->when($filters['batch_id'] ?? null, fn ($q, $v) => $q->where('batch_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('applied_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('applied_at', '<=', $v));

        $bySource = (clone $base)->select('source',
            DB::raw('count(*) as total'),
            DB::raw("sum(case when status='hired' then 1 else 0 end) as hired"),
            DB::raw("sum(case when status='rejected' then 1 else 0 end) as rejected"))
            ->groupBy('source')->get();

        $batches = RecruitmentBatch::orderByDesc('drive_date')->get();
        $sources = Candidate::SOURCES;

        return view('admin.hr.recruitment.reports', compact('funnel', 'bySource', 'batches', 'sources', 'filters'));
    }

    public function export(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.view'), 403);
        $filters = $request->only(['source', 'stage_id', 'status', 'from', 'to']);

        return Excel::download(new CandidateReportExport($filters), 'recruitment-candidates-'.date('Y-m-d').'.xlsx');
    }

    /**
     * Bulk candidate import — upload form.
     */
    public function importForm()
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.create'), 403);

        return view('admin.hr.recruitment.import');
    }

    public function import(Request $request, RecruitmentImportService $importer)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.create'), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $result = $importer->import($request->file('file'), $this->businessId());

        $msg = "Imported {$result['imported']} candidates";
        if ($result['failed'] > 0) {
            $msg .= ", {$result['failed']} failed";
        }

        return redirect()->route('admin.hr.recruitment.index')
            ->with($result['failed'] > 0 ? 'warning' : 'success', $msg)
            ->with('import_errors', $result['errors']);
    }

    /**
     * Download a CSV template for the bulk import.
     */
    public function template()
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.create'), 403);
        $headers = ['First Name', 'Last Name', 'Email', 'Phone', 'Source', 'Experience', 'Expected CTC', 'Designation', 'Batch'];
        $sample = ['Asha', 'Verma', 'asha@example.com', '9876543210', 'campus', '0', '350000', 'Software Engineer', ''];

        $callback = function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        };

        return response()->streamDownload($callback, 'candidate-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
