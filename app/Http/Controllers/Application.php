<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Application extends Controller
{
    use HasFactory;
    
    protected $fillable = [
        'first_name', 'middle_name', 'last_name',
        'age', 'sex', 'mobile', 'email', 'address',
        'education', 'eligibility',
        'pds', 'wes', 'intent', 'resume', 'tor', 'coe', 'cert_training',
    ];
}
