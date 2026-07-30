<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Personnel Selection Board — Comparative Assessment Form.
 *
 * The consolidation sheet for one vacancy. Potential and psychosocial
 * attributes come from the panel interview; the rest is scored on the sheet.
 * See App\Services\PsbScoring for the weights.
 */
class ComparativeAssessment extends Model
{
    protected $fillable = [
        'jid', 'interview_id',
        'position_to_be_filled', 'item_no', 'location',
        'date_posted', 'date_published', 'rate_per_month',
        'finalised_at', 'created_by',
    ];

    protected $casts = [
        'date_posted'    => 'date',
        'date_published' => 'date',
        'finalised_at'   => 'datetime',
    ];

    public function rows()
    {
        return $this->hasMany(ComparativeAssessmentRow::class)
            ->orderBy('rank')
            ->orderBy('sort_order');
    }

    public function job()
    {
        return $this->belongsTo(JobHiring::class, 'jid');
    }

    /**
     * The board that signs this sheet, in printing order.
     *
     * Copied from the standing board under Settings when the sheet is built,
     * and its own from then on — editing a name here changes this hiring only.
     */
    public function boardMembers()
    {
        return $this->hasMany(ComparativeAssessmentBoardMember::class, 'comparative_assessment_id')
            ->orderByRaw("CASE role WHEN 'Chairperson' THEN 0 WHEN 'Vice-Chairperson' THEN 1 ELSE 2 END")
            ->orderBy('sort_order');
    }

    /**
     * Take a copy of the standing board. Only ever called for a sheet that has
     * none yet, so a rebuild cannot overwrite names already adjusted here.
     */
    public function seedBoardFrom($members): void
    {
        if ($this->boardMembers()->exists()) {
            return;
        }

        foreach ($members as $i => $member) {
            $this->boardMembers()->create([
                'employee_id' => $member->employee_id,
                'name'        => $member->name,
                'credentials' => $member->credentials,
                'role'        => $member->role,
                'sort_order'  => $i,
            ]);
        }
    }

    public function isFinalised(): bool
    {
        return $this->finalised_at !== null;
    }
}
