<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendancePunchLog extends Model
{
    use HasFactory;

    protected $table = 'attendance_punch_logs';

    protected $fillable = [
        'employee_id', 'emp_ID', 'action', 'mode',
        'lat', 'lng', 'accuracy_m',
        'station_id', 'station_name', 'distance_m', 'out_of_range',
        'ip_address',
    ];

    protected $casts = [
        'lat'          => 'float',
        'lng'          => 'float',
        'accuracy_m'   => 'integer',
        'distance_m'   => 'integer',
        'out_of_range' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
