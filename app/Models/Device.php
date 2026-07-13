<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $table = 'f_devices';
    
    protected $fillable = ['device_id', 'camp_id', 'area_id', 'label'];
}
