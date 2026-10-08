<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Office;
use App\Models\Employee;

class OfficeController extends Controller
{
    public function getGuaard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }
    
    /** Employees assigned to each office, keyed by office id. */
    private function headcounts()
    {
        return Employee::whereNotNull('emp_dept')
            ->selectRaw('emp_dept, COUNT(*) as total')
            ->groupBy('emp_dept')
            ->pluck('total', 'emp_dept');
    }

    public function officeList() {
        $guard = $this->getGuaard();
        $office = Office::leftJoin('employees', 'offices.office_head_id', '=', 'employees.id')
                ->leftJoin('employees as oic', 'offices.oic_id', '=', 'oic.id')
                ->get(['offices.*', 'employees.fname as efname', 'employees.lname as elname' , 'oic.fname as ofname', 'oic.lname as olname']);      
        
        $employee = Employee::all()->where('emp_status', 1);
        $headcounts = $this->headcounts();

        return view("offdept.officelist", compact('office', 'employee', 'headcounts', 'guard'));
    }

    public function officeCreate(Request $request){

        $validator = Validator::make($request->all(), [
            'OfficeName'=>'required',
            'OfficeAbbreviation'=>'required',
            'office_head_id'=>'nullable',
            'oic_id'=>'nullable',
            'GroupBy'=>'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        else{
            $select = Office::where('office_name', $request->OfficeName)->exists();
            if ($select) {
                return redirect()->back()->with('error', 'Office Already Exist!');
            }
            else
            {
                $query = Office::insert([
                    'office_name'=>$request->input('OfficeName'),
                    'office_abbr'=>$request->input('OfficeAbbreviation'),
                    'office_head_id'=>$request->input('office_head_id'),
                    'oic_id'=>$request->input('oic_id'),
                    'group_by'=> '0',
                ]);
                
                return redirect()->back()->with('success', 'Office Added Successfully'); 
            }
        }
    }

    public function officeEdit($id)
    {
        $guard = $this->getGuaard();
        $employee = Employee::all()->where('emp_status', 1);
        $office = Office::leftJoin('employees', 'offices.office_head_id', '=', 'employees.id')
                ->leftJoin('employees as oic', 'offices.oic_id', '=', 'oic.id')
                ->get(['offices.*', 'employees.fname as efname', 'employees.lname as elname', 'oic.fname as ofname', 'oic.lname as olname']);         
 
        $offEdit = Office::find($id);
        $headcounts = $this->headcounts();

        return view("offdept.officelist", compact('offEdit', 'office', 'employee', 'headcounts', 'guard'));
    }
    
    public function officeUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'OfficeName'=>'required',
            'OfficeAbbreviation'=>'required',
            'office_head_id'=> 'required',
            'oic_id' => 'nullable',
            'GroupBy' => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        else{
            $select = Office::where('office_name', $request->OfficeName)->where('id', '!=', $request->oid)->exists();
            if ($select) {
                return redirect()->back()->with('error', 'Office Already Exist!');
            }
            else
            {
                $update = [
                    'office_name'=>$request->input('OfficeName'),
                    'office_abbr'=>$request->input('OfficeAbbreviation'),
                    'office_head_id'=>$request->input('office_head_id'),
                    'oic_id'=>$request->input('oic_id'),
                    'group_by'=>'0',
                ];
                DB::table('offices')->where('id', $request->oid)->update($update);

                return redirect()->back()->with('success', 'Office Updated Successfully');
            }
        }
    }

    public function officeDelete($id){
        $office = Office::find($id);
        $office->delete();

        return response()->json([
            'status'=>200,
            'message'=>"Deleted Successfully",
        ]);
    }
}
