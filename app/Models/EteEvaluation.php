<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EteEvaluation extends Model
{
    protected $fillable = [
        'jid',
        'off_id',
        'experience_years',
        'evaluation_date',
        'active_application_id',
    ];

    protected $casts = [
        'evaluation_date' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(JobHiring::class, 'jid');
    }

    public function office()
    {
        return $this->belongsTo(Office::class, 'off_id');
    }

    public function activeApplication()
    {
        return $this->belongsTo(Application::class, 'active_application_id');
    }

    public function evaluators()
    {
        return $this->hasMany(Evaluator::class, 'ete_id');
    }

    public function employeeEvaluates()
    {
        return $this->hasMany(EmployeeEvaluate::class, 'ete_id');
    }

    public function applicantRatings()
    {
        return $this->hasMany(EteApplicantRating::class, 'ete_id');
    }
}
