<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecruitmentBatchController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.view'), 403);
        $batches = RecruitmentBatch::withCount('candidates')->orderByDesc('drive_date')->paginate(20);

        return view('admin.hr.recruitment.batches.index', compact('batches'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.create'), 403);
        $data = $this->validateBatch($request);
        $data['status'] = $request->boolean('status', true);
        RecruitmentBatch::create($data);

        return back()->with('success', 'Batch created.');
    }

    public function update(Request $request, RecruitmentBatch $batch)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.edit'), 403);
        $data = $this->validateBatch($request);
        $data['status'] = $request->boolean('status', true);
        $batch->update($data);

        return back()->with('success', 'Batch updated.');
    }

    public function destroy(RecruitmentBatch $batch)
    {
        abort_unless(Auth::guard('admin')->user()->can('recruitment.delete'), 403);
        if ($batch->candidates()->exists()) {
            return back()->withErrors(['batch' => 'Cannot delete a batch with candidates attached.']);
        }
        $batch->delete();

        return back()->with('success', 'Batch deleted.');
    }

    private function validateBatch(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'institution' => ['nullable', 'string', 'max:160'],
            'drive_date' => ['nullable', 'date'],
            'coordinator' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
