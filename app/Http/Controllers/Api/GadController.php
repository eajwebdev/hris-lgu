<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class GadController extends Controller
{
    public function genderCount() {
        $overall = Employee::query()
            ->where('stat_1', 1)
            ->select('sex')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('sex')
            ->get();

        // An LGU has no campuses, so the breakdown is by office instead.
        $byOffice = Employee::query()
            ->where('employees.stat_1', 1)
            ->join('offices', 'employees.emp_dept', '=', 'offices.id')
            ->select('offices.office_abbr', 'sex')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('offices.office_abbr', 'sex')
            ->get();

        return response()->json([
            'overall'  => $overall,
            'byoffice' => $byOffice,
        ]);
    }
}
