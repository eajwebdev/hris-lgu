<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewApplicant extends Model
{
    protected $fillable = [
        'interview_id',
        'application_id',
        'is_cast',
        'casted_at',
        'uncasted_at',
    ];

    protected $casts = [
        'is_cast' => 'boolean',
        'casted_at' => 'datetime',
        'uncasted_at' => 'datetime',
    ];

    public function interview()
    {
        return $this->belongsTo(InterviewEvaluation::class, 'interview_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }
}
