<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateStageHistory;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\RecruitmentBatch;
use App\Models\RecruitmentStage;
use App\Services\RecruitmentService;
use App\Support\Tenancy\CurrentBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function __construct(private RecruitmentService $service)
    {
    }

    private function businessId(): int
    {
        return app(CurrentBusiness::class)->id();
    }

    /**
     * List view with source / stage / status filters.
     */
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.view'), 403);
        $this->service->ensureStages($this->businessId());

        $candidates = Candidate::with(['stage', 'designation', 'referrer', 'batch'])
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->when($request->filled('stage_id'), fn ($q) => $q->where('stage_id', $request->stage_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->batch_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('candidate_code', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stages = RecruitmentStage::where('business_id', $this->businessId())->ordered()->get();
        $batches = RecruitmentBatch::orderByDesc('drive_date')->get();
        $sources = Candidate::SOURCES;

        return view('admin.hr.recruitment.index', compact('candidates', 'stages', 'batches', 'sources'));
    }

    /**
     * Kanban pipeline (board) view grouped by stage.
     */
    public function pipeline(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.view'), 403);
        $this->service->ensureStages($this->businessId());

        $stages = RecruitmentStage::where('business_id', $this->businessId())->active()->ordered()->get();

        $candidates = Candidate::with(['designation', 'referrer'])
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->batch_id))
            ->orderByDesc('stage_changed_at')
            ->get()
            ->groupBy('stage_id');

        $batches = RecruitmentBatch::orderByDesc('drive_date')->get();
        $sources = Candidate::SOURCES;

        return view('admin.hr.recruitment.pipeline', compact('stages', 'candidates', 'batches', 'sources'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.create'), 403);

        return view('admin.hr.recruitment.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.create'), 403);
        $data = $this->validateCandidate($request);

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')->store('candidates/resumes', 'public');
        }

        $data['business_id'] = $this->businessId();
        $candidate = $this->service->create($data);

        return redirect()->route('admin.hr.recruitment.show', $candidate)
            ->with('success', 'Candidate added to the pipeline.');
    }

    public function show(Candidate $candidate)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.view'), 403);
        $candidate->load(['stage', 'department', 'designation', 'referrer', 'batch', 'creator',
            'history.fromStage', 'history.toStage', 'history.mover']);

        $stages = RecruitmentStage::where('business_id', $this->businessId())->active()->ordered()->get();

        return view('admin.hr.recruitment.show', compact('candidate', 'stages'));
    }

    public function edit(Candidate $candidate)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.edit'), 403);

        return view('admin.hr.recruitment.edit', array_merge(['candidate' => $candidate], $this->formData()));
    }

    public function update(Request $request, Candidate $candidate)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.edit'), 403);
        $data = $this->validateCandidate($request);

        if ($request->hasFile('resume')) {
            if ($candidate->resume_path) {
                Storage::disk('public')->delete($candidate->resume_path);
            }
            $data['resume_path'] = $request->file('resume')->store('candidates/resumes', 'public');
        }

        $candidate->update($data);

        return redirect()->route('admin.hr.recruitment.show', $candidate)
            ->with('success', 'Candidate updated.');
    }

    public function destroy(Candidate $candidate)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.delete'), 403);
        $candidate->delete();

        return redirect()->route('admin.hr.recruitment.index')->with('success', 'Candidate removed.');
    }

    /**
     * Move a candidate to a new pipeline stage (board drag, or stage dropdown).
     */
    public function move(Request $request, Candidate $candidate)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.edit'), 403);
        $request->validate([
            'stage_id' => ['required', 'exists:recruitment_stages,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $stage = RecruitmentStage::where('business_id', $this->businessId())->findOrFail($request->stage_id);
        $this->service->moveToStage($candidate, $stage, $request->remarks);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $candidate->status]);
        }

        return back()->with('success', "Moved to {$stage->name}.");
    }

    /**
     * Add a free-text note to the candidate timeline.
     */
    public function addNote(Request $request, Candidate $candidate)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.edit'), 403);
        $request->validate(['remarks' => ['required', 'string', 'max:1000']]);

        CandidateStageHistory::create([
            'business_id' => $candidate->business_id,
            'candidate_id' => $candidate->id,
            'from_stage_id' => $candidate->stage_id,
            'to_stage_id' => $candidate->stage_id,
            'action' => 'note',
            'remarks' => $request->remarks,
            'moved_by' => Auth::guard('admin')->id(),
        ]);

        return back()->with('success', 'Note added.');
    }

    /**
     * Generate (and stream) the offer letter PDF using candidate + business data.
     */
    public function offerLetter(Request $request, Candidate $candidate)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.view'), 403);

        // Persist offer details if supplied on the way to generating the letter.
        if ($request->isMethod('post')) {
            $request->validate([
                'offer_ctc' => ['nullable', 'numeric', 'min:0'],
                'offer_designation' => ['nullable', 'string', 'max:120'],
                'offer_date' => ['nullable', 'date'],
                'proposed_joining_date' => ['nullable', 'date'],
            ]);
            $candidate->update([
                'offer_ctc' => $request->offer_ctc ?? $candidate->offer_ctc ?? $candidate->expected_ctc,
                'offer_designation' => $request->offer_designation ?? $candidate->offer_designation ?? $candidate->designation?->name,
                'offer_date' => $request->offer_date ?? $candidate->offer_date ?? now()->toDateString(),
                'proposed_joining_date' => $request->proposed_joining_date ?? $candidate->proposed_joining_date,
                'offer_generated_at' => now(),
            ]);
        }

        $candidate->load('business', 'designation', 'department');
        $business = $candidate->business;

        $pdf = Pdf::loadView('admin.hr.recruitment.offer-letter', compact('candidate', 'business'));

        return $pdf->stream("offer-letter-{$candidate->candidate_code}.pdf");
    }

    /**
     * Validation rules shared by store / update.
     */
    private function validateCandidate(Request $request): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'current_location' => ['nullable', 'string', 'max:120'],
            'total_experience' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'current_ctc' => ['nullable', 'numeric', 'min:0'],
            'expected_ctc' => ['nullable', 'numeric', 'min:0'],
            'notice_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'source' => ['required', 'in:walkin,referral,campus,online,agency,other'],
            'referred_by_employee_id' => ['nullable', 'exists:employees,id'],
            'batch_id' => ['nullable', 'exists:recruitment_batches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'stage_id' => ['nullable', 'exists:recruitment_stages,id'],
            'applied_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);
        unset($data['resume']);

        return $data;
    }

    private function formData(): array
    {
        $businessId = $this->businessId();
        $this->service->ensureStages($businessId);

        return [
            'stages' => RecruitmentStage::where('business_id', $businessId)->active()->ordered()->get(),
            'batches' => RecruitmentBatch::where('status', true)->orderByDesc('drive_date')->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'designations' => Designation::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']),
            'sources' => Candidate::SOURCES,
        ];
    }
}
