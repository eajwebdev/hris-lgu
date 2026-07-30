<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobHiring extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_description_id',
        'type',
        'title',
        'plantilla_item_no',
        'salary',
        'assignment',
        'education',
        'eligibility',
        'training',
        'experience',
        'competency',
        'posted_at',
        'expiration_at',
        'status',
    ];

    /**
     * The standing DBM-CSC Form No. 1 for this plantilla item. One description
     * serves every posting of the item, so this is a reference, not ownership.
     */
    public function positionDescription()
    {
        return $this->belongsTo(PositionDescription::class, 'position_description_id');
    }

    public function comparativeAssessment()
    {
        return $this->hasOne(ComparativeAssessment::class, 'jid');
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'jid');
    }

    /**
     * Qualification standards shown on the posting. The Position Description is
     * the source of record when one is linked; the columns on this table are
     * the fallback for postings created before descriptions existed.
     */
    public function qualificationStandards(): array
    {
        $pd = $this->positionDescription;

        return [
            'education'   => $pd->qs_education   ?? $this->education,
            'experience'  => $pd->qs_experience  ?? $this->experience,
            'training'    => $pd->qs_training    ?? $this->training,
            'eligibility' => $pd->qs_eligibility ?? $this->eligibility,
            'competency'  => $this->competency,
        ];
    }
}
