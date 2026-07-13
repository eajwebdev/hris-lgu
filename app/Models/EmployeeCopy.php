<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCopy extends Model
{
    use HasFactory;
    
    protected $table = 'employees';

    protected $fillable = [
        'fname', 'mname', 'lname', 'position', 'profile', 'emp_ID', 'emp_status', 'emp_dept', 'item_no',
        'username', 'verification_code', 'password', 'role', 'date_hired', 'prefix', 'title_prefix', 'suffix', 'bdate', 'age',
        'b_place', 'sex', 'civil_status', 'height_cm', 'height_ft', 'weight_kg', 'weight_lb', 'b_type',
        'gsis', 'pagibig', 'philhealth', 'sss', 'tin', 'citizenship', 'c_category', 'country', 'telephone', 'mobile',
        'org_email', 'add_block', 'add_street', 'add_village', 'add_brgy', 'add_city', 'supervisor',
        'add_region', 'add_prov', 'add_zcode', 'padd_block', 'padd_street', 'padd_village', 'padd_brgy',
        'padd_city', 'padd_region', 'padd_prov', 'padd_zcode', 'sl', 'vl', 'f1', 'f2', 'f3'
    ];
}
