<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmploymentHistory;
use App\Models\Office;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * An employee's employment history: the periods they have worked for the
 * LGU, with HR's two actions on it. Admin and HR only (face.registrar on the
 * routes).
 *
 * A returning employee keeps their one record — the same Employee ID, PDS,
 * photo, face and DTR and leave history — and is given a new period, so the
 * history reads as one career with gaps in it.
 */
class EmploymentHistoryController extends Controller
{
    public function show($id)
    {
        $employee = Employee::findOrFail($id);
        $guard = 'web';
        $empid = $employee->id;
        $columnstatus = app(EmployeeController::class)->columnStat($employee->emp_ID);

        $periods = $employee->employmentHistory()->with(['office', 'status'])->get();
        $current = $periods->firstWhere('date_end', null);

        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')->orderBy('office_name')->get();
        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();

        return view('emp.employment-history', compact('employee', 'guard', 'empid', 'columnstatus', 'periods', 'current', 'offices', 'stat'));
    }

    /**
     * Ends the current period and turns the employee's sign-in off. Their
     * record stays, ready for a rehire.
     */
    public function separate(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $current = $employee->currentEmployment;

        if (! $current) {
            return back()->with('error', 'This employee is already separated.');
        }

        $data = $request->validate([
            'date_end' => ['required', 'date', $current->date_start ? 'after_or_equal:' . $current->date_start->toDateString() : 'date'],
            'separation_reason' => ['required', Rule::in(EmploymentHistory::SEPARATION_REASONS)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'date_end.after_or_equal' => 'The separation date cannot be before the period began (' . $current->date_start?->format('M d, Y') . ').',
        ]);

        DB::transaction(function () use ($employee, $current, $data) {
            $current->update($data + ['recorded_by' => Auth::guard('web')->id()]);
            $employee->update(['stat_1' => 2]);
        });

        return back()->with('success', $this->name($employee) . ' has been separated. Their sign-in is now off.');
    }

    /**
     * Opens a new period, in what may be a different position, office and
     * status, and makes that the employee's record again.
     */
    public function rehire(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        if (! $employee->isSeparated()) {
            return back()->with('error', 'This employee is still employed. Record their separation first.');
        }

        $lastEnd = $employee->employmentHistory()->max('date_end');

        $data = $request->validate([
            'date_hired' => ['required', 'date', $lastEnd ? 'after:' . $lastEnd : 'date'],
            'position' => ['required', 'string', 'max:255'],
            'emp_dept' => ['required', 'exists:offices,id'],
            'emp_status' => ['required', 'exists:statuses,id'],
            'item_no' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'date_hired.after' => 'The rehire date must be after their last separation (' . ($lastEnd ? \Carbon\Carbon::parse($lastEnd)->format('M d, Y') : '') . ').',
        ]);

        DB::transaction(function () use ($employee, $data) {
            // Opened before the record is updated: the record's own hook
            // copies the update into the open period, which is then this one
            // and already says the same.
            $employee->employmentHistory()->create([
                'position' => $data['position'],
                'office_id' => $data['emp_dept'],
                'status_id' => $data['emp_status'],
                'item_no' => $data['item_no'] ?? null,
                'date_start' => $data['date_hired'],
                'remarks' => $data['remarks'] ?? null,
                'recorded_by' => Auth::guard('web')->id(),
            ]);

            $employee->update([
                'position' => $data['position'],
                'emp_dept' => $data['emp_dept'],
                'emp_status' => $data['emp_status'],
                'item_no' => $data['item_no'] ?? null,
                'date_hired' => $data['date_hired'],
                'stat_1' => 1,
            ]);
        });

        return back()->with('success', $this->name($employee) . ' has been rehired. Their sign-in is on again.');
    }

    private function name(Employee $employee): string
    {
        return trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname)));
    }
}
