<?php

namespace App\Models;

use App\Services\PsbScoring;
use Illuminate\Database\Eloquent\Model;

/** One candidate's line on the Comparative Assessment Form. */
class ComparativeAssessmentRow extends Model
{
    protected $fillable = [
        'comparative_assessment_id', 'application_id',
        'candidate_name', 'present_position', 'salary_grade', 'appointment_status',
        'civil_service_eligibility',
        'performance_rating', 'education_points', 'training_points',
        'experience_points', 'potential_points', 'psychosocial_points',
        'preliminary_total', 'further_assessment', 'further_assessment_label',
        'overall_points', 'rank', 'remarks', 'sort_order',
    ];

    protected $casts = [
        'performance_rating'  => 'float',
        'education_points'    => 'float',
        'training_points'     => 'float',
        'experience_points'   => 'float',
        'potential_points'    => 'float',
        'psychosocial_points' => 'float',
        'preliminary_total'   => 'float',
        'further_assessment'  => 'float',
        'overall_points'      => 'float',
    ];

    public function assessment()
    {
        return $this->belongsTo(ComparativeAssessment::class, 'comparative_assessment_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * Recompute the two derived columns from the six components.
     * Kept here so a row can never be saved with a total that does not follow
     * from its parts.
     */
    public function recalculate(): self
    {
        $psb = new PsbScoring();

        $this->preliminary_total = $psb->preliminaryTotal($this->only(
            array_keys(PsbScoring::ASSESSMENT_WEIGHTS)
        ));
        $this->overall_points = $psb->overallPoints($this->preliminary_total, $this->further_assessment);

        return $this;
    }

    /** "Position title / SG / status", the second column on the form. */
    public function getPresentPositionLineAttribute(): string
    {
        return collect([$this->present_position, $this->salary_grade, $this->appointment_status])
            ->filter()
            ->implode(' / ');
    }
}
