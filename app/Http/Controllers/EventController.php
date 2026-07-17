<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Status;
use App\Models\Employee;
use App\Models\EventLog;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }
    
    public function eventIndex(){
        $guard = $this->getGuard();
        $status = Status::all();
        return view("events.event-read", compact('guard', 'status'));
    }

    public function eventShow()
    {
        $events = Event::select('id', 'title', 'venue', 'org_dept', 'emp_status', 'start', 'end', 'bg_color')
            ->where('event_stat', 1) // Optional: only active events
            ->get();

        return response()->json($events);
    }

    public function eventCreate(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'venue' => 'required',
            'start' => 'required|date',
            'end' => 'nullable|date', 
            'emp_status' => 'required',
            'bg_color' => 'required', 
            'org_dept' => 'required',
        ]);

        $event = Event::create([
            'title' => $request->input('title'),
            'venue' => $request->input('venue'),
            'start' => $request->input('start'),
            'end' => $request->input('end'),
            'emp_status' => $request->input('emp_status'),
            'bg_color' => $request->input('bg_color'),
            'org_dept' => $request->input('org_dept'),
            'remember_token' => Str::random(60),
            'event_stat' => 1,
        ]);

        $employeeQuery = Employee::query();

        
        if ($request->emp_status != 0) {
            $employeeQuery->where('emp_status', $request->emp_status);
        }

        $employees = $employeeQuery->pluck('emp_ID');

        foreach ($employees as $empid) {
            EventLog::create([
                'event_id' => $event->id,
                'empid' => $empid,
            ]);
        }

        \DB::commit();
        
        return redirect()->back()->with('success', 'Event stored successfully!');
    }

    public function eventUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:events,id',
            'title' => 'required',
            'venue' => 'required',
            'org_dept' => 'required',
            'start' => 'required|date',
            'end' => 'nullable|date',
            'bg_color' => 'required',
        ]);

        try {
            $event = Event::findOrFail($request->input('id'));
            $event->update([
                'title' => $request->input('title'),
                'venue' => $request->input('venue'),
                'org_dept' => $request->input('org_dept'),
                'start' => $request->input('start'),
                'end' => $request->input('end'),
                'bg_color' => $request->input('bg_color'),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 200, 'message' => 'Event updated successfully!']);
            }

            return redirect()->back()->with('success', 'Event updated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 500, 'message' => 'Failed to update event.'], 500);
            }

            return redirect()->back()->with('error', 'Failed to update event.');
        }
    }

    public function eventDestroy($id)
    {
        try {
            $event = Event::findOrFail($id);

            // Remove attendance rows tied to this event before deleting it.
            EventLog::where('event_id', $event->id)->delete();
            $event->delete();

            return response()->json(['status' => 200, 'message' => 'Event deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Failed to delete event.'], 500);
        }
    }

    /**
     * The event QR attendance scanner (Admin/HR only — gated by face.registrar).
     * The operator picks the event, then scans employee QR badges.
     */
    public function scanPortal()
    {
        $guard = $this->getGuard();

        // Active events, soonest first, so the operator picks the right one.
        $events = Event::where('event_stat', 1)
            ->orderBy('start')
            ->get(['id', 'title', 'venue', 'start', 'end']);

        return view('events.scan', compact('guard', 'events'));
    }

    /**
     * Record one QR scan against an event.
     *
     * The badge carries an encrypted emp_ID, not a name — the browser cannot
     * name an attendee, the server decides whose row moves. First scan clocks
     * the attendee IN; every later scan moves the clock-OUT to now, so the last
     * scan of the day is the one that sticks.
     */
    public function scanPunch(Request $request)
    {
        $request->validate([
            'event_id' => ['required', 'integer'],
            'qr'       => ['required', 'string', 'max:512'],
        ]);

        $event = Event::where('id', $request->event_id)->where('event_stat', 1)->first();

        if (! $event) {
            return response()->json(['status' => 404, 'message' => 'Event not found or no longer active.'], 404);
        }

        // Decrypt the badge. A garbled scan decrypts to nothing rather than to
        // somebody else's id — see the shortEncrypt on the printed QR cards.
        try {
            $empid = trim((string) shortDecrypt(trim($request->input('qr'))));
        } catch (\Throwable $e) {
            $empid = '';
        }

        if ($empid === '') {
            return response()->json(['status' => 422, 'message' => 'Unrecognised QR code. Please try again.'], 422);
        }

        $employee = Employee::where('emp_ID', $empid)->first();

        if (! $employee) {
            return response()->json(['status' => 404, 'message' => 'This badge does not match any employee.'], 422);
        }

        $log = EventLog::where('event_id', $event->id)->where('empid', $empid)->first();

        // Attendance is only for employees enrolled to the event (EventCreate
        // seeds one row per eligible employee). No row means not on the list.
        if (! $log) {
            return response()->json([
                'status'   => 409,
                'message'  => $this->scanName($employee) . ' is not on the attendee list for this event.',
                'employee' => $this->scanCard($employee),
            ], 409);
        }

        $now      = Carbon::now();
        $cooldown = 8; // seconds — a single pass past the lens must not read as in AND out

        if (is_null($log->in)) {
            $log->in = $now;
            $log->save();

            return $this->scanResult('CLOCK IN', true, $employee, $now, 'Clocked in');
        }

        // Already clocked in. Ignore a repeat scan of the same badge within the
        // cooldown so a fresh clock-in is not instantly flipped to a clock-out.
        $last = Carbon::parse($log->out ?? $log->in);

        if ($now->diffInSeconds($last) < $cooldown) {
            return $this->scanResult(
                $log->out ? 'CLOCK OUT' : 'CLOCK IN',
                false, $employee, $last, 'Already scanned a moment ago'
            );
        }

        // Last scan wins: move the clock-out to now.
        $log->out = $now;
        $log->save();

        return $this->scanResult('CLOCK OUT', true, $employee, $now, 'Clocked out');
    }

    private function scanResult(string $action, bool $recorded, Employee $employee, Carbon $at, string $message)
    {
        return response()->json([
            'status'   => 200,
            'recorded' => $recorded,
            'action'   => $action,
            'message'  => $message,
            'employee' => $this->scanCard($employee),
            'time'     => $at->format('h:i A'),
            'date'     => $at->format('M d, Y'),
        ]);
    }

    private function scanName(Employee $employee): string
    {
        return trim(strtoupper($employee->lname) . ', ' . strtoupper($employee->fname)
            . ' ' . strtoupper($employee->suffix ?? ''));
    }

    private function scanCard(Employee $employee): array
    {
        return [
            'name'     => $this->scanName($employee),
            'position' => $employee->position ?: 'Employee',
            'id'       => $employee->emp_ID,
            'initials' => strtoupper(substr($employee->fname, 0, 1) . substr($employee->lname, 0, 1)),
        ];
    }

    public function showReport(){
        $guard = $this->getGuard();
        $events = Event::all();
        $status = Status::all();

        return view("events.report", compact('guard', 'events', 'status'));
    }

    public function searchReport(Request $request){
        $request->validate([
            'eventid' => 'required',
            'statusid' => 'required',
        ]);

        $guard = $this->getGuard();
        $events = Event::all();
        $status = Status::all();

        $eventid = $request->input('eventid');
        $statusid = $request->input('statusid');

        return view("events.report", compact('guard', 'events', 'status', 'eventid', 'statusid'));
    }
    
    public function reportGenrate(Request $request)
    {
        $eventid = $request->eventid;
        $statusid = $request->statusid;

        $eventsdatas = Event::find($eventid);

        if (!$eventsdatas) {
            return redirect()->route('showReport')->with('error', 'Event not found.');
        }

        $events = EventLog::join('employees', 'event_logs.empid', '=', 'employees.emp_ID')
        ->when($eventid, function ($query) use ($eventid) {
            return $query->where('event_logs.event_id', $eventid);
        })
        ->when($statusid != 0, function ($query) use ($statusid) {
            return $query->where('employees.emp_status', $statusid);
        })
        ->where(function ($query) {
            $query->whereNotNull('event_logs.in')
                  ->where('event_logs.in', '!=', '')
                  ->orWhere(function ($query) {
                      $query->whereNotNull('event_logs.out')
                            ->where('event_logs.out', '!=', '');
                  });
        })
        ->orderBy('event_logs.updated_at', 'desc')
        ->select(
            'employees.fname',
            'employees.lname',
            'employees.suffix',
            'employees.position',
            'employees.emp_status',
            'event_logs.updated_at',
            'event_logs.in',
            'event_logs.out'
        )
        ->get();
    
    
        $chunkedEvents = $events->chunk(35);
    
        $customPaper = [0, 0, 612, 792];
    
        $pdf = \PDF::loadView('events.report-generate', compact('chunkedEvents', 'eventsdatas'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 30,
                'margin-left' => 10,
            ])
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $canvas = $domPdf->getCanvas();
                    $canvas->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                },
            ]);
    
        return $pdf->stream();
    }    

}
