<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logzone extends Model
{
    use HasFactory;

    protected $fillable = [
        'points',
        'camp_id',
        'label'
    ];
}
