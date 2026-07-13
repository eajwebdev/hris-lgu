<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class CoasController extends Controller
{
    public function empSig(Request $request)
    {
        $employees = Employee::select('id', 'emp_ID', 'fname', 'lname', 'esign')->get();

        // Add fullname field to each employee
        $employees = $employees->map(function ($emp) {
            $emp->fullname = $emp->fname . ' ' . $emp->lname;
            return $emp;
        });

        return response()->json($employees);
    }
}
