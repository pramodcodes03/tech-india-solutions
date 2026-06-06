<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentStage;
use App\Services\RecruitmentService;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RecruitmentStageController extends Controller
{
    public function __construct(private RecruitmentService $service)
    {
    }

    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.manage_stages'), 403);
        $businessId = app(CurrentBusiness::class)->id();
        $this->service->ensureStages($businessId);

        $stages = RecruitmentStage::where('business_id', $businessId)->ordered()
            ->withCount('candidates')->get();

        return view('admin.hr.recruitment.stages.index', compact('stages'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.manage_stages'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:open,hired,rejected'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $businessId = app(CurrentBusiness::class)->id();
        $max = RecruitmentStage::where('business_id', $businessId)->max('sort_order') ?? 0;

        RecruitmentStage::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($businessId, $data['name']),
            'type' => $data['type'],
            'color' => $data['color'] ?? '#6366f1',
            'sort_order' => $max + 1,
            'status' => true,
        ]);

        return back()->with('success', 'Stage added.');
    }

    public function update(Request $request, RecruitmentStage $stage)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.manage_stages'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:open,hired,rejected'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'boolean'],
        ]);

        $stage->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'color' => $data['color'] ?? $stage->color,
            'status' => $request->boolean('status'),
        ]);

        return back()->with('success', 'Stage updated.');
    }

    public function destroy(RecruitmentStage $stage)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.manage_stages'), 403);
        if ($stage->candidates()->exists()) {
            return back()->withErrors(['stage' => 'Cannot delete a stage that still has candidates. Move them first.']);
        }
        $stage->delete();

        return back()->with('success', 'Stage deleted.');
    }

    /**
     * Persist a new ordering (array of stage ids in order).
     */
    public function reorder(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.manage_stages'), 403);
        $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        $businessId = app(CurrentBusiness::class)->id();
        foreach ($request->order as $i => $id) {
            RecruitmentStage::where('business_id', $businessId)->where('id', $id)
                ->update(['sort_order' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }

    private function uniqueSlug(int $businessId, string $name): string
    {
        $base = Str::slug($name) ?: 'stage';
        $slug = $base;
        $i = 1;
        while (RecruitmentStage::where('business_id', $businessId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
