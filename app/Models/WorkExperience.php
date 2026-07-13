<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkExperience extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'inc_date1',
        'inc_date2',
        'position',
        'department',
        'salary',
        'sg_grade',
        'attachment',
        'stat_app',
        'status',
        'service',
        'supervisor',
        'list_accom',
        'actual_summary',
        'remarks',
    ];
}
