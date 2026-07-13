<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    protected $fillable = [
        'id',
        'transnum',
        'empid',
        'position',
        'salary',
        'leave_type',
        'leave_purpose',
        'leave_detail',
        'date_range',
        'days',
        'commutation',
        'total_vl',
        'total_sl',
        'less_vl',
        'less_sl',
        'recommend',
        'emp_esign',
        'supervisor',
        'oic',
        'sup_prefix',
        'sup_sign',
        'sup_sdate',
        'approver',
        'approver_prefix',
        'approver_role',
        'approver_sign',
        'approver_sdate',
        'hr',
        'hr_prefix',
        'hr_sign',
        'hr_sdate',
        'remarks_stat',
        'remarks_details',
        'remarks_details1',
        'remarks_details2',
        'department',
        'date_filing',
        'day_wpay',
        'earn',
        'less',
        'balance',
        'status',
        'gen_app',
        'as_of',
        'holiday'
    ];

    public function office()
    {
        return $this->belongsTo(Office::class, 'department', 'id');
    }
    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empid', 'emp_ID');
    }

    // public function leaveApplications()
    // {
    //     return $this->hasMany(LeaveApplication::class, 'empid', 'emp_ID');
    // }

}
