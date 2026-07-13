<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\Employee;
use App\Models\Status;
use App\Models\Office;
use App\Models\Qualification;
use App\Models\FamilyBg;
use App\Models\EducBg;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\VoluntaryWork;
use App\Models\LearningDev;
use App\Models\OtherInfo;
use App\Models\InfoQuestion;
use App\Models\PdsReference;
use App\Models\GovId;
use App\Models\Device;
use App\Models\OfficialTime;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }    
    
    public function columnStat($empid){
        $familyBg = FamilyBg::where('empid', $empid)->first();
        $educBg = EducBg::where('empid', $empid)->first();
        $eligibility = Eligibility::where('empid', $empid)->get();
        $workexperience = WorkExperience::where('empid', $empid)->get();
        $voluntaryworks = VoluntaryWork::where('empid', $empid)->get();
        $learningdev = LearningDev::where('empid', $empid)->get();
        $otherinfo = OtherInfo::where('empid', $empid)->first();
        $infoquestion = InfoQuestion::where('empid', $empid)->first();
        $references = PdsReference::where('empid', $empid)->first();
        $govids= GovId::where('empid', $empid)->first();
        
        $columnstatus = [
            'colfamstat' => $familyBg->famhasAnyValue(),
            'coleducstat' => $educBg->educhasAnyValue(),
            'eligibility' => $eligibility,
            'workexperience' => $workexperience,
            'voluntaryworks' => $voluntaryworks,
            'learningdev' => $learningdev,
            'colotherinfo' => $otherinfo->otherinfoAnyValue(),
            'colinfoquestion' => $infoquestion->infoquestionValue(),
            'colreferences' => $references->referencesValue(),
            'colgovids' => $govids->govidsValue(),
        ];

        return $columnstatus;
    }

    public function emp_list()
    {
        // Get guard and user details
        $guard = $this->getGuard();
        $user = User::where('username', auth()->user()->username)->first();
        
        // Get offices excluding the 'UNKNOWN' placeholder
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
                         ->get();
    
        // Get statuses excluding 'Part-time/JO'
        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();
    
        if (auth()->user()->role == "Payroll Extension") {
            // Further filter statuses if the user's role is 'Payroll Extension'
            $stat->whereNotIn('status_name', ['Regular', 'Part-time/JO'])->get();
        }
    
        // Create the employee query with Eloquent relationships
        $employee = Employee::leftjoin('offices', 'employees.emp_dept', '=', 'offices.id')
            ->leftjoin('statuses', 'employees.emp_status', '=', 'statuses.id')
            ->select(
                'employees.id',
                'employees.emp_ID',
                'employees.position',
                'employees.org_email',
                'employees.date_hired',
                'employees.lname',
                'employees.fname',
                'employees.mname',
                'employees.emp_dept',
                'employees.emp_status',
                'offices.office_name',
                'statuses.status_name',
                'employees.emp_status',
                'employees.stat_1'
            );
        
        // Retrieve employees
        $employee = $employee->get();
    
        // Manually add row numbers to employees collection
        $employee = $employee->map(function($item, $key) {
            $item->ids = $key + 1; // Row number starts from 1
            return $item;
        });
    
        // Get all qualifications
        $quali = Qualification::all();
    
        // Return the view with the data
        return view("emp.emplist", compact('employee', 'offices', 'stat', 'quali', 'guard'));
    }
    

    public function empAdd(){
        $regions = Region::all();
        $guard = $this->getGuard();
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
                 ->get();
        
        $supervisor = Employee::where("emp_status", 1)->get();

        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();
        $quali = Qualification::all();
        
        return view("emp.empadd", compact('guard', 'offices', 'stat', 'quali', 'regions', 'supervisor'));
    }

    public function empCreate(Request $request)
    {
        $validated = $request->validate([
            'lname' => 'required|string',
            'fname' => 'required|string',
        ]);

        $existingEmployee = Employee::where('lname', $request->lname)
                ->where('fname', $request->fname)
                ->first();

        if ($existingEmployee) {
            return redirect()->back()->withErrors(['Employee already exists.']);
        }


        if ($request->filled('ProfileImage')) {
            $base64Image = $request->input('ProfileImage');
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            $fileName = date('Ymdhis');
            $fileExtension = '.jpg';
            $fullFileName = $fileName . $fileExtension;
            $file = public_path('Profile/Employee/' . $fullFileName);
            file_put_contents($file, $imageData);
        } elseif ($request->hasFile('ProfileImage1')) {
            $profileImage1 = $request->file('ProfileImage1');
            $fileName = date('Ymdhis') . '.' . $profileImage1->getClientOriginalExtension();
            $profileImage1->move(public_path('Profile/Employee/'), $fileName);
            $fullFileName = $fileName;
        } 
        else {
            if ($request->sex == 'Male') {
                $fullFileName = 'default-male.png';
            } elseif ($request->sex == 'Female') {
                $fullFileName = 'default-female.png';
            } else {
                $fullFileName = 'default.png';
            }
        }        

        $lastEmployee = Employee::orderBy('emp_ID', 'desc')->first();

        if ($lastEmployee) {
            $lastEmpID = $lastEmployee->emp_ID;
            $lastNumericPart = (int)substr($lastEmpID, 3);
            $newNumericPart = $lastNumericPart + 1;
            $newEmpID = 'EMP' . str_pad($newNumericPart, 4, '0', STR_PAD_LEFT);
        } else {
            $newEmpID = 'EMP0001';
            
        }
        $password = substr($newEmpID, 0, 3).substr($newEmpID, 3);

        $employee = new Employee([
            'profile' => $fullFileName,
            'date_hired' => $request->date_hired,
            'item_plan' => $request->item_plan,
            'lname' => strtoupper($request->lname),
            'fname' => strtoupper($request->fname),
            'mname' => strtoupper($request->mname),
            'suffix' => $request->suffix,
            'title_suffix' => $request->title_suffix,
            'position' => $request->position,
            'emp_ID' => $newEmpID,
            'emp_status' => $request->emp_status,
            'emp_dept' => $request->emp_dept,
            'em_supervise' => $request->em_supervise,
            'item_no' => $request->item_no,
            'prefix' => $request->prefix,
            'bdate' => $request->bdate,
            'age' => $request->age,
            'b_place' => $request->b_place,
            'sex' => $request->sex,
            'civil_status' => $request->civil_status,
            'height_cm' => $request->height_cm,
            'height_ft' => $request->height_ft,
            'weight_kg' => $request->weight_kg,
            'weight_lb' => $request->weight_lb,
            'b_type' => $request->b_type,
            'gsis' => $request->gsis,
            'pagibig' => $request->pagibig,
            'philhealth' => $request->philhealth,
            'sss' => $request->sss,
            'tin' => $request->tin,
            'citizenship' => $request->citizenship,
            'c_category' => $request->c_category,
            'country' => $request->country,
            'telephone' => $request->telephone,
            'org_email' => $request->org_email,
            'mobile' => $request->mobile,
            'add_block' => $request->add_block,
            'add_street' => $request->add_street,
            'add_village' => $request->add_village,
            'add_brgy' => $request->add_brgy,
            'add_city' => $request->add_city,
            'add_prov' => $request->add_prov,
            'add_region' => $request->add_region,
            'add_zcode' => $request->add_zcode,
            'padd_block' => $request->padd_block,
            'padd_street' => $request->padd_street,
            'padd_village' => $request->padd_village,
            'padd_brgy' => $request->padd_brgy,
            'padd_city' => $request->padd_city,
            'padd_prov' => $request->padd_prov,
            'padd_region' => $request->padd_region,
            'padd_zcode' => $request->padd_zcode,
            // 'special_pl' => ($request->emp_status == 1) ? 3 : 0,
            // 'solo_pl' => ($request->emp_status == 1) ? 7 : 0,
            'username' => $newEmpID,
            'special_pl' => 0,
            'solo_pl' => 0,
            'password' => $password,
        ]);
        
        $employee->save();

        $models = ['FamilyBg', 'EducBg', 'OtherInfo', 'InfoQuestion', 'PdsReference', 'GovId', 'OfficialTime'];

        foreach ($models as $model) {
            $modelClass = "App\\Models\\{$model}";
            
            if (class_exists($modelClass)) {
                $modelClass::create([
                    'empid' => $newEmpID,
                ]);
            } else {
                throw new Exception("Model {$modelClass} not found.");
            }
        }

        return redirect()->back()->with('success', 'Employee added successfully.');
    }

    public function updateProfilePicture(Request $request, $id)
    {
        $request->validate([
            'profileImage' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        $employee = Employee::find($id);
    
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }
    
        if ($request->hasFile('profileImage')) {
            $profileImagePath = public_path('Profile/Employee/');

            if ($employee->profile != "default.png" && file_exists($profileImagePath . $employee->profile)) {
                unlink($profileImagePath . $employee->profile);
            }
    
            $profileImage1 = $request->file('profileImage');
            $fileName = date('Ymdhis') . '.' . $profileImage1->getClientOriginalExtension();
            $profileImage1->move($profileImagePath, $fileName);
    
            $employee->profile = $fileName;
            $employee->save();
        }
    
        return response()->json([
            'success' => 'Profile picture updated successfully.',
            'profile' => asset('Profile/Employee/' . $fileName),
        ]);
    }    

    public function employeeUpdate(Request $request)
    {
        $employee = Employee::findOrFail($request->id);
        $column = $request->column;
    
        if ($column == 'bdate') {
            $bdate = Carbon::parse($request->value);
            $age = $bdate->age;
            $employee->update([
                $column => $request->value,
                'age' => $age
            ]);
        } 
        elseif ($column == 'citizenship' && $request->value == 1) {
            $employee->update([
                $column => $request->value,
                'c_category' => '',
                'country' => '',
            ]);
        } 
        elseif ($column == 'org_email') {
            $employee->update([
                $column => $request->value,
                'username' => $request->value,
            ]);
        } 
        elseif ($column == 'height_cm' || $column == 'height_m') {
            if ($column == 'height_cm') {
                // Convert cm to meters
                $height_m = round($request->value / 100, 2); // 1 m = 100 cm
        
                $employee->update([
                    $column => $request->value,
                    'height_m' => $height_m
                ]);
            } elseif ($column == 'height_m') {
                // Convert meters to cm
                $height_cm = round($request->value * 100); // 1 m = 100 cm
        
                $employee->update([
                    $column => $request->value,
                    'height_cm' => $height_cm
                ]);
            }
        }
        elseif ($column == 'weight_kg' || $column == 'weight_lb') {
            if ($column == 'weight_kg') {
                // Convert kg to pounds
                $weight_lb = round($request->value * 2.20462, 2); // 1 kg = 2.20462 lbs
        
                $employee->update([
                    $column => $request->value,
                    'weight_lb' => $weight_lb
                ]);
            } elseif ($column == 'weight_lb') {
                // Convert lb to kg
                $weight_kg = round($request->value / 2.20462, 2); // 1 lb = 0.453592 kg
        
                $employee->update([
                    $column => $request->value,
                    'weight_kg' => $weight_kg
                ]);
            }
        }
        elseif ($column == 'emp_salary') {
            $value = filter_var($request->value, FILTER_VALIDATE_FLOAT);
            $employee->update(['emp_salary' => round($value, 2)]);
        }
        else {
            $columnsToCapitalize = ['lname', 'fname', 'mname'];
    
            $employee->update([
                $column => in_array($column, $columnsToCapitalize) ? strtoupper($request->value) : $request->value
            ]);
        }
    
        return response()->json(['success' => true]);
    }

    public function PDS($id){
        $guard = $this->getGuard();
        $empid = $id; 
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $columnstatus = $this->columnStat($employee->emp_ID);
        $devices = Device::all();

        $hprovinces = Province::where('region_id', $employee->add_region)->get();
        $hcities = City::where('city_id', $employee->add_city)->get();
        $hbarangays = Barangay::find($employee->add_brgy);

        $gprovinces = Province::where('region_id', $employee->padd_region)->get();
        $gcities = City::where('city_id', $employee->padd_city)->get();
        $gbarangays = Barangay::find($employee->padd_brgy);

        $supervisor = Employee::where('id', '!=', $empid)->where('emp_status', 1)->get();
        
        $regions = Region::all();
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
                 ->get();
        
        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();
        $quali = Qualification::all();

        return view("emp.pds", compact('employee', 'supervisor', 'guard', 'devices', 'offices', 'stat', 'quali', 'regions', 'hprovinces', 'hcities', 'hbarangays', 'gprovinces', 'gcities', 'gbarangays', 'empid', 'columnstatus'));
    }

    public function genEmp(){
        $customPaper = array(0, 0, 970, 612);
        $employees = Employee::leftjoin('offices', 'employees.emp_dept', '=', 'offices.id')
        ->join('statuses', 'employees.emp_status', '=', 'statuses.id')
        ->get();
        
        $pdf = \PDF::loadView('emp.gen-emp', compact('employees'))->setPaper($customPaper, 'portrait');

        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

        $pdf->setCallbacks([
            'before_render' => function ($domPdf) {
                $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
            },
        ]);

        $pdf->render();

        return $pdf->stream();
    }
    
    public function empEdit($id)
    {
        $emp = Employee::find($id);
        return response()->json([
            'status'=>200,
            'emp'=>$emp,
        ]);
    }

    public function empDelete($id){
        $emp = Employee::find($id);
        $emp->delete();

        return response()->json([
            'status'=>200,
            'message'=>"Deleted Successfully",
        ]);
    }

    public function empPartimeRate(Request $request){
        $validator = Validator::make($request->all(), [
            'PartimeRate'=>'',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'=>400,
                'error'=>$validator->messages(),
            ]);
        }

        else{
            $update = [
                'partime_rate'=>round($request->input('PartimeRate'), 2)
            ];
            DB::table('employees')->where('id', $request->empid)->update($update);

            return response()->json([
                'status'=>200,
                'message'=>"Successfully Update",
            ]);
        }
    }
    
    public function empEditRate($id){
        $emp = Employee::find($id);
        return response()->json([
            'status'=>200,
            'emp'=>$emp,
        ]);
    }

    public function toggleAcctStat(Request $request)
    {
        $employee = Employee::findOrFail($request->id);
        $employee->stat_1 = $request->stat_1;
        $employee->save();
        
        return response()->json(['success' => true, 'message' => 'User role updated successfully.']);
    }    

    public function OfficialTimeRead(Request $request, $empid)
    {
        $offtimes = OfficialTime::where('empid', '=', $empid)->first();
        $monmorn = explode('-', $offtimes->morn_mon);
        $monnoon = explode('-', $offtimes->aft_mon);

        $tuemorn = explode('-', $offtimes->morn_tue);
        $tuenoon = explode('-', $offtimes->aft_tue);

        $wedmorn = explode('-', $offtimes->morn_wed);
        $wednoon = explode('-', $offtimes->aft_wed);

        $thumorn = explode('-', $offtimes->morn_thu);
        $thunoon = explode('-', $offtimes->aft_thu);

        $frimorn = explode('-', $offtimes->morn_fri);
        $frinoon = explode('-', $offtimes->aft_fri);

        $data = [
            'mon_mornin' => $monmorn[0],
            'mon_mornout' => $monmorn[1],
            'mon_noonin' => $monnoon[0],
            'mon_noonout' => $monnoon[1],

            'tue_mornin' => $tuemorn[0],
            'tue_mornout' => $tuemorn[1],
            'tue_noonin' => $tuenoon[0],
            'tue_noonout' => $tuenoon[1],

            'wed_mornin' => $wedmorn[0],
            'wed_mornout' => $wedmorn[1],
            'wed_noonin' => $wednoon[0],
            'wed_noonout' => $wednoon[1],

            'thu_mornin' => $thumorn[0],
            'thu_mornout' => $thumorn[1],
            'thu_noonin' => $thunoon[0],
            'thu_noonout' => $thunoon[1],

            'fri_mornin' => $frimorn[0],
            'fri_mornout' => $frimorn[1],
            'fri_noonin' => $frinoon[0],
            'fri_noonout' => $frinoon[1],
        ];
    
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);

    }    

    public function OfficialTimeCreate(Request $request)
    {
        $validatedData = $request->validate([
            'empid' => 'required',
            'mon_mornin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'mon_mornout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'mon_noonin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'mon_noonout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
    
            'tue_mornin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'tue_mornout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'tue_noonin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'tue_noonout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
    
            'wed_mornin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'wed_mornout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'wed_noonin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'wed_noonout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
    
            'thu_mornin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'thu_mornout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'thu_noonin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'thu_noonout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
    
            'fri_mornin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'fri_mornout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'fri_noonin' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
            'fri_noonout' => 'required                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
        ]);
        
        $officialTime = OfficialTime::firstOrNew(['empid' => $request->empid]);

        $convertTo24HourFormat = function ($time) {
            return (new \DateTime($time))->format('H:i:s');
        };
    
        $officialTime->morn_mon = $convertTo24HourFormat($request->mon_mornin) . '-' . $convertTo24HourFormat($request->mon_mornout);
        $officialTime->aft_mon = $convertTo24HourFormat($request->mon_noonin) . '-' . $convertTo24HourFormat($request->mon_noonout);
    
        $officialTime->morn_tue = $convertTo24HourFormat($request->tue_mornin) . '-' . $convertTo24HourFormat($request->tue_mornout);
        $officialTime->aft_tue = $convertTo24HourFormat($request->tue_noonin) . '-' . $convertTo24HourFormat($request->tue_noonout);
    
        $officialTime->morn_wed = $convertTo24HourFormat($request->wed_mornin) . '-' . $convertTo24HourFormat($request->wed_mornout);
        $officialTime->aft_wed = $convertTo24HourFormat($request->wed_noonin) . '-' . $convertTo24HourFormat($request->wed_noonout);
    
        $officialTime->morn_thu = $convertTo24HourFormat($request->thu_mornin) . '-' . $convertTo24HourFormat($request->thu_mornout);
        $officialTime->aft_thu = $convertTo24HourFormat($request->thu_noonin) . '-' . $convertTo24HourFormat($request->thu_noonout);
    
        $officialTime->morn_fri = $convertTo24HourFormat($request->fri_mornin) . '-' . $convertTo24HourFormat($request->fri_mornout);
        $officialTime->aft_fri = $convertTo24HourFormat($request->fri_noonin) . '-' . $convertTo24HourFormat($request->fri_noonout);
    
        $officialTime->save();
    
        return redirect()->back()->with('success', 'Official time saved successfully.');
    }    
    
    public function empQr(){
        $employees = Employee::select('emp_ID', 'fname', 'lname', 'emp_dept')
            ->orderBy('emp_dept')
            ->orderBy('lname')
            ->get();

        return view('emp.qr-code', compact('employees'));
    }
    
    // public function updateEmployeePasswords()
    // {
    //     try {
    //         $employees = Employee::all();
    
    //         foreach ($employees as $employee) {
    //             if (!empty($employee->username)) {
    //                 $username = $employee->username;
    
    //                 $hashedPassword = Hash::make($username);
    
    //                 $employee->password = $hashedPassword;
    //                 $employee->save();
    //             }
    //         }
    
    //         return 'Passwords updated successfully.';
    
    //     } catch (\Exception $e) {
    //         return 'An error occurred: ' . $e->getMessage();
    //     }
    // }
    

}
