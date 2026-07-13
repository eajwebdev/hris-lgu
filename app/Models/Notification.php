<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'lapp_id',
        'esign_id',
        'category',
        'utype',
        'module',
        'status',
    ];

}
