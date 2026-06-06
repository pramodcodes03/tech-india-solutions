<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateStageHistory;
use App\Models\RecruitmentStage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecruitmentService
{
    /**
     * Default pipeline used when a business has none configured yet.
     * type: open | hired | rejected.
     */
    public const DEFAULT_STAGES = [
        ['name' => 'Applied',   'type' => 'open',     'color' => '#6366f1', 'is_default' => true],
        ['name' => 'Screened',  'type' => 'open',     'color' => '#0ea5e9', 'is_default' => false],
        ['name' => 'Interview', 'type' => 'open',     'color' => '#f59e0b', 'is_default' => false],
        ['name' => 'Offer',     'type' => 'open',     'color' => '#8b5cf6', 'is_default' => false],
        ['name' => 'Hired',     'type' => 'hired',    'color' => '#22c55e', 'is_default' => false],
        ['name' => 'Rejected',  'type' => 'rejected', 'color' => '#ef4444', 'is_default' => false],
    ];

    /**
     * Ensure the active business has a pipeline; seed the default set if empty.
     */
    public function ensureStages(int $businessId): void
    {
        if (RecruitmentStage::where('business_id', $businessId)->exists()) {
            return;
        }

        foreach (self::DEFAULT_STAGES as $i => $stage) {
            RecruitmentStage::create([
                'business_id' => $businessId,
                'name' => $stage['name'],
                'slug' => Str::slug($stage['name']),
                'type' => $stage['type'],
                'color' => $stage['color'],
                'is_default' => $stage['is_default'],
                'sort_order' => $i + 1,
                'status' => true,
            ]);
        }
    }

    public function defaultStage(int $businessId): ?RecruitmentStage
    {
        return RecruitmentStage::where('business_id', $businessId)->active()->ordered()
            ->orderByDesc('is_default')->first();
    }

    /**
     * Generate the next candidate code (CAN-0001) for a business.
     */
    public function nextCode(int $businessId): string
    {
        $count = Candidate::withTrashed()->where('business_id', $businessId)->count();

        return 'CAN-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a candidate and seed the first history row.
     */
    public function create(array $data): Candidate
    {
        return DB::transaction(function () use ($data) {
            $businessId = $data['business_id'];
            $this->ensureStages($businessId);

            $data['candidate_code'] ??= $this->nextCode($businessId);
            $data['stage_id'] ??= $this->defaultStage($businessId)?->id;
            $data['applied_at'] ??= now()->toDateString();
            $data['stage_changed_at'] = now();
            $data['created_by'] = Auth::guard('admin')->id();

            $candidate = Candidate::create($data);

            CandidateStageHistory::create([
                'business_id' => $businessId,
                'candidate_id' => $candidate->id,
                'from_stage_id' => null,
                'to_stage_id' => $candidate->stage_id,
                'action' => 'created',
                'remarks' => 'Candidate added to pipeline',
                'moved_by' => Auth::guard('admin')->id(),
            ]);

            return $candidate;
        });
    }

    /**
     * Move a candidate to a new stage, recording history and syncing the
     * derived status (hired / rejected / active) from the stage type.
     */
    public function moveToStage(Candidate $candidate, RecruitmentStage $stage, ?string $remarks = null): Candidate
    {
        return DB::transaction(function () use ($candidate, $stage, $remarks) {
            $from = $candidate->stage_id;

            $candidate->stage_id = $stage->id;
            $candidate->stage_changed_at = now();

            $action = 'moved';
            if ($stage->type === 'hired') {
                $candidate->status = 'hired';
                $candidate->hired_at = now();
                $action = 'hired';
            } elseif ($stage->type === 'rejected') {
                $candidate->status = 'rejected';
                $candidate->rejected_at = now();
                if ($remarks) {
                    $candidate->rejection_reason = $remarks;
                }
                $action = 'rejected';
            } else {
                $candidate->status = 'active';
            }
            $candidate->save();

            CandidateStageHistory::create([
                'business_id' => $candidate->business_id,
                'candidate_id' => $candidate->id,
                'from_stage_id' => $from,
                'to_stage_id' => $stage->id,
                'action' => $action,
                'remarks' => $remarks,
                'moved_by' => Auth::guard('admin')->id(),
            ]);

            return $candidate;
        });
    }

    /**
     * Funnel counts keyed by stage id for a business (optionally filtered).
     *
     * @return array{stages: \Illuminate\Support\Collection, counts: array<int,int>}
     */
    public function funnel(int $businessId, array $filters = []): array
    {
        $stages = RecruitmentStage::where('business_id', $businessId)->ordered()->get();

        $query = Candidate::where('business_id', $businessId);
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (! empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('applied_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('applied_at', '<=', $filters['to']);
        }

        $counts = (clone $query)->select('stage_id', DB::raw('count(*) as c'))
            ->groupBy('stage_id')->pluck('c', 'stage_id')->toArray();

        return ['stages' => $stages, 'counts' => $counts];
    }
}
