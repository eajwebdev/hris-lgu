<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A signatory on one Comparative Assessment Form.
 *
 * Copied from the standing board when the sheet is built, then owned by that
 * sheet: the name is free text from that point on, so a substitute member or a
 * correction applies to this hiring only.
 */
class ComparativeAssessmentBoardMember extends Model
{
    protected $table = 'comparative_assessment_board_members';

    protected $fillable = [
        'comparative_assessment_id', 'employee_id',
        'name', 'credentials', 'role', 'sort_order',
    ];

    public function assessment()
    {
        return $this->belongsTo(ComparativeAssessment::class, 'comparative_assessment_id');
    }

    /** "ERNIE T. UY, RN, JD" */
    public function getPrintedNameAttribute(): string
    {
        $name = strtoupper(trim((string) $this->name));

        return $this->credentials ? $name.', '.$this->credentials : $name;
    }
}
