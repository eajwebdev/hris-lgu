<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dtr extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['device_id', 'device_id_in', 'device_id_out', 'device_id_over', 'emp_ID', 'time_in', 'time_out', 'time_over', 'date'];
}
