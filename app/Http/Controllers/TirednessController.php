<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
use App\Models\Fdevice;
use App\Models\OfficialTime;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Route;

class TirednessController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function readTiredness(Request $request)
    {
        $guard = $this->getGuard();
        $employeeall = Employee::all();
        $employee = null;
        $month = null;
        $employeeId = null;
    
        if ($request->isMethod('post')) {
            if ($request->has('employee') && $request->has('month')) {
                if($employee == 0){
                    $employee = Employee::where('emp_ID', $request->employee)->first();
                    $month = $request->month;
                    $employeeId = $employee ? $employee->emp_ID : 0;
                }
            }
        }
    
        return view('tiredeness.tiredeness', compact('guard', 'employee', 'employeeall', 'employeeId', 'month'));
    }
    
    public function pdfTirednes($employeeId, $month)
    {
        $year = explode('-', $month)[0];
        $guard = $this->getGuard();
    
        $monthNumber = date('m', strtotime($month));
    
        if($employeeId == 0){
            $dtrRecords = Dtr::whereMonth('date', $monthNumber)
            ->join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
            ->selectRaw("
                employees.lname,
                employees.prefix,
                employees.fname,
                employees.mname,
                
                -- Total minutes beyond 8 hours in the morning (if any)
                SUM(
                    CASE
                        WHEN LEFT(SUBSTRING_INDEX(time_in, ',', 1), 5) >= '08:00' 
                            AND LEFT(SUBSTRING_INDEX(time_in, ',', 1), 5) < '11:00'
                        THEN 
                            GREATEST(TIME_TO_SEC(LEFT(SUBSTRING_INDEX(time_in, ',', 1), 5)) / 60 - 480, 0) -- Deduct 8 hours (480 minutes)
                        ELSE 0
                    END
                ) as total_minutes,
                
                COUNT(
                    CASE
                        WHEN LEFT(SUBSTRING_INDEX(time_in, ',', 1), 5) >= '08:00' 
                            AND LEFT(SUBSTRING_INDEX(time_in, ',', 1), 5) < '11:00' 
                        THEN 1 
                        ELSE NULL 
                    END
                ) as morning_count,
                
                -- Total minutes beyond 13:00 for noon (if any), using the last occurrence of time_in
                SUM(
                    CASE
                        -- Get the last time_in and compare it with 13:00
                        WHEN LEFT(SUBSTRING_INDEX(time_in, ',', -1), 5) > '13:00'
                        THEN 
                            GREATEST(TIME_TO_SEC(LEFT(SUBSTRING_INDEX(time_in, ',', -1), 5)) / 60 - 780, 0) -- Deduct 13:00 (780 minutes)
                        ELSE 0
                    END
                ) as total_noon_minutes,
        
                COUNT(
                    CASE
                        -- Count occurrences where the last time_in is greater than or equal to 13:00
                        WHEN LEFT(SUBSTRING_INDEX(time_in, ',', -1), 5) >= '13:00'
                        THEN 1 
                        ELSE NULL 
                    END
                ) as noon_count,
                
                -- Calculate total undertime for times out before 12:00
                SUM(
                    CASE
                        WHEN LEFT(SUBSTRING_INDEX(time_out, ',', 1), 5) < '12:00'
                        THEN 
                            GREATEST(TIME_TO_SEC('12:00') / 60 - TIME_TO_SEC(LEFT(SUBSTRING_INDEX(time_out, ',', 1), 5)) / 60, 0)
                        ELSE 0
                    END
                ) as total_undertime_minutes,
                
                -- Count only days where time_out is less than 12:00
                COUNT(
                    CASE
                        WHEN LEFT(SUBSTRING_INDEX(time_out, ',', 1), 5) < '12:00' 
                        THEN 1 
                        ELSE NULL 
                    END
                ) as undertime_count,
                
                -- Calculate total afternoon undertime for times out before 17:00
                SUM(
                    CASE
                        WHEN LEFT(SUBSTRING_INDEX(time_out, ',', -1), 5) < '17:00' -- Use the last time_out
                        THEN 
                            GREATEST(TIME_TO_SEC('17:00') / 60 - TIME_TO_SEC(LEFT(SUBSTRING_INDEX(time_out, ',', -1), 5)) / 60, 0)
                        ELSE 0
                    END
                ) as total_afternoon_undertime_minutes,
                
                -- Count only days where the last time_out is less than 17:00
                COUNT(
                    CASE
                        WHEN LEFT(SUBSTRING_INDEX(time_out, ',', -1), 5) < '17:00' 
                        THEN 1 
                        ELSE NULL 
                    END
                ) as afternoon_undertime_count
            ")
            ->groupBy('employees.emp_ID', 'employees.lname', 'employees.prefix', 'employees.fname', 'employees.mname')
            ->orderBy('employees.lname', 'asc')
            ->get();
        
        
            foreach ($dtrRecords as $record) {
                $totalMinutes = (int)$record->total_minutes;
                $record->total_hours = floor($totalMinutes / 60);
                $record->remaining_minutes = $totalMinutes % 60;
            }
            $officialtimes = [];
            $form = 'tiredeness.tiredeness-pdf';
        }else{
            $dtrRecords = Dtr::where('emp_ID', $employeeId)
                ->whereMonth('date', $monthNumber)->get();

            $officialtimes = OfficialTime::where('empid', '=', $employeeId)->first();

            $form = 'tiredeness.tiredeness-pdf1';
        }
        
        $pdf = PDF::loadView($form, compact('dtrRecords', 'monthNumber', 'officialtimes', 'year'))->setPaper('Legal', 'portrait');
        
        return $pdf->stream();
    }
    
    
    
}
