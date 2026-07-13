<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'region_id',
        'province_id',
        'city_id',
    ];

    public $timestamps = false;
}
