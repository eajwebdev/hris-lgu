<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * DBM-CSC Form No. 1 (Revised 2017) — Position Description Form.
 *
 * A standing description of a plantilla item, not of a vacancy: the same
 * record is reused by every posting of that item (job_hirings.position_description_id).
 */
class PositionDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_title', 'parenthetical_title', 'item_number', 'salary_grade',
        'lgu_unit_and_class', 'department_agency', 'bureau_office',
        'division_branch', 'workstation',
        'present_approp_act', 'previous_approp_act', 'salary_authorized', 'other_compensation',
        'immediate_supervisor_title', 'next_higher_supervisor_title',
        'equipment_used', 'contacts', 'working_conditions',
        'unit_general_function', 'position_general_function',
        'qs_education', 'qs_experience', 'qs_training', 'qs_eligibility',
        'core_competencies', 'leadership_competencies',
        'status', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'contacts'                => 'array',
        'working_conditions'      => 'array',
        'core_competencies'       => 'array',
        'leadership_competencies' => 'array',
    ];

    public function duties()
    {
        return $this->hasMany(PositionDescriptionDuty::class)->orderBy('sort_order');
    }

    public function supervised()
    {
        return $this->hasMany(PositionDescriptionSupervised::class)->orderBy('sort_order');
    }

    public function postings()
    {
        return $this->hasMany(JobHiring::class, 'position_description_id');
    }

    /**
     * The current advertisement of this item, if any.
     *
     * A position can be advertised more than once over the years; the newest
     * round is the one the positions list and the editor's publication card
     * show. Earlier rounds keep their own applicants and assessment.
     */
    public function latestPosting()
    {
        return $this->hasOne(JobHiring::class, 'position_description_id')->latestOfMany();
    }

    /** Section 22 must account for the whole working week. */
    public function dutiesPercentageTotal(): float
    {
        return round((float) $this->duties->sum('percentage'), 2);
    }

    public function getFullTitleAttribute(): string
    {
        return $this->parenthetical_title
            ? "{$this->position_title} ({$this->parenthetical_title})"
            : (string) $this->position_title;
    }
}
