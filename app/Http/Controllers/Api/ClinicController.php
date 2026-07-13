<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class ClinicController extends Controller
{
    public function emplList(){
        $employees = Employee::select('id', 'emp_ID', 'fname', 'mname', 'lname', 'prefix', 'suffix', 'bdate', 'age', 'sex', 'civil_status', 'org_email')->get();

        return response()->json($employees);
    }
}
