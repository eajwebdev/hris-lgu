<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialTime extends Model
{
    use HasFactory;
    protected $fillable = [
        'empid', 
        'morn_mon', 
        'aft_mon',
        'morn_tue',
        'aft_tue',
        'morn_wed',
        'aft_wed',
        'morn_thu',
        'aft_thu',
        'morn_fri',
        'aft_fri',
    ];

}
