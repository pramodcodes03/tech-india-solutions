<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentFeedback extends Model
{
    use BelongsToBusiness;

    protected $table = 'department_feedback';

    /**
     * The 10 evaluation parameters used by the feedback form.
     * Keys are stored as the JSON property names; labels are user-facing.
     */
    public const PARAMETERS = [
        'service_quality' => 'Service Quality',
        'timeliness' => 'Timeliness',
        'communication' => 'Communication',
        'technical_competence' => 'Technical Competence',
        'problem_solving' => 'Problem Solving Ability',
        'professionalism' => 'Professionalism',
        'collaboration' => 'Collaboration',
        'accessibility' => 'Accessibility',
        'process_efficiency' => 'Process Efficiency',
        'employee_satisfaction' => 'Employee Satisfaction',
    ];

    /**
     * Score scale used in the form/UI. 0 means "Not applicable".
     */
    public const SCORE_LABELS = [
        0 => 'N/A',
        1 => 'Very Poor',
        2 => 'Poor',
        3 => 'Average',
        4 => 'Good',
        5 => 'Excellent',
    ];

    protected $fillable = [
        'business_id',
        'employee_id', 'department_id',
        'rating',                // legacy 1-5 single score (still present for old rows)
        'parameter_ratings',     // new: { service_quality: 4, ... }
        'overall_rating',        // cached avg of non-zero parameter values
        'feedback', 'is_anonymous',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'parameter_ratings' => 'array',
            'overall_rating' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Compute the overall rating on the 0-5 scale.
     *
     * Formula: sum of all 10 parameter scores ÷ 10.
     *
     * Zeros (Not Applicable) are included in the sum (as 0) and the divisor
     * is fixed at 10. This means an N/A reduces the overall — by design,
     * because not having an experience with a parameter is itself information
     * about the department's accessibility / scope of service.
     *
     * Example: [4,5,3,4,4,5,3,4,4,5] → sum 41 → 41/10 = 4.10 → 82%
     */
    public static function computeOverall(array $params): ?float
    {
        if (empty($params)) {
            return null;
        }
        $sum = array_sum(array_map(fn ($v) => (int) $v, $params));

        return round($sum / 10, 2);
    }

    /**
     * Convert a 0-5 overall rating into a 0-100 percentage.
     * Used by listings/dashboards that want a quick "score %" display.
     */
    public static function ratingToPercentage(?float $rating): ?int
    {
        if ($rating === null) {
            return null;
        }

        return (int) round(($rating / 5) * 100);
    }

    public function overallPercentage(): ?int
    {
        return self::ratingToPercentage($this->overall_rating);
    }

    /**
     * True if this row was submitted with the 10-parameter matrix.
     */
    public function hasParameterRatings(): bool
    {
        return ! empty($this->parameter_ratings);
    }
}
