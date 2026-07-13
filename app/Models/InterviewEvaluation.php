<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewEvaluation extends Model
{
    protected $fillable = [
        'ete_id',
        'jid',
        'interview_date',
        'active_application_id',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
    ];

    public function eteEvaluation()
    {
        return $this->belongsTo(EteEvaluation::class, 'ete_id');
    }

    public function job()
    {
        return $this->belongsTo(JobHiring::class, 'jid');
    }

    public function activeApplication()
    {
        return $this->belongsTo(Application::class, 'active_application_id');
    }

    public function panels()
    {
        return $this->hasMany(InterviewPanel::class, 'interview_id');
    }

    public function applicants()
    {
        return $this->hasMany(InterviewApplicant::class, 'interview_id');
    }

    public function ratings()
    {
        return $this->hasMany(InterviewRating::class, 'interview_id');
    }
}
