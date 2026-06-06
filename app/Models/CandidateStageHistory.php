<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateStageHistory extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'candidate_id', 'from_stage_id', 'to_stage_id',
        'action', 'remarks', 'moved_by',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(RecruitmentStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(RecruitmentStage::class, 'to_stage_id');
    }

    public function mover(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'moved_by');
    }
}
