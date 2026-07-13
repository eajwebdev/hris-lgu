<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'jid',
        'app_number',
        'position',	
        'first_name',	
        'middle_name',	
        'last_name',	
        'age',	
        'sex',	
        'mobile',	
        'email',
        'address',	
        'education',	
        'eligibility',	
        'pds',	
        'wes',	
        'intent',	
        'resume',	
        'tor',	
        'coe',	
        'cert_training',
        'dq_reason',
        'ctrl_no',
        'interview_datetime',
        'venue',
        'status',
        'checked',
        'is_complete',
        'created_at'
    ];
}
