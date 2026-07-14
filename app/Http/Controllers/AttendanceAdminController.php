<?php

namespace App\Http\Controllers;

use App\Models\AttendancePunchLog;
use App\Models\AttendanceStation;
use Illuminate\Http\Request;

/**
 * The HR side of the attendance portal: where the stations live, and who
 * punched from where.
 *
 * Every route into this controller sits behind the face.registrar middleware —
 * the same Admin/HR boundary as face enrolment, because both are about the same
 * data: an employee's whereabouts and biometrics.
 */
class AttendanceAdminController extends Controller
{
    /**
     * The punch monitor. One day at a time, everyone who clocked through the
     * portal, with the location tag beside each punch.
     */
    public function monitor(Request $request)
    {
        $date = $request->query('date');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
            $date = now()->toDateString();
        }

        $logs = AttendancePunchLog::with('employee:id,fname,lname,position')
            ->whereDate('created_at', $date)
            ->orderByDesc('id')
            ->get();

        $stations = AttendanceStation::orderBy('name')->get();

        return view('attendance.monitor', [
            'guard'    => 'web',
            'date'     => $date,
            'logs'     => $logs,
            'stations' => $stations,
            'flagged'  => $logs->where('out_of_range', true)->count(),
            'unlocated'=> $logs->whereNull('lat')->count(),
        ]);
    }

    public function storeStation(Request $request)
    {
        $data = $this->validated($request);

        AttendanceStation::create($data);

        return back()->with('success', 'Station "' . $data['name'] . '" added.');
    }

    public function updateStation(Request $request, AttendanceStation $station)
    {
        $station->update($this->validated($request));

        return back()->with('success', 'Station "' . $station->name . '" updated.');
    }

    public function deleteStation(AttendanceStation $station)
    {
        // Punch logs keep their denormalised station_name, so history survives
        // the row going away.
        $station->delete();

        return back()->with('success', 'Station "' . $station->name . '" removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'lat'      => ['required', 'numeric', 'between:-90,90'],
            'lng'      => ['required', 'numeric', 'between:-180,180'],
            'radius_m' => ['required', 'integer', 'between:20,100000'],
            'active'   => ['nullable', 'boolean'],
        ]);

        $data['active'] = (bool) ($data['active'] ?? true);

        return $data;
    }
}
