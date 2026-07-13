<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
use App\Models\FamilyBg;
use App\Models\EducBg;
use App\Models\Qualification;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\VoluntaryWork;
use App\Models\LearningDev;
use App\Models\OtherInfo;
use App\Models\InfoQuestion;
use App\Models\PdsReference;
use App\Models\GovId;
use App\Models\Device;
use PDF;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class PdsController extends Controller
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

    public function signature($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }


        $imageData = asset('Uploads/esign-default.jpg'); // fallback
        if ($employee->esign) {
            try {
                $decrypted = Crypt::decrypt($employee->esign);
                $imageData = 'data:image/png;base64,' . base64_encode($decrypted);
            } catch (\Exception $e) {
                // fallback stays default
            }
        }

        return view("emp.signature", compact('employee', 'guard', 'empid', 'imageData'));
    }

    public function uploadSignature(Request $request, $id = null)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png',
        ]);

        $employee = Employee::findOrFail($id);

        if ($request->hasFile('signature')) {
            try {
                $file = $request->file('signature');
                $binaryContent = file_get_contents($file->getRealPath());

                $encryptedContent = Crypt::encrypt($binaryContent);
                $employee->esign = $encryptedContent;
                $employee->save();

                $imageUrl = 'data:image/png;base64,' . base64_encode($binaryContent);

                return response()->json([
                    'success' => true,
                    'image_url' => $imageUrl,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() // for debugging only
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No file selected or upload failed'
        ], 400);
    }

    public function empPDS(){
        $guard = $this->getGuard();
        $empid = auth()->guard($guard)->user()->id; 
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $devices = Device::all();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        $hprovinces = Province::where('region_id', $employee->add_region)->get();
        $hcities = City::where('city_id', $employee->add_city)->get();
        $hbarangays = Barangay::find($employee->add_brgy);

        $gprovinces = Province::where('region_id', $employee->padd_region)->get();
        $gcities = City::where('city_id', $employee->padd_city)->get();
        $gbarangays = Barangay::find($employee->padd_brgy);
        
        $regions = Region::all();
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
                 ->get();
        
        $supervisor = Employee::where('id', '!=', $empid)->where('emp_status', 1)->get();

        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();
        $quali = Qualification::all();
        
        return view("emp.pds", compact('employee', 'supervisor', 'guard', 'devices', 'offices', 'stat', 'quali', 'regions', 'hprovinces', 'hcities', 'hbarangays', 'gprovinces', 'gcities', 'gbarangays', 'empid', 'columnstatus'));
    }

    public function generatepds($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }

        $familyBg = FamilyBg::where('empid', $employee->emp_ID)->first();
        $educBg = EducBg::where('empid', $employee->emp_ID)->first();
        $eligibility = Eligibility::where('empid', $employee->emp_ID)->where('status', '!=', 0)->get();
        $workexperience = WorkExperience::where('empid', $employee->emp_ID)
        ->where('status', '!=', 0)->orderByDesc('inc_date1')->orderByDesc('inc_date2')->get();

        $voluntaryworks = VoluntaryWork::where('empid', $employee->emp_ID)->where('status', '!=', 0)
        ->where('status', '!=', 0)->orderByDesc('inc_date1')->orderByDesc('inc_date2')->get();
        
        $learningdev = LearningDev::where('empid', $employee->emp_ID)->where('status', '!=', 0)
        ->where('status', '!=', 0)->orderByDesc('inc_date1')->orderByDesc('inc_date2')->get();
            
        $otherinfo = OtherInfo::where('empid', $employee->emp_ID)->first();
        $infoquestion = InfoQuestion::where('empid', $employee->emp_ID)->first();
        $references = PdsReference::where('empid', $employee->emp_ID)->first();
        $govids= GovId::where('empid', $employee->emp_ID)->first();
 
        $barangay = Barangay::find($employee->add_brgy);
        $city = City::where('city_id', $employee->add_city)->first();
        $province = Province::where('province_id', $employee->add_prov)->first();

        $barangay1 = Barangay::find($employee->padd_brgy);
        $city1 = City::where('city_id', $employee->padd_city)->first();
        $province1 = Province::where('province_id', $employee->padd_prov)->first();

        $datas = [
            'employee' => $employee,
            'familyBg' => $familyBg,
            'educBg' => $educBg,
            'eligibility' => $eligibility,
            'workexperience' => $workexperience,
            'voluntaryworks' => $voluntaryworks,
            'learningdev' => $learningdev,
            'otherinfo' => $otherinfo,
            'infoquestion' => $infoquestion,
            'references' => $references,
            'govids' => $govids, 
            'barangay' => $barangay,
            'city' => $city,
            'province' => $province,
            'barangay1' => $barangay1,
            'city1' => $city1,
            'province1' => $province1,
        ];

       $customPaper = array(0, 0, 612, 990);
        $pdf = \PDF::loadView('emp.generate-pds', compact('datas'))->setPaper($customPaper, 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'enable_php' => true,
            'margin-top' => 0,
            'margin-right' => 0,
            'margin-bottom' => 0,
            'margin-left' => 0,
        ]);
        $pdf->setCallbacks([
            'before_render' => function ($domPdf) {
                $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
            },
        ]);

        $pdf->render();

        return $pdf->stream();
    }

    public function genpdsAtthachment($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $workexperience = WorkExperience::where('empid', $employee->emp_ID)->where('status', '!=', 0)->get();

        $customPaper = array(0, 0, 612, 970);
        $pdf = \PDF::loadView('emp.gen-pds-attachment', compact('workexperience'))->setPaper($customPaper, 'portrait');

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

        return $pdf->stream();;
    }
}
