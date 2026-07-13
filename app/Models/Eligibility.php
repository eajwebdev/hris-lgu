<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eligibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'careereligible',
        'rating',
        'date_exam',
        'place_exam',
        'number',
        'date_valid',
        'attachment',
        'status',
        'remarks',
    ];

    public $timestamps = false;
}
