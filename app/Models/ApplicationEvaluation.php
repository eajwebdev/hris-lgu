<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'employee_id',
        'evaluation_date',

        'present_position',
        'college_department',

        'education_met',
        'experience_met',
        'eligibility_met',
        'training_met',

        'education_score',
        'training_score',
        'experience_score',
        'experience_credits',

        'minimum_requirement_score',
        'total_rating',

        'remarks',
    ];

    protected $casts = [
        'evaluation_date' => 'date',

        'education_met' => 'boolean',
        'experience_met' => 'boolean',
        'eligibility_met' => 'boolean',
        'training_met' => 'boolean',

        'education_score' => 'decimal:2',
        'training_score' => 'decimal:2',
        'experience_score' => 'decimal:2',
        'minimum_requirement_score' => 'decimal:2',
        'total_rating' => 'decimal:2',

        'experience_credits' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}