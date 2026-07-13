<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewRating extends Model
{
    protected $fillable = [
        'interview_id',
        'application_id',
        'panel_employee_id',
        'interview_scores',
        'potential_scores',
        'interview_total',
        'potential_total',
        'total_score',
        'remarks',
        'submitted_at',
    ];

    protected $casts = [
        'interview_scores' => 'array',
        'potential_scores' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function interview()
    {
        return $this->belongsTo(InterviewEvaluation::class, 'interview_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function panelEmployee()
    {
        return $this->belongsTo(Employee::class, 'panel_employee_id');
    }
}
