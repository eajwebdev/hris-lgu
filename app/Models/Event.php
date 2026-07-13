<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
 
    protected $primaryKey = 'id';
 
    protected $fillable = [
        'title',
        'venue',
        'start',
        'end',
        'emp_status',
        'bg_color',
        'org_dept',
        'remember_token',
        'event_stat',
    ];
}
