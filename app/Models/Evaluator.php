<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluator extends Model
{
    protected $fillable = [
        'ete_id',
        'emp_id',
    ];

    public function eteEvaluation()
    {
        return $this->belongsTo(EteEvaluation::class, 'ete_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}