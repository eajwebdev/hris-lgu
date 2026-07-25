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

        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();

        $opcr = SpmsOpcr::firstOrCreate(
            [
                'office_id' => $office->id,
                'year' => $request->year,
                'semester' => $request->semester,
            ],
            [
                'office_head_id' => $office->office_head_id ?? ($user ? $user->id : null),
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

        // STRICT SAME OFFICE SCOPING: Get REGULAR & PERMANENT employees belonging strictly to this OPCR's office!
        $allOfficeEmployees = Employee::where('emp_dept', $opcr->office_id)
            ->where('stat_1', 1) // Active employees
            ->orderBy('lname', 'asc')
            ->get();

        $officeEmployees = $allOfficeEmployees->reject(function ($emp) {
            $empStatusStr = strtolower((string) ($emp->emp_status ?? ''));
            $positionStr = strtolower((string) ($emp->position ?? ''));
            $combinedStr = $empStatusStr . ' ' . $positionStr;

            return str_contains($combinedStr, 'job order')
                || str_contains($combinedStr, 'contract')
                || str_contains($combinedStr, 'cos')
                || str_contains($combinedStr, 'jo')
                || str_contains($combinedStr, 'part');
        });

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
        $user = auth()->guard($guard)->user();
        $assignerId = $user ? $user->id : null;

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

            // EXCLUDE JO / COS / Part-time Employees:
            $empStatusStr = strtolower((string) ($employee->emp_status ?? ''));
            $positionStr = strtolower((string) ($employee->position ?? ''));
            $combinedStr = $empStatusStr . ' ' . $positionStr;
            $isJoOrCos = str_contains($combinedStr, 'job order')
                || str_contains($combinedStr, 'contract')
                || str_contains($combinedStr, 'cos')
                || str_contains($combinedStr, 'jo')
                || str_contains($combinedStr, 'part');

            if ($isJoOrCos) {
                $failedNames[] = "{$employee->fname} {$employee->lname} (JO/COS/Part-time employees cannot be assigned OPCR targets)";
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
     * OPCR Item Rating Entry
     */
    public function rateOpcrItem(Request $request)
    {
        $request->validate([
            'opcr_item_id' => 'required|exists:spms_opcr_items,id',
            'rating_q' => 'nullable|numeric|between:1,5',
            'rating_e' => 'nullable|numeric|between:1,5',
            'rating_t' => 'nullable|numeric|between:1,5',
            'remarks' => 'nullable|string',
        ]);

        $item = SpmsOpcrItem::findOrFail($request->opcr_item_id);

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
        ]);

        return back()->with('success', 'OPCR row item rating updated successfully.');
    }

    /**
     * Load Official LGU OPCR Template Items from Excel Standard
     */
    public function loadOpcrTemplate(Request $request)
    {
        $request->validate([
            'opcr_id' => 'required|exists:spms_opcrs,id',
        ]);

        $opcr = SpmsOpcr::findOrFail($request->opcr_id);

        $defaultOpcrItems = [
            // CORE FUNCTIONS
            [
                'category' => 'Core Functions',
                'subcategory' => 'POLICY AND PROGRAM IMPLEMENTATION',
                'mfo_pap' => 'Translates national and local policies into actionable plans, program and projects within the department and overseeing the execution of these initiatives to ensure they are carried out effectively and efficiently',
                'success_indicators' => '100% of national and local policies are translated into actionable programs and projects and execution of these initiatives are fully supervised',
                'actual_accomplishment' => '95% of national and local policies were translated into actionable programs and projects and execution of these initiatives were fully supervised',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 5, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'POLICY AND PROGRAM IMPLEMENTATION',
                'mfo_pap' => 'Prepare memoranda, executive orders, MOA, letters etc. as requested by the office of the mayor',
                'success_indicators' => 'Memoranda, orders, MOA and letters shall have been properly prepared.',
                'actual_accomplishment' => 'All memoranda, executive orders, MOA and letters requested were promptly prepared.',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Directs and supervises the daily operations of the department',
                'success_indicators' => '100% of the staff are given directions and specific tasks for the operations of the department and completely supervised',
                'actual_accomplishment' => '100% of the staff were given directions and specific tasks for the operations of the department and 95% were supervised',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 5, 'rating_ave' => 5.00,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => "Assign numbers to PRs, Po's, JRs, and JOs",
                'success_indicators' => '100% of PRs, Requests for Sealed Quotations, POs, JRs, and JOs subjected for numbering shall have been properly numbered.',
                'actual_accomplishment' => '100% of PRs, Requests for Sealed Quotations, POs, JRs, and JOs subjected for numbering were properly assigned numbers.',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Assign property number and keep a property card for each property acquired',
                'success_indicators' => '100% of the property acquired shall have been properly numbered with a corresponding property card each.',
                'actual_accomplishment' => '100% of the property acquired are properly numbered with corresponding property card each.',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Issue RIS,ICS, and ARE/PAR as the case may require',
                'success_indicators' => '100% of procured supplies,materials,and equipment shall have been properly issued and received by concerned personnel using the appropriate document as proofs of',
                'actual_accomplishment' => '100% of procured supplies,materials,and equipment were properly issued and received by concerned personnel with the appropriate use of RIS, ICS, and ARE.',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Conduct inventory of PPEs supplies and materials in all offices or departments',
                'success_indicators' => '100 % of the inventory reports submitted by the offices are accurately consolidated, reviewed and submitted to the COA within the first quarter of the calendar year.',
                'actual_accomplishment' => '100% of the accurate inventory reports are consolidated, reviewed and submitted to the COA within the first quarter of the calendar year.',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Recommend transfer of unserviceable property/ donation of a serviceable property as requested by the concerned agency/organization',
                'success_indicators' => '100% of the requests shall have been acted upon promptly.',
                'actual_accomplishment' => '100% of the requested donation was approved by the local sanggunian. Requested transfer was carried out immediately alter the approval.',
                'rating_q' => 4, 'rating_e' => 5, 'rating_t' => 5, 'rating_ave' => 5.00,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Ensure disposal of unserviceable property',
                'success_indicators' => '100% of the disposal of unserviceable property shall have been supervised properly.',
                'actual_accomplishment' => '100% of the disposal of unserviceable property shall have been supervised properly.',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 4, 'rating_ave' => 4.67,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Ensure timely registration/renewal of registration and insurance vehicles',
                'success_indicators' => '100% of the municipal vehicle shall have been registered and insured',
                'actual_accomplishment' => '100% of the municipal vehicles were registered and insured',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 4, 'rating_ave' => 4.67,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Issue fuel slips and prepare payment documents',
                'success_indicators' => '100% of the fuel slips shall have been issued and payment documents prepared properly',
                'actual_accomplishment' => '100% of the fuel slips were issued and payment documents prepared properly',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => 'Ensure proper utilization of fuel for heavy equipment and other vehicles',
                'success_indicators' => '100% of the fuel consumption shall been monitored not to exceed the 600 thousand pesos monthly utilization',
                'actual_accomplishment' => '100% monthly fuel consumption did not exceed to 600 thousand pesos',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 5, 'rating_ave' => 5.00,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'SERVICE DELIVERY AND PUBLIC ENGAGEMENT',
                'mfo_pap' => 'Ensures effective and efficient delivery of the basic services of the office',
                'success_indicators' => '100% of the basic services of the office are effectively and efficiently carried out',
                'actual_accomplishment' => '100% of the basic services of the office were effectively carried out with 90% efficiency',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'PERSONNEL MANAGEMENT',
                'mfo_pap' => 'Ensures that office personnel are continuousely undergoing training/mentoring/coaching sessions to ensure professional growth',
                'success_indicators' => '100% of the personnel are trained, mentored, and coached',
                'actual_accomplishment' => '90% of the personnel were trained, mentored, and coached',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 5, 'rating_ave' => 4.67,
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'PERSONNEL MANAGEMENT',
                'mfo_pap' => 'Ensures that personnel performance evaluation is given paramount importance',
                'success_indicators' => '100% of the IPCRFs are reviewed and ratings are agreed upon by the personnel concerned',
                'actual_accomplishment' => '100% of the IPCRFs were reviewed and ratings are agreed upon by the personnel concerned',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
            ],

            // STRATEGIC FUNCTIONS
            [
                'category' => 'Strategic Functions',
                'subcategory' => 'STRATEGIC PLANNING AND OVERSIGHT',
                'mfo_pap' => 'Formulates long-term strategies for the department and the municipality',
                'success_indicators' => '100% of training workshops to formulate plans for the department and municipality are participated in',
                'actual_accomplishment' => '100% of training workshops to formulate plans for the department and municipality were participated in',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Strategic Functions',
                'subcategory' => 'FINANCIAL RESOURCE MANAGEMENT',
                'mfo_pap' => 'Prepares budget, monitoring the use of funds and controlling expenditures to ensure fiscal responsibility and sustainability',
                'success_indicators' => 'Shall have prepared annual budget and managed funds effectively and efficiently',
                'actual_accomplishment' => 'Prepared the required annual budget and funds were spent based on PPMP and APP',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],

            // SUPPORT FUNCTIONS
            [
                'category' => 'Support Functions',
                'subcategory' => 'COMPLIANCE AND REGULATION',
                'mfo_pap' => 'Ensures that all departmental operations and staff adhere to established rules, regulations, and laws',
                'success_indicators' => 'Shall have ensured that rules, regulations, and laws established in the office are fully adhered to',
                'actual_accomplishment' => 'Made sure that 100% of established rules, regulations, and laws of the office were fully adhered to',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
            ],
            [
                'category' => 'Support Functions',
                'subcategory' => 'HUMAN RESOURCE MANAGEMENT',
                'mfo_pap' => 'Manages personnel within the Local Government Unit by handling recruitment, retention, training and performance evaluation',
                'success_indicators' => 'Shall have managed effectively and efficiently of the Human Resource programs and projects of the municipal government',
                'actual_accomplishment' => '100% of Human Resource activities were carried out',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
            ],
            [
                'category' => 'Support Functions',
                'subcategory' => 'HUMAN RESOURCE MANAGEMENT',
                'mfo_pap' => 'Ensures compliance to laws and regulations of the Anti-Red Tape Authority',
                'success_indicators' => 'Shall have fully ensured compliance to laws and regulations of ARTA',
                'actual_accomplishment' => '100% of required ARTA reports were submitted on time',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Support Functions',
                'subcategory' => 'SERVICE DELIVERY AND PUBLIC ENGAGEMENT',
                'mfo_pap' => "Attends and participates in department head's meetings",
                'success_indicators' => '100% of all department and committee meetings shall have been actively participated.',
                'actual_accomplishment' => '98% of department and committee meetings were actively participated in',
                'rating_q' => 4, 'rating_e' => 5, 'rating_t' => 4, 'rating_ave' => 4.33,
            ],
            [
                'category' => 'Support Functions',
                'subcategory' => 'SERVICE DELIVERY AND PUBLIC ENGAGEMENT',
                'mfo_pap' => 'Attends flag raising and lowering ceremonies',
                'success_indicators' => '100% of required flag raising and lowering ceremonies shall have been attended.',
                'actual_accomplishment' => 'Attended 98% of required flag raising and lowering ceremonies',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
            ],
        ];

        $orderStart = (int) ($opcr->items()->max('sort_order') ?? 0);

        foreach ($defaultOpcrItems as $idx => $itemData) {
            SpmsOpcrItem::create([
                'opcr_id' => $opcr->id,
                'category' => $itemData['category'],
                'subcategory' => $itemData['subcategory'] ?? null,
                'mfo_pap' => $itemData['mfo_pap'],
                'success_indicators' => $itemData['success_indicators'],
                'actual_accomplishment' => null,
                'rating_q' => null,
                'rating_e' => null,
                'rating_t' => null,
                'rating_ave' => null,
                'sort_order' => $orderStart + $idx + 1,
            ]);
        }

        return back()->with('success', 'Official LGU OPCR template items loaded successfully.');
    }

    /**
     * Clear / Delete All OPCR Items for an OPCR Document
     */
    public function clearOpcrItems(Request $request, $id)
    {
        $opcr = SpmsOpcr::findOrFail($id);
        $opcr->items()->delete();

        return back()->with('success', 'All OPCR rows cleared successfully.');
    }

    /**
     * Clear / Delete All IPCR Items for an IPCR Document
     */
    public function clearIpcrItems(Request $request, $id)
    {
        $ipcr = SpmsIpcr::findOrFail($id);
        $ipcr->items()->delete();

        return back()->with('success', 'All IPCR rows cleared successfully.');
    }

    /**
     * IPCR List View (Matching Screenshot & OPCR List layout)
     */
    public function ipcrList(Request $request)
    {
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();
        $isHead = $this->isOfficeHead($guard, $user);

        $year = $request->input('year', date('Y'));
        $semester = $request->input('semester', (date('n') <= 6 ? 1 : 2));
        $search = trim((string)$request->input('search', ''));

        if ($guard === 'web') {
            $managedOffices = Office::where('id', '>', 2)->get();
            $selectedOfficeId = $request->input('office_id', $managedOffices->first()?->id ?? 3);
        } else {
            $selectedOfficeId = $user->emp_dept ?? 3;
            $managedOffices = Office::where('id', $selectedOfficeId)->get();
        }

        $activeOffice = Office::find($selectedOfficeId);

        if (!$isHead && $guard === 'employee') {
            // STRICT PRIVACY: Regular employees only see THEIR OWN IPCR document!
            $query = Employee::where('id', $user->id);
        } else {
            // Office Heads / Admins see their office employees' IPCR documents for evaluation
            $query = Employee::where('emp_dept', $selectedOfficeId)->where('stat_1', 1);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                  ->orWhere('lname', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        $officeEmployees = $query->orderBy('lname', 'asc')->get();

        return view('spms.ipcr_list', compact(
            'guard', 'user', 'isHead', 'managedOffices', 'activeOffice', 'officeEmployees', 'year', 'semester', 'search'
        ));
    }

    /**
     * IPCR Matrix View for Logged-In Employee or Requested Employee
     */
    public function ipcrMatrix(Request $request, $id = null)
    {
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();
        $isHead = $this->isOfficeHead($guard, $user);

        if ($guard === 'employee' && !$isHead && $id && $id != $user->id) {
            return redirect()->route('spms.ipcr')
                ->with('error', 'Unauthorized access: You can only view your own IPCR.');
        }

        $year = $request->input('year', date('Y'));
        $semester = $request->input('semester', (date('n') <= 6 ? 1 : 2));

        if ($guard === 'employee' && !$id) {
            $employeeId = $user->id;
        } else {
            $employeeId = $id ?? ($guard === 'employee' ? $user->id : Employee::first()?->id);
        }

        $employee = Employee::findOrFail($employeeId);
        $office = Office::with('head')->find($employee->emp_dept);
        $officeHead = $office?->head;
        if (!$officeHead && $office) {
            $officeHead = Employee::where('emp_dept', $office->id)
                ->where(function($q) {
                    $q->where('position', 'like', '%Head%')
                      ->orWhere('position', 'like', '%Chief%')
                      ->orWhere('position', 'like', '%Director%')
                      ->orWhere('position', 'like', '%Manager%');
                })->first();
        }

        $ipcr = SpmsIpcr::with(['items.opcrItem', 'items.assigner', 'office.head'])
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

        $empStatusStr = strtolower((string) ($employee->emp_status ?? ''));
        $positionStr = strtolower((string) ($employee->position ?? ''));
        $combinedStr = $empStatusStr . ' ' . $positionStr;

        $isJoOrCos = str_contains($combinedStr, 'job order') 
            || str_contains($combinedStr, 'contract') 
            || str_contains($combinedStr, 'cos') 
            || str_contains($combinedStr, 'jo');

        return view('spms.ipcr_matrix', compact(
            'guard', 'user', 'isHead', 'employee', 'office', 'officeHead', 'ipcr', 'year', 'semester', 'isJoOrCos'
        ));
    }

    /**
     * Submit Employee Accomplishment & Evidence Attachment (Link Only)
     */
    public function submitAccomplishment(Request $request)
    {
        $request->validate([
            'ipcr_item_id' => 'required|exists:spms_ipcr_items,id',
            'actual_accomplishment' => 'required|string',
            'evidence_file' => 'nullable|url',
        ]);

        $ipcrItem = SpmsIpcrItem::findOrFail($request->ipcr_item_id);

        $ipcrItem->update([
            'actual_accomplishment' => $request->actual_accomplishment,
            'evidence_file' => $request->filled('evidence_file') ? trim($request->evidence_file) : null,
            'status' => 'Submitted',
        ]);

        return back()->with('success', 'Accomplishment and evidence link saved successfully.');
    }

    /**
     * Delete an IPCR item row
     */
    public function deleteIpcrItem($id)
    {
        $item = SpmsIpcrItem::findOrFail($id);
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();

        if ($guard === 'employee' && $item->employee_id != $user->id) {
            return back()->with('error', 'Unauthorized access.');
        }

        $item->delete();

        return back()->with('success', 'IPCR objective item deleted successfully.');
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

        // Recalculate parent IPCR overall ratings
        $ipcr = SpmsIpcr::find($item->ipcr_id);
        if ($ipcr) {
            $allRated = SpmsIpcrItem::where('ipcr_id', $ipcr->id)->whereNotNull('rating_ave')->get();
            if ($allRated->count() > 0) {
                $finalScore = round($allRated->avg('rating_ave'), 2);
                $adjectival = 'Needs Improvement';
                if ($finalScore >= 4.5) {
                    $adjectival = 'Outstanding';
                } elseif ($finalScore >= 3.5) {
                    $adjectival = 'Very Satisfactory';
                } elseif ($finalScore >= 2.5) {
                    $adjectival = 'Satisfactory';
                }

                $ipcr->update([
                    'final_numerical_rating' => $finalScore,
                    'final_adjectival_rating' => $adjectival,
                    'status' => 'Evaluated',
                ]);
            }
        }

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

    /**
     * Update OPCR Footer Signatories (Prepared by, PMT Members, Approved by)
     */
    public function updateSignatories(Request $request, $id)
    {
        $request->validate([
            'prepared_by_name' => 'required|string|max:255',
            'prepared_by_position' => 'required|string|max:255',
            'pmt_members' => 'nullable|string',
            'approved_by_name' => 'required|string|max:255',
            'approved_by_position' => 'required|string|max:255',
        ]);

        $opcr = SpmsOpcr::findOrFail($id);

        $opcr->update([
            'prepared_by_name' => $request->prepared_by_name,
            'prepared_by_position' => $request->prepared_by_position,
            'pmt_members' => $request->pmt_members,
            'approved_by_name' => $request->approved_by_name,
            'approved_by_position' => $request->approved_by_position,
        ]);

        return back()->with('success', 'OPCR signatories updated successfully.');
    }

    /**
     * Update IPCR Footer Signatories (Ratee, Assessed by, Approved by)
     */
    public function updateIpcrSignatories(Request $request, $id)
    {
        $request->validate([
            'ratee_name' => 'required|string|max:255',
            'ratee_position' => 'required|string|max:255',
            'assessed_by_name' => 'required|string|max:255',
            'assessed_by_position' => 'required|string|max:255',
            'approved_by_name' => 'required|string|max:255',
            'approved_by_position' => 'required|string|max:255',
        ]);

        $ipcr = SpmsIpcr::findOrFail($id);

        $ipcr->update([
            'ratee_name' => $request->ratee_name,
            'ratee_position' => $request->ratee_position,
            'assessed_by_name' => $request->assessed_by_name,
            'assessed_by_position' => $request->assessed_by_position,
            'approved_by_name' => $request->approved_by_name,
            'approved_by_position' => $request->approved_by_position,
        ]);

        return back()->with('success', 'IPCR signatories updated successfully.');
    }

    /**
     * Reorder OPCR items within a category
     */
    public function reorderOpcrItems(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:spms_opcr_items,id',
        ]);

        foreach ($request->order as $index => $itemId) {
            SpmsOpcrItem::where('id', $itemId)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['status' => 'success', 'message' => 'OPCR item order updated']);
    }

    /**
     * Reorder IPCR items within a category
     */
    public function reorderIpcrItems(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:spms_ipcr_items,id',
        ]);

        foreach ($request->order as $index => $itemId) {
            SpmsIpcrItem::where('id', $itemId)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['status' => 'success', 'message' => 'IPCR item order updated']);
    }

    /**
     * Load Contract of Service (COS) / Job Order (JO) Performance Rating Template
     */
    public function loadCosTemplate(Request $request)
    {
        $request->validate([
            'ipcr_id' => 'required|exists:spms_ipcrs,id',
            'template_type' => 'nullable|string|in:general_services,admin_support,custom',
        ]);

        $ipcr = SpmsIpcr::findOrFail($request->ipcr_id);
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();

        if ($guard === 'employee' && $ipcr->employee_id != $user->id) {
            return back()->with('error', 'Unauthorized access to this IPCR matrix.');
        }

        $templateType = $request->input('template_type', 'general_services');

        if ($templateType === 'official_regular') {
            $defaultItems = [
                [
                    'category' => 'Core Functions',
                    'subcategory' => 'POLICY AND PROGRAM IMPLEMENTATION',
                    'mfo_pap' => 'Translates national and local policies into actionable plans, programs and projects within the department and overseeing execution',
                    'success_indicators' => '100% of national and local policies are translated into actionable programs and projects and execution of these initiatives are fully supervised.',
                ],
                [
                    'category' => 'Core Functions',
                    'subcategory' => 'OPERATIONAL MANAGEMENT',
                    'mfo_pap' => 'Directs and supervises the daily operations of the department',
                    'success_indicators' => '100% of the staff are given directions and specific tasks for the operations of the department and completely supervised.',
                ],
                [
                    'category' => 'Core Functions',
                    'subcategory' => 'SERVICE DELIVERY AND PUBLIC ENGAGEMENT',
                    'mfo_pap' => 'Ensures effective and efficient delivery of the basic services of the office',
                    'success_indicators' => '100% of the basic services of the office are effectively and efficiently carried out.',
                ],
                [
                    'category' => 'Core Functions',
                    'subcategory' => 'PERSONNEL MANAGEMENT',
                    'mfo_pap' => 'Ensures that office personnel are continuously undergoing training/mentoring/coaching sessions and IPCRFs are evaluated',
                    'success_indicators' => '100% of the personnel are trained, mentored, and coached; 100% of IPCRFs are reviewed and ratings agreed upon.',
                ],
                [
                    'category' => 'Core Functions',
                    'subcategory' => 'FINANCIAL RESOURCE MANAGEMENT',
                    'mfo_pap' => 'Prepares budget, monitoring the use of funds and controlling expenditures to ensure fiscal responsibility and sustainability',
                    'success_indicators' => 'Shall have prepared annual budget and managed funds effectively and efficiently.',
                ],
                [
                    'category' => 'Support Functions',
                    'subcategory' => 'COMPLIANCE AND REGULATION',
                    'mfo_pap' => 'Ensures that all departmental operations and staff adhere to established rules, regulations, and laws',
                    'success_indicators' => 'Shall have ensured that rules, regulations, and laws established in the office are fully adhered to.',
                ],
                [
                    'category' => 'Support Functions',
                    'subcategory' => 'SERVICE DELIVERY AND PUBLIC ENGAGEMENT',
                    'mfo_pap' => 'Attends and participates in department head meetings, conferences, trainings, and flag ceremonies',
                    'success_indicators' => '100% of required department meetings, trainings, and flag raising/lowering ceremonies attended.',
                ],
            ];
        } elseif ($templateType === 'admin_support') {
            $defaultItems = [
                [
                    'category' => 'Core Functions',
                    'mfo_pap' => 'Administrative & Clerical Support',
                    'success_indicators' => 'Prepares, encodes, files, and processes official office documents, communications, and records accurately and on time.',
                ],
                [
                    'category' => 'Core Functions',
                    'mfo_pap' => 'Client Service & Records Assistance',
                    'success_indicators' => 'Receives and routes incoming/outgoing documents; assists clients courteously and resolves front-line inquiries.',
                ],
                [
                    'category' => 'Support Functions',
                    'mfo_pap' => 'Department & Executive Support Duties',
                    'success_indicators' => 'Performs other tasks assigned by Department Head; attends Flag Raising Ceremony; participates in capacity enhancement and LCE activities.',
                ],
                [
                    'category' => 'Support Functions',
                    'mfo_pap' => 'Work Ethics & Conduct',
                    'success_indicators' => 'Demonstrates Punctuality, Attendance, Integrity, Teamwork, Professionalism, Time Management, Respect, Adaptability, and Customer Service Skills.',
                ],
            ];
        } else {
            // General Services / Maintenance / COS Personnel Rating Form
            $defaultItems = [
                [
                    'category' => 'Core Functions',
                    'mfo_pap' => 'Hallway & Premises Maintenance',
                    'success_indicators' => 'Maintains the cleanliness and orderliness of the hallways and assigned areas of the Government Center/Office premises.',
                ],
                [
                    'category' => 'Core Functions',
                    'mfo_pap' => 'Waste Gathering & Proper Disposal',
                    'success_indicators' => 'Gathers garbage from landscaped/assigned areas; segregates and disposes garbage properly daily.',
                ],
                [
                    'category' => 'Core Functions',
                    'mfo_pap' => 'Operational Task Execution',
                    'success_indicators' => 'Performs other operational tasks as directed by the immediate supervisor efficiently and on time.',
                ],
                [
                    'category' => 'Support Functions',
                    'mfo_pap' => 'Departmental & LCE Activities',
                    'success_indicators' => 'Does other tasks assigned by Department Head; attends Flag Raising Ceremony; participates in capacity enhancement and LCE sanctioned activities.',
                ],
                [
                    'category' => 'Support Functions',
                    'mfo_pap' => 'Work Ethics & Rating Standard',
                    'success_indicators' => 'Demonstrates Punctuality, Attendance, Responsibility, Integrity, Teamwork, Professionalism, Time Management, Continuous Improvement, Respect, Accountability, Adaptability, and Customer Service Skills.',
                ],
            ];
        }

        $orderStart = (int) ($ipcr->items()->max('sort_order') ?? 0);

        foreach ($defaultItems as $idx => $itemData) {
            SpmsIpcrItem::create([
                'ipcr_id' => $ipcr->id,
                'employee_id' => $ipcr->employee_id,
                'assigned_by' => $user->id,
                'category' => $itemData['category'],
                'subcategory' => $itemData['subcategory'] ?? null,
                'mfo_pap' => $itemData['mfo_pap'],
                'success_indicators' => $itemData['success_indicators'],
                'sort_order' => $orderStart + $idx + 1,
                'status' => 'Assigned',
            ]);
        }

        return back()->with('success', 'Contract of Service / Job Order Performance Rating template loaded successfully.');
    }

    /**
     * Print Printable Performance Rating Form for COS / JO / Part-timer (Matching Image 1 format)
     */
    public function printCosRating(Request $request, $id)
    {
        $guard = $this->getGuard();
        $user = auth()->guard($guard)->user();
        $isHead = $this->isOfficeHead($guard, $user);

        if ($guard === 'employee' && !$isHead && $id != $user->id) {
            return back()->with('error', 'Unauthorized access to another employee rating form.');
        }

        $year = $request->input('year', date('Y'));
        $semester = $request->input('semester', (date('n') <= 6 ? 1 : 2));

        $employee = Employee::findOrFail($id);
        $office = Office::with('head')->find($employee->emp_dept);
        $officeHead = $office?->head;

        $ipcr = SpmsIpcr::with(['items.opcrItem', 'items.assigner', 'office.head'])
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

        $empStatusStr = strtolower((string) ($employee->emp_status ?? ''));
        $positionStr = strtolower((string) ($employee->position ?? ''));
        $combinedStr = $empStatusStr . ' ' . $positionStr;

        $isJoOrCos = str_contains($combinedStr, 'job order') 
            || str_contains($combinedStr, 'contract') 
            || str_contains($combinedStr, 'cos') 
            || str_contains($combinedStr, 'jo')
            || str_contains($combinedStr, 'part');

        if ($isJoOrCos) {
            return view('spms.print_cos_rating', compact(
                'guard', 'user', 'isHead', 'employee', 'office', 'officeHead', 'ipcr', 'year', 'semester'
            ));
        }

        // Regular / Permanent / Casual Employee: Render official Landscape IPCR form matching Excel template
        return view('spms.print_ipcr_regular', compact(
            'guard', 'user', 'isHead', 'employee', 'office', 'officeHead', 'ipcr', 'year', 'semester'
        ));
    }
}
