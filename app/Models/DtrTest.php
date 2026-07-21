<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrTest extends Model
{
    use HasFactory;
    protected $table = 'dtrs_test';
    public $timestamps = false;

    protected $fillable = ['device_id', 'device_id_in', 'device_id_out', 'device_id_over', 'emp_ID', 'time_in', 'time_out', 'time_over', 'date'];
}