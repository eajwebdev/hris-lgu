<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeEvaluate extends Model
{
    protected $fillable = [
        'ete_id',
        'application_id',
        'jid',
        'evaluator_id',
        'position',
        'evaluation_date',
        'present_position',
        'college_department',
        'education_met',
        'experience_met',
        'eligibility_met',
        'training_met',
        'minimum_requirement_score',
        'education_score',
        'education_ratings',
        'training_score',
        'training_ratings',
        'experience_score',
        'experience_year_ratings',
        'total_score',
        'remarks',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'education_met' => 'boolean',
        'experience_met' => 'boolean',
        'eligibility_met' => 'boolean',
        'training_met' => 'boolean',
        'education_ratings' => 'array',
        'training_ratings' => 'array',
        'experience_year_ratings' => 'array',
    ];

    public function eteEvaluation()
    {
        return $this->belongsTo(EteEvaluation::class, 'ete_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function job()
    {
        return $this->belongsTo(JobHiring::class, 'jid');
    }

    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }
}
