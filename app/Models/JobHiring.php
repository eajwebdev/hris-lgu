<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobHiring extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'plantilla_item_no',
        'salary',
        'assignment',
        'education',
        'eligibility',
        'training',
        'experience',
        'competency',
        'posted_at',
        'expiration_at',
        'status',
    ];

}
