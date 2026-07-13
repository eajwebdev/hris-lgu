<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'days',
        'earn_sl',
        'earn_vl',
        'date',
        'remarks',
        'add_by',
        'stat',
    ];

}
