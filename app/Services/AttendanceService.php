<?php

namespace App\Services;

use App\Models\Dtr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Writing a punch into the DTR.
 *
 * The DTR keeps one row per employee per day, with each day's times held as a
 * comma-separated list in time_in / time_out and the zone that recorded them in
 * the matching device_id_* column. That is the existing shape and this service
 * preserves it exactly — a portal punch has to be indistinguishable from any
 * other punch to every report that already reads these columns.
 */
class AttendanceService
{
    public const CLOCK_IN  = 1;
    public const CLOCK_OUT = 2;

    /**
     * Record a punch, or explain why it was refused.
     *
     * Runs under a row lock: two devices punching the same employee in the same
     * second would otherwise both read the row, both append to what they read,
     * and one of the two times would vanish.
     */
    public function punch(string $empId, int $action, ?string $zoneId = null): array
    {
        $zoneId = (string) ($zoneId ?? config('attendance.zone_id', 0));

        $field       = $action === self::CLOCK_OUT ? 'time_out'      : 'time_in';
        $deviceField = $action === self::CLOCK_OUT ? 'device_id_out' : 'device_id_in';

        $now  = now();
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        $result = DB::transaction(function () use ($empId, $date, $time, $field, $deviceField, $zoneId, $now) {
            $record = Dtr::where('emp_ID', $empId)
                ->where('date', $date)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $record) {
                Dtr::create([
                    'emp_ID'     => $empId,
                    'date'       => $date,
                    $field       => $time,
                    $deviceField => $zoneId,
                ]);

                return ['recorded' => true, 'wait' => 0];
            }

            $times   = $this->split($record->$field);
            $devices = $this->split($record->$deviceField);

            // Cooldown against the most recent punch of *this* action. Repeatedly
            // clocking in is the mistake; clocking out right after clocking in is
            // legitimate (a half-day, a field trip) and is not blocked.
            if ($times) {
                $last    = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . end($times), $now->timezone);
                $elapsed = $last->diffInSeconds($now, false);
                $cooldown = (int) config('attendance.cooldown_seconds', 60);

                if ($elapsed >= 0 && $elapsed < $cooldown) {
                    return ['recorded' => false, 'wait' => $cooldown - $elapsed];
                }
            }

            if (in_array($time, $times, true)) {
                return ['recorded' => false, 'wait' => 0];
            }

            // Daily cap. Clock-ins and clock-outs are capped independently, each
            // at this many, because beyond a handful the extra entries are always
            // mistakes rather than real movements.
            $max = (int) config('attendance.max_punches_per_day', 5);

            if (count($times) >= $max) {
                return ['recorded' => false, 'wait' => 0, 'limit' => true];
            }

            $times[]   = $time;
            $devices[] = $zoneId;

            $record->update([
                $field       => implode(',', $times),
                $deviceField => implode(',', $devices),
            ]);

            return ['recorded' => true, 'wait' => 0];
        });

        return [
            'recorded' => $result['recorded'],
            'wait'     => $result['wait'],
            'limit'    => $result['limit'] ?? false,
            'action'   => $action === self::CLOCK_OUT ? 'CLOCK OUT' : 'CLOCK IN',
            'time'     => $now->format('g:i:s A'),
            'date'     => $now->format('l, F j, Y'),
        ];
    }

    private function split(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
