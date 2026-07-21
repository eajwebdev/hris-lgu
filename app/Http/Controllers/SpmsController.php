<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Office;
use App\Models\Employee;
use App\Models\SpmsOpcr;
use App\Models\SpmsOpcrItem;
use App\Models\SpmsIpcr;
use App\Models\SpmsIpcrItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SpmsController extends Controller
{
    private function getGuard()
    {
        if (Auth::guard('web')->check()) {
            return 'web';
        } elseif (Auth::guard('employee')->check()) {
            return 'employee';
        }
        return null;
    }

    private function isOfficeHead($guard, $user): bool
    {
        if ($guard === 'web') {
            return true; // Administrators / HR staff have administrative access
        }

        if ($user && method_exists($user, 'isOfficeHead')) {
            return $user->isOfficeHead();
        }

        return false;
    }

    /**
     * SPMS Drive Landing Page (Folder Cards matching Screenshot 1)
     */
    public function drive(Request $request)
    {
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();
        $isHead = $this->isOfficeHead($guard, $user);

        return view('spms.drive', compact('guard', 'user', 'isHead'));
    }

    /**
     * OPCR List View (Matching Screenshot 2)
     */
    public function opcrList(Request $request)
    {
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();
        $isHead = $this->isOfficeHead($guard, $user);

        if (!$isHead) {
            return redirect()->route('spms.ipcr')
                ->with('error', 'Access Restricted: OPCR is strictly reserved for Office Heads and HR Administrators.');
        }

        $year = $request->input('year', date('Y'));
        $semester = $request->input('semester', (date('n') <= 6 ? 1 : 2));

        if ($guard === 'web') {
            $managedOffices = Office::where('id', '>', 2)->get();
            $selectedOfficeId = $request->input('office_id', $managedOffices->first()?->id);
        } else {
            $managedOffices = Office::where('office_head_id', $user->id)
                ->orWhere('oic_id', $user->id)
                ->get();

            if ($managedOffices->isEmpty()) {
                $selectedOfficeId = $user->emp_dept;
                $managedOffices = Office::where('id', $selectedOfficeId)->get();
            } else {
                $selectedOfficeId = $request->input('office_id', $managedOffices->first()?->id);
            }
        }

        $activeOffice = Office::find($selectedOfficeId);

        // Get OPCR documents list
        $opcrs = SpmsOpcr::with(['office', 'head', 'items'])
            ->where('office_id', $selectedOfficeId)
            ->orderBy('year', 'desc')
            ->orderBy('semester', 'desc')
            ->get();

        return view('spms.opcr_list', compact(
            'guard', 'user', 'isHead', 'managedOffices', 'activeOffice', 'opcrs', 'year', 'semester'
        ));
    }

    /**
     * Create or Get OPCR Document for Office
     */
    public function createOpcr(Request $request)
    {
        $request->validate([
            'office_id' => 'required|exists:offices,id',
            'year' => 'required|integer',
            'semester' => 'required|in:1,2',
        ]);

        $office = Office::findOrFail($request->office_id);

        $opcr = SpmsOpcr::firstOrCreate(
            [
                'office_id' => $office->id,
                'year' => $request->year,
                'semester' => $request->semester,
            ],
            [
                'office_head_id' => $office->office_head_id ?? auth()->guard($this->getGuard())->id(),
                'status' => 'Draft',
            ]
        );

        return redirect()->route('spms.opcr.matrix', $opcr->id)
            ->with('success', 'OPCR Document created/loaded successfully.');
    }

    /**
     * Detailed OPCR Matrix View (Matching Screenshot 3)
     */
    public function opcrMatrix($id)
    {
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();
        $isHead = $this->isOfficeHead($guard, $user);

        $opcr = SpmsOpcr::with(['office', 'head', 'items.assignedEmployees'])->findOrFail($id);

        if (!$isHead && $guard === 'employee' && $user->emp_dept != $opcr->office_id) {
            return redirect()->route('spms.ipcr')
                ->with('error', 'Unauthorized access to another office OPCR matrix.');
        }

        // STRICT SAME OFFICE SCOPING: Get employees belonging strictly to this OPCR's office!
        $officeEmployees = Employee::where('emp_dept', $opcr->office_id)
            ->where('stat_1', 1) // Active employees
            ->orderBy('lname', 'asc')
            ->get();

        return view('spms.opcr_matrix', compact('guard', 'user', 'isHead', 'opcr', 'officeEmployees'));
    }

    /**
     * Store / Update OPCR Row Item
     */
    public function storeOpcrItem(Request $request)
    {
        $request->validate([
            'opcr_id' => 'required|exists:spms_opcrs,id',
            'category' => 'required|string',
            'mfo_pap' => 'required|string',
            'success_indicators' => 'required|string',
            'link_to_source' => 'nullable|url',
            'allotted_budget' => 'nullable|string',
            'division_accountable' => 'nullable|string',
        ]);

        $opcr = SpmsOpcr::findOrFail($request->opcr_id);

        if ($request->filled('item_id')) {
            $item = SpmsOpcrItem::where('opcr_id', $opcr->id)->findOrFail($request->item_id);
            $item->update([
                'category' => $request->category,
                'subcategory' => $request->subcategory,
                'mfo_pap' => $request->mfo_pap,
                'success_indicators' => $request->success_indicators,
                'link_to_source' => $request->link_to_source,
                'allotted_budget' => $request->allotted_budget,
                'division_accountable' => $request->division_accountable,
            ]);
            $msg = 'OPCR row item updated successfully.';
        } else {
            SpmsOpcrItem::create([
                'opcr_id' => $opcr->id,
                'category' => $request->category,
                'subcategory' => $request->subcategory,
                'mfo_pap' => $request->mfo_pap,
                'success_indicators' => $request->success_indicators,
                'link_to_source' => $request->link_to_source,
                'allotted_budget' => $request->allotted_budget,
                'division_accountable' => $request->division_accountable,
            ]);
            $msg = 'OPCR row item added successfully.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete OPCR Row Item
     */
    public function deleteOpcrItem($id)
    {
        $item = SpmsOpcrItem::findOrFail($id);
        
        // Also remove associated cascaded IPCR items to maintain integrity
        SpmsIpcrItem::where('opcr_item_id', $item->id)->delete();
        
        $item->delete();

        return back()->with('success', 'OPCR row item and its cascaded assignments were deleted.');
    }

    /**
     * CASCADE OPCR Row Item to Selected Office Employee(s)
     * STRICT RULE: Employees MUST belong to the SAME office as the OPCR owner!
     */
    public function cascadeOpcrItem(Request $request)
    {
        $request->validate([
            'opcr_item_id' => 'required|exists:spms_opcr_items,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $opcrItem = SpmsOpcrItem::with('opcr')->findOrFail($request->opcr_item_id);
        $opcr = $opcrItem->opcr;
        $guard = $this->getGuard();
        $assignerId = ($guard === 'employee') ? auth()->guard('employee')->id() : null;

        $assignedNames = [];
        $failedNames = [];

        foreach ($request->employee_ids as $empId) {
            $employee = Employee::find($empId);

            if (!$employee) {
                continue;
            }

            // STRICT OFFICE RESTRICTION CHECK:
            // Ensure the employee belongs strictly to the OPCR's office!
            if ($employee->emp_dept != $opcr->office_id) {
                $failedNames[] = "{$employee->fname} {$employee->lname} (Different Office)";
                continue;
            }

            // Check if already assigned to avoid duplicate assignments
            $alreadyAssigned = SpmsIpcrItem::where('opcr_item_id', $opcrItem->id)
                ->where('employee_id', $employee->id)
                ->exists();

            if ($alreadyAssigned) {
                $failedNames[] = "{$employee->fname} {$employee->lname} (Already Assigned)";
                continue;
            }

            // Get or Create the Employee's IPCR for this year and semester
            $ipcr = SpmsIpcr::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'year' => $opcr->year,
                    'semester' => $opcr->semester,
                ],
                [
                    'office_id' => $employee->emp_dept,
                    'opcr_id' => $opcr->id,
                    'status' => 'Draft',
                ]
            );

            // Create IPCR Item linked directly to opcr_item_id
            SpmsIpcrItem::create([
                'ipcr_id' => $ipcr->id,
                'employee_id' => $employee->id,
                'opcr_item_id' => $opcrItem->id, // Traceable origin link!
                'assigned_by' => $assignerId,
                'category' => $opcrItem->category,
                'subcategory' => $opcrItem->subcategory,
                'mfo_pap' => $opcrItem->mfo_pap,
                'success_indicators' => $opcrItem->success_indicators,
                'status' => 'Assigned',
            ]);

            $assignedNames[] = "{$employee->fname} {$employee->lname}";
        }

        $msg = "";
        if (count($assignedNames) > 0) {
            $msg .= "Successfully cascaded row target to: " . implode(', ', $assignedNames) . ". ";
        }
        if (count($failedNames) > 0) {
            $msg .= "Skipped: " . implode(', ', $failedNames) . ".";
        }

        return back()->with('success', $msg);
    }

    /**
     * IPCR Matrix View for Logged-In Employee or Requested Employee
     */
    public function ipcrMatrix(Request $request, $id = null)
    {
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();
        $isHead = $this->isOfficeHead($guard, $user);

        $year = $request->input('year', date('Y'));
        $semester = $request->input('semester', (date('n') <= 6 ? 1 : 2));

        if ($guard === 'employee' && !$id) {
            $employeeId = $user->id;
        } else {
            $employeeId = $id ?? ($guard === 'employee' ? $user->id : Employee::first()?->id);
        }

        $employee = Employee::findOrFail($employeeId);
        $office = Office::find($employee->emp_dept);

        $ipcr = SpmsIpcr::with(['items.opcrItem', 'items.assigner'])
            ->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'year' => $year,
                    'semester' => $semester,
                ],
                [
                    'office_id' => $employee->emp_dept ?? 3,
                    'status' => 'Draft',
                ]
            );

        return view('spms.ipcr_matrix', compact(
            'guard', 'user', 'isHead', 'employee', 'office', 'ipcr', 'year', 'semester'
        ));
    }

    /**
     * Submit Employee Accomplishment & Evidence Attachment
     */
    public function submitAccomplishment(Request $request)
    {
        $request->validate([
            'ipcr_item_id' => 'required|exists:spms_ipcr_items,id',
            'actual_accomplishment' => 'required|string',
            'evidence_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:10240', // Max 10MB
        ]);

        $ipcrItem = SpmsIpcrItem::findOrFail($request->ipcr_item_id);

        $filePath = $ipcrItem->evidence_file;
        if ($request->hasFile('evidence_file')) {
            $file = $request->file('evidence_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('spms_evidence', $fileName, 'public');
        }

        $ipcrItem->update([
            'actual_accomplishment' => $request->actual_accomplishment,
            'evidence_file' => $filePath,
            'status' => 'Submitted',
        ]);

        return back()->with('success', 'Accomplishment and evidence uploaded successfully.');
    }

    /**
     * Office Head Rating Entry
     */
    public function rateIpcrItem(Request $request)
    {
        $request->validate([
            'ipcr_item_id' => 'required|exists:spms_ipcr_items,id',
            'rating_q' => 'nullable|numeric|between:1,5',
            'rating_e' => 'nullable|numeric|between:1,5',
            'rating_t' => 'nullable|numeric|between:1,5',
            'remarks' => 'nullable|string',
        ]);

        $item = SpmsIpcrItem::findOrFail($request->ipcr_item_id);

        $q = $request->input('rating_q', $item->rating_q);
        $e = $request->input('rating_e', $item->rating_e);
        $t = $request->input('rating_t', $item->rating_t);

        $ratings = array_filter([$q, $e, $t], fn($v) => !is_null($v) && $v > 0);
        $ave = (count($ratings) > 0) ? round(array_sum($ratings) / count($ratings), 2) : null;

        $item->update([
            'rating_q' => $q,
            'rating_e' => $e,
            'rating_t' => $t,
            'rating_ave' => $ave,
            'remarks' => $request->input('remarks', $item->remarks),
            'status' => 'Evaluated',
        ]);

        return back()->with('success', 'IPCR item evaluation updated successfully.');
    }

    /**
     * View or Download IPCR Evidence Attachment
     */
    public function viewEvidence(Request $request, $id)
    {
        $ipcrItem = SpmsIpcrItem::findOrFail($id);

        if (!$ipcrItem->evidence_file) {
            abort(404, 'No evidence file attached to this item.');
        }

        $fullPath = storage_path('app/public/' . $ipcrItem->evidence_file);

        if (!file_exists($fullPath)) {
            abort(404, 'File not found on server storage.');
        }

        if ($request->has('download')) {
            return response()->download($fullPath, basename($fullPath));
        }

        $mime = mime_content_type($fullPath) ?: 'application/pdf';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Employee Self-Add or Edit Custom IPCR Item
     */
    public function storeIpcrItem(Request $request)
    {
        $request->validate([
            'ipcr_id' => 'required|exists:spms_ipcrs,id',
            'category' => 'required|string',
            'mfo_pap' => 'required|string',
            'success_indicators' => 'required|string',
        ]);

        $ipcr = SpmsIpcr::findOrFail($request->ipcr_id);
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();

        if ($guard === 'employee' && $ipcr->employee_id != $user->id) {
            return back()->with('error', 'Unauthorized access to this IPCR matrix.');
        }

        if ($request->filled('item_id')) {
            $item = SpmsIpcrItem::findOrFail($request->item_id);
            $item->update([
                'category' => $request->category,
                'mfo_pap' => $request->mfo_pap,
                'success_indicators' => $request->success_indicators,
            ]);
            $msg = 'Custom IPCR objective updated successfully.';
        } else {
            SpmsIpcrItem::create([
                'ipcr_id' => $ipcr->id,
                'employee_id' => $ipcr->employee_id,
                'assigned_by' => $user->id,
                'category' => $request->category,
                'mfo_pap' => $request->mfo_pap,
                'success_indicators' => $request->success_indicators,
                'status' => 'Assigned',
            ]);
            $msg = 'Custom IPCR objective added successfully.';
        }

        return back()->with('success', $msg);
    }
}
