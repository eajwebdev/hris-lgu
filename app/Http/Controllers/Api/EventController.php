<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DtrTest;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    /**
     * The shared passcode is verified by the `event.passcode` middleware before
     * any of these actions run, so the controller no longer carries the secret
     * or repeats the check.
     */
    public function eventList($passcode)
    {
        $events = Event::where('event_stat', 1)->get();
        $eventData = [];

        foreach ($events as $event) {
            $eventData[] = [
                'id' => $event->id,
                'title' => $event->title,
            ];
        }

        return response()->json($eventData);
    }

    private function shortDecrypt($encrypted)
    {
        $key    = config('api.crypto.key');
        $cipher = config('api.crypto.cipher');
        $encrypted = strtr($encrypted, '-_', '+/');
        return openssl_decrypt(base64_decode($encrypted), $cipher, $key, 0);
    }

    public function eventLogin($passcode, $eventid, $encryptedempid)
    {
        $empid = $this->shortDecrypt($encryptedempid);

        if ($empid !== false) {
            $employee = Employee::where('emp_ID', $empid)->first();

            if (!$employee) {
                return response()->json(['message' => 'Employee not found.'], 404);
            }

            $fullname = strtoupper($employee->lname) . ', ' . strtoupper($employee->fname) . ' ' . strtoupper($employee->suffix);

            $log = EventLog::where('event_id', $eventid)
                        ->where('empid', $empid)
                        ->first();

            if (!$log) {
                return response()->json(['message' => 'Employee not registered for this event.'], 404);
            }

            if (is_null($log->in)) {
                $log->in = Carbon::now();
                $log->save();
                return response()->json(['message' => $fullname], 200);
            } elseif (!is_null($log->in)) {
                $log->out = Carbon::now();
                $log->save();
                return response()->json(['message' => $fullname], 200);
            }
        } else {
            return response()->json(['message' => 'Invalid employee ID.'], 400);
        }
    }

    public function eventLogs($passcode, $eventId)
    {
        $eventlogs = EventLog::join('employees', function($join) {
                $join->on(DB::raw('BINARY event_logs.empid'), '=', DB::raw('BINARY employees.emp_ID'));
            })
            ->where('event_logs.event_id', $eventId)
            ->where(function ($query) {
                $query->whereNotNull('event_logs.in')
                      ->orWhereNotNull('event_logs.out');
            })
            ->orderBy('event_logs.updated_at', 'desc')
            ->limit(10)
            ->select(
                'event_logs.in',
                'event_logs.out',
                'event_logs.event_id',
                'employees.lname',
                'employees.fname',
                'employees.suffix'
            )
            ->get();
            

            $eventlogData = [];
        
            foreach ($eventlogs as $log) {
                $suffix = $log->suffix ? $log->suffix : '';
                $fullname = strtoupper($log->lname) . ', ' . strtoupper($log->fname) . ' ' . $suffix;
        
                $eventlogData[] = [
                    'fullname' => trim($fullname),
                    'in' => $log->in,
                    'out' => $log->out,
                    'eventid' => $log->event_id,
                ];
            }
        
            return response()->json($eventlogData);
    }
}
