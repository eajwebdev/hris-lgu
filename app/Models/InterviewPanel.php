<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewPanel extends Model
{
    protected $fillable = [
        'interview_id',
        'emp_id',
        'is_chairman',
    ];

    protected $casts = [
        'is_chairman' => 'boolean',
    ];

    public function interview()
    {
        return $this->belongsTo(InterviewEvaluation::class, 'interview_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
