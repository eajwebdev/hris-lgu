<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoluntaryWork extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'org_name',
        'inc_date1',
        'inc_date2',
        'num_hours',
        'position',
        'attachment',
        'status',
        'remarks',
    ];
}
