<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningDev extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'empid',
        'learning_dev',
        'inc_date1',
        'inc_date2',
        'num_hours',
        'types',
        'conducted',
        'attachment',
        'status',
        'remarks',
    ];

}
