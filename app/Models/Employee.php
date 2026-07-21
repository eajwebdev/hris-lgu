<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'employees';
    
    protected $fillable = [
        'fname', 'mname', 'lname', 'position', 'profile', 'area_id', 'emp_ID', 'android_id', 'emp_status', 'emp_salary', 'emp_dept', 'item_no',
        'username', 'verification_code', 'password', 'role', 'date_hired', 'prefix', 'title_prefix', 'suffix', 'bdate', 'age',
        'b_place', 'sex', 'civil_status', 'height_cm', 'height_m', 'weight_kg', 'weight_lb', 'b_type',
        'gsis', 'pagibig', 'philhealth', 'sss', 'tin', 'citizenship', 'c_category', 'country', 'telephone', 'mobile',
        'org_email', 'add_block', 'add_street', 'add_village', 'add_brgy', 'add_city', 'supervisor',
        'add_region', 'add_prov', 'add_zcode', 'padd_block', 'padd_street', 'padd_village', 'padd_brgy',
        'padd_city', 'padd_region', 'padd_prov', 'padd_zcode', 'sl', 'vl', 'mat_leave', 'special_pl', 'solo_pl', 
        'study_leave','vawc_leave','rehab_leave','benefits_leave','calamity_leave','adopt_leave','servcred_leave', 'well_leave',
        'esign', 'dpn', 'stat_1', 'esign', 'strat_function', 'f1', 'f2', 'f3',
        'face_embeddings'
    ];

    // Biometric data is never serialised out with the model. Anything that needs
    // to report on it goes through FaceEmbeddingService, which returns metadata
    // rather than vectors.
    protected $hidden = [
        'password',
        'face_embeddings',
    ];

    protected $casts = [
        'role' => 'string',
        'face_embeddings' => 'array',
    ];

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'empid', 'emp_ID');
    }

    public function faceVector()
    {
        return $this->hasOne(EmployeeFaceVector::class, 'employee_id');
    }

    public function faceAuditLogs()
    {
        return $this->hasMany(FaceAuditLog::class, 'employee_id');
    }

    /**
     * Check if employee is an Office Head or OIC.
     */
    public function isOfficeHead(): bool
    {
        return Office::where('office_head_id', $this->id)
            ->orWhere('oic_id', $this->id)
            ->exists();
    }

    /**
     * Registration metadata for the profile panel — never the vectors themselves.
     */
    public function faceSummary(): array
    {
        return app(\App\Services\FaceEmbeddingService::class)->summary($this);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            $employee->password = Hash::make($employee->password);
        });
    }

    public function getAuthIdentifierName()
    {
        return 'username';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }
}
