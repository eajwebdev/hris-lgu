<?php

namespace App\Services;

use App\Models\AttendancePunchLog;
use App\Models\Dtr;
use App\Models\Employee;
use Carbon\Carbon;

/**
 * Today's punches for one employee, in the words the kiosk shows them.
 *
 * WHERE THE TIMES COME FROM, and why not simply the punch log.
 *
 * The DTR is the record of truth — it is what the employee's own DTR prints and
 * what payroll reads, and it holds every punch whatever recorded it. The
 * attendance_punch_logs table only ever sees punches made through this portal,
 * so a history built from it alone would quietly omit anything entered by other
 * means and disagree with the employee's DTR. That disagreement is the worst
 * possible outcome for a screen whose whole purpose is reassurance.
 *
 * So the DTR supplies the times, and the punch log is consulted only to name the
 * station each one happened at. A punch with no matching log still appears; it
 * just has no station beside it.
 *
 * OVERTIME IS ONE COLUMN. time_in and time_out are a pair of fields; time_over
 * is a single comma-separated list holding both ends of an overtime stretch,
 * told apart by their order in it — first is the start, second the end, and so
 * on in pairs. That is the existing shape (see AttendanceService::OVERTIME), and
 * it is why this class has to count OT entries rather than read a column name.
 */
class AttendanceHistoryService
{
    /**
     * @return array<int, array{label:string, time:string, station:?string, kind:string}>
     *         Ordered by time of day, earliest first.
     */
    public function today(Employee $employee, ?Carbon $date = null): array
    {
        $date = $date ?: Carbon::now();
        $day  = $date->toDateString();

        $dtr = Dtr::where('emp_ID', $employee->emp_ID)
            ->where('date', $day)
            ->orderByDesc('id')
            ->first();

        if (! $dtr) {
            return [];
        }

        $stations = $this->stationsByAction($employee, $day);
        $entries  = [];

        foreach ($this->split($dtr->time_in) as $i => $time) {
            $entries[] = $this->entry('login', 'Login', $time, $stations['in'][$i] ?? null);
        }

        foreach ($this->split($dtr->time_out) as $i => $time) {
            $entries[] = $this->entry('logout', 'Logout', $time, $stations['out'][$i] ?? null);
        }

        // The pairing the single column implies: even positions open an overtime
        // stretch, odd positions close the one before it.
        foreach ($this->split($dtr->time_over) as $i => $time) {
            $isStart = $i % 2 === 0;

            $entries[] = $this->entry(
                $isStart ? 'ot-in' : 'ot-out',
                $isStart ? 'Login overtime' : 'Logout overtime',
                $time,
                $stations['ot'][$i] ?? null
            );
        }

        // Sorted by the clock, not by column, so the list reads as the day
        // actually happened rather than as three separate groups.
        usort($entries, fn ($a, $b) => strcmp($a['sort'], $b['sort']));

        return array_map(function ($e) {
            unset($e['sort']);

            return $e;
        }, $entries);
    }

    /**
     * Station names for today's portal punches, grouped by action and kept in
     * time order, so the Nth time in a DTR column lines up with the Nth punch
     * of that action.
     *
     * Positional rather than matched on the timestamp itself: the DTR stores
     * H:i:s while the log carries a full created_at, and a punch that lands
     * either side of a second boundary would match nothing at all. Order is the
     * one thing both agree on.
     *
     * @return array{in: array<int, ?string>, out: array<int, ?string>, ot: array<int, ?string>}
     */
    private function stationsByAction(Employee $employee, string $day): array
    {
        $grouped = ['in' => [], 'out' => [], 'ot' => []];

        $logs = AttendancePunchLog::where('emp_ID', $employee->emp_ID)
            ->whereDate('created_at', $day)
            ->orderBy('created_at')
            ->get(['action', 'station_name']);

        foreach ($logs as $log) {
            $action = (string) $log->action;

            if (! array_key_exists($action, $grouped)) {
                continue;
            }

            $grouped[$action][] = $log->station_name;
        }

        return $grouped;
    }

    /** @return array{label:string, time:string, station:?string, kind:string, sort:string} */
    private function entry(string $kind, string $label, string $time, ?string $station): array
    {
        return [
            'kind'    => $kind,
            'label'   => $label,
            'time'    => $this->format($time),
            'station' => $station ?: null,
            'sort'    => $time,
        ];
    }

    /** "16:25:00" -> "4:25 PM". Returned as-is if it is not a time at all. */
    private function format(string $time): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', $time)->format('g:i A');
        } catch (\Throwable $e) {
            try {
                return Carbon::parse($time)->format('g:i A');
            } catch (\Throwable $e) {
                return $time;
            }
        }
    }

    /** @return array<int, string> */
    private function split(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
