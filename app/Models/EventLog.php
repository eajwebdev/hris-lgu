<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{    
    use HasFactory;

    protected $table = 'event_logs';


    protected $fillable = [
        'empid',
        'event_id',
        'in',
        'out',
    ];
}
