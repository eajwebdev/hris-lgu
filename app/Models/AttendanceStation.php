<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceStation extends Model
{
    use HasFactory;

    protected $table = 'attendance_stations';

    protected $fillable = ['name', 'lat', 'lng', 'radius_m', 'active'];

    protected $casts = [
        'lat'      => 'float',
        'lng'      => 'float',
        'radius_m' => 'integer',
        'active'   => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
