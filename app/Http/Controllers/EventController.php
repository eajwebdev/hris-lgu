<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Status;
use App\Models\Employee;
use App\Models\EventLog;
use Illuminate\Support\Str;

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
