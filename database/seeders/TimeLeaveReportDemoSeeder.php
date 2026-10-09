<?php

namespace Database\Seeders;

use App\Models\AttendancePunchLog;
use App\Models\AttendanceStation;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\Fdevice;
use App\Models\LeaveApplication;
use App\Models\LeaveCredit;
use App\Models\Office;
use App\Models\OfficialTime;
use App\Models\Setting;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Explicit, repeatable demo data for the Time & Leave report screens. */
class TimeLeaveReportDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing']) && !config('app.demo')) {
            throw new \RuntimeException('Report demo data requires APP_ENV=local/testing or APP_DEMO=true.');
        }

        $today = Carbon::today('Asia/Manila');
        $months = [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->startOfMonth()];

        DB::transaction(function () use ($today, $months) {
            $office = Office::firstOrCreate(['office_abbr' => 'DEMO-RPT'], [
                'office_name' => 'Demo - Report Testing', 'group_by' => 0,
            ]);
            $permanent = Status::firstOrCreate(['status_name' => 'Permanent']);
            $casual = Status::firstOrCreate(['status_name' => 'Casual']);
            $jobOrder = Status::firstOrCreate(['status_name' => 'Job Order']);

            $supervisor = $this->employee('090', 'Sam', 'DemoSupervisor', 'Demo Office Head', $office, $permanent->id);
            $hr = $this->employee('091', 'Helen', 'DemoHR', 'Demo HR Head', $office, $permanent->id, $supervisor->id);
            $mayor = $this->employee('092', 'Marco', 'DemoMayor', 'Demo Mayor', $office, $permanent->id, $supervisor->id);
            if (!$office->office_head_id) {
                $office->update(['office_head_id' => $supervisor->id]);
            }

            // Fill unassigned signatories only. Existing assignments belong to HR.
            $settings = Setting::first() ?? Setting::create([]);
            foreach (['hr' => $hr->id, 'mayor' => $mayor->id] as $role => $id) {
                if (!$settings->{$role}) {
                    $settings->{$role} = $id;
                }
            }
            $settings->save();

            $employees = collect([
                $this->employee('001', 'Alex', 'Demo', 'Demo Administrative Assistant', $office, $permanent->id, $supervisor->id),
                $this->employee('002', 'Bea', 'Demo', 'Demo Records Clerk', $office, $permanent->id, $supervisor->id),
                $this->employee('003', 'Carlo', 'Demo', 'Demo Utility Worker', $office, $casual->id, $supervisor->id),
                $this->employee('004', 'Dina', 'Demo', 'Demo Job Order Staff', $office, $jobOrder->id, $supervisor->id),
            ]);

            foreach ($employees->concat([$supervisor, $hr, $mayor]) as $employee) {
                $hours = [];
                foreach (['mon', 'tue', 'wed', 'thu', 'fri'] as $day) {
                    $hours['morn_' . $day] = '08:00-12:00';
                    $hours['aft_' . $day] = '13:00-17:00';
                }
                OfficialTime::firstOrCreate(['empid' => $employee->emp_ID], $hours);
            }

            $device = Fdevice::firstOrCreate(['device_id' => 'DEMO-REPORT-DEVICE'], ['label' => 'Demo report kiosk']);
            $station = AttendanceStation::firstOrCreate(['name' => 'Demo report station'], [
                'lat' => 9.7294, 'lng' => 122.9015, 'radius_m' => 150,
                // An audit-data fixture must not change the live kiosk's geofence.
                'active' => false,
            ]);

            foreach ($months as $month) {
                foreach ($employees as $index => $employee) {
                    $this->attendance($employee, $index, $month, $today, $device, $station);
                }
                $this->leaves($employees, $month, $supervisor, $hr, $mayor, $office);
                $this->events($employees, $month, $office);
            }

            $this->command?->table(['Employee', 'ID', 'Username'], $employees->map(fn ($employee) => [
                $employee->fname . ' ' . $employee->lname, $employee->emp_ID, $employee->username,
            ])->all());
        });

        $this->command?->info('Demo reports ready for ' . implode(' and ', array_map(fn ($month) => $month->format('F Y'), $months)) . '.');
        $this->command?->line('Pick Alex Demo (DEMO-RPT-001) for DTR, overtime, logs, tardiness, and leave PDFs.');
        $this->command?->line('Events: choose a Demo event and All employment statuses. Sign in using demo quick access or password ReportDemo123!.');
        $this->command?->line('Rerunning adds missing demo records; existing records and changes are kept.');
    }

    private function employee(string $number, string $first, string $last, string $position, Office $office, int $status, ?int $supervisor = null): Employee
    {
        return Employee::firstOrCreate(['emp_ID' => 'DEMO-RPT-' . $number], [
            'fname' => $first, 'mname' => 'Test', 'lname' => $last, 'suffix' => '', 'prefix' => '',
            'position' => $position, 'emp_status' => $status, 'emp_dept' => $office->id,
            'emp_salary' => 25000, 'supervisor' => $supervisor, 'stat_1' => 1, 'role' => 'employee',
            'username' => 'report.' . strtolower($first) . '@example.test',
            'org_email' => 'report.' . strtolower($first) . '@example.test',
            'password' => 'ReportDemo123!', 'dpn' => 1,
            'vl' => 18.5, 'sl' => 21.25, 'special_pl' => 3, 'well_leave' => 5,
        ]);
    }

    private function attendance(Employee $employee, int $index, Carbon $month, Carbon $today, Fdevice $device, AttendanceStation $station): void
    {
        $end = $month->copy()->endOfMonth()->min($today);
        for ($day = $month->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isWeekend() || ($index === 0 && in_array($day->day, [14, 15])) || ($index === 2 && $day->day % 9 === 0)) {
                continue;
            }

            $variant = ($day->day + $index) % 6;
            $ins = $variant === 1 ? ['08:17:00', '13:09:00'] : ['07:55:00', '12:55:00'];
            $outs = $variant === 2 ? ['11:40:00', '16:35:00'] : ['12:03:00', '17:06:00'];
            if ($variant === 4 && $index === 1) {
                $outs = ['12:03:00']; // Missing afternoon clock-out.
            }
            $overtime = $variant === 3 ? ['17:30:00', '19:30:00', '20:00:00', '21:00:00'] : [];
            $dtr = Dtr::firstOrCreate(['emp_ID' => $employee->emp_ID, 'date' => $day->toDateString()], [
                'time_in' => implode(',', $ins), 'time_out' => implode(',', $outs), 'time_over' => implode(',', $overtime),
                'device_id' => $device->id,
                'device_id_in' => implode(',', array_fill(0, count($ins), $device->id)),
                'device_id_out' => implode(',', array_fill(0, count($outs), $device->id)),
                'device_id_over' => implode(',', array_fill(0, count($overtime), $device->id)),
            ]);

            // Derive the audit trail from stored DTR values, including any edits on a re-run.
            foreach (['in' => $dtr->time_in, 'out' => $dtr->time_out, 'ot' => $dtr->time_over] as $action => $times) {
                foreach (array_filter(explode(',', (string) $times)) as $time) {
                    $at = $day->copy()->setTimeFromTimeString($time);
                    $flag = ($index + $day->day) % 7;
                    $unlocated = $flag === 0;
                    $far = $flag === 1;
                    AttendancePunchLog::firstOrCreate([
                        'employee_id' => $employee->id, 'action' => $action, 'created_at' => $at,
                    ], [
                        'emp_ID' => $employee->emp_ID, 'mode' => $index % 2 ? 'qr' : 'face',
                        'lat' => $unlocated ? null : ($far ? 9.735 : $station->lat),
                        'lng' => $unlocated ? null : $station->lng, 'accuracy_m' => $unlocated ? null : 12,
                        'station_id' => $unlocated ? null : $station->id,
                        'station_name' => $unlocated ? null : $station->name,
                        'distance_m' => $unlocated ? null : ($far ? 650 : 18),
                        'out_of_range' => $unlocated ? null : $far,
                        'ip_address' => '127.0.0.1', 'updated_at' => $at,
                    ]);
                }
            }
        }
    }

    private function leaves($employees, Carbon $month, Employee $supervisor, Employee $hr, Employee $mayor, Office $office): void
    {
        $adminId = User::where('role', 'Administrator')->value('id');
        foreach ($employees->take(3) as $employee) {
            LeaveCredit::firstOrCreate([
                'empid' => $employee->emp_ID, 'date' => $month->toDateString(), 'remarks' => 'DEMO report monthly accrual',
            ], ['days' => 0, 'earn_vl' => 1.25, 'earn_sl' => 1.25, 'stat' => 0, 'add_by' => $adminId]);
        }

        $cases = [
            ['APPROVED-VL', 0, 1, 4, 2, 0, 14, 2],
            ['APPROVED-SL', 1, 3, 4, 2, 0, 7, 1],
            ['DISAPPROVED', 0, 6, 3, 2, 2, 22, 1],
            ['CANCELLED', 0, 1, 4, 2, 4, 24, 1],
            ['FINAL-APPROVAL', 0, 1, 3, 1, 0, 18, 2],
            ['CASUAL-FINAL', 2, 3, 3, 1, 0, 21, 1],
            ['HR-REVIEW', 1, 15, 1, 1, 0, 26, 1],
            ['SIGNATURE', 2, 6, 1, 1, 0, 28, 1],
        ];
        foreach ($cases as [$tag, $who, $type, $status, $history, $remarks, $day, $days]) {
            $employee = $employees[$who];
            $from = $month->copy()->day($day);
            $to = $from->copy()->addDays($days - 1);
            $filed = $from->copy()->subDays(5)->setTime(9, 0);
            $leave = LeaveApplication::firstOrNew(['transnum' => 'DEMO-' . $month->format('Ym') . '-' . $tag]);
            if ($leave->exists) {
                continue;
            }
            $leave->forceFill([
                'empid' => $employee->emp_ID, 'position' => $employee->position, 'salary' => $employee->emp_salary,
                'leave_type' => $type, 'leave_purpose' => $type === 3 ? 4 : 1,
                'leave_detail' => $type === 3 ? 'DEMO: outpatient recovery' : 'DEMO: report testing only',
                'date_range' => $days > 1 ? $from->toDateString() . ' to ' . $to->toDateString() : $from->toDateString(),
                'date_filing' => $filed->toDateString(), 'days' => $days, 'commutation' => 1,
                'total_vl' => 20.5, 'total_sl' => 22.25, 'less_vl' => $type === 3 ? 0 : $days,
                'less_sl' => $type === 3 ? $days : 0, 'day_wpay' => $tag === 'FINAL-APPROVAL' ? 0.5 : 0,
                'holiday' => 0, 'as_of' => $month->toDateString(), 'emp_esign' => $tag === 'SIGNATURE' ? 1 : 2,
                'supervisor' => $supervisor->id, 'sup_prefix' => '', 'sup_sign' => $status >= 3 ? 2 : 0,
                'sup_sdate' => $status >= 3 ? $filed->copy()->addDays(2)->toDateTimeString() : null,
                'hr' => $hr->id, 'hr_prefix' => '', 'hr_sign' => $status >= 2 ? 2 : 0,
                'hr_sdate' => $status >= 2 ? $filed->copy()->addDay()->toDateTimeString() : null,
                'approver' => $mayor->id, 'approver_prefix' => '', 'approver_role' => 'Mayor',
                'approver_sign' => $status === 4 ? 2 : 0,
                'approver_sdate' => $status === 4 ? $filed->copy()->addDays(3)->toDateTimeString() : null,
                'recommend' => $remarks === 2 ? 2 : 1, 'status' => $status, 'history' => $history,
                'department' => $office->id, 'remarks_stat' => $remarks,
                'remarks_details' => $remarks === 2 ? 'DEMO: office coverage required.' : '',
                'remarks_details1' => '', 'remarks_details2' => $remarks === 4 ? 'DEMO: employee returned early.' : '',
                'created_at' => $filed, 'updated_at' => $filed,
            ])->save();
        }
    }

    private function events($employees, Carbon $month, Office $office): void
    {
        foreach ([['Orientation', 5], ['Safety Workshop', 19]] as [$name, $day]) {
            $start = $month->copy()->day($day)->setTime(8, 0);
            $event = Event::firstOrCreate(['title' => 'DEMO - ' . $name . ' (' . $month->format('F Y') . ')'], [
                'venue' => 'Demo Municipal Training Room', 'start' => $start, 'end' => $start->copy()->setTime(17, 0),
                'emp_status' => 0, 'org_dept' => $office->office_name, 'bg_color' => '#187744', 'event_stat' => 1,
            ]);
            foreach ($employees as $index => $employee) {
                EventLog::firstOrCreate(['event_id' => $event->id, 'empid' => $employee->emp_ID], [
                    'in' => $index === 3 ? null : $start->copy()->addMinutes($index * 12),
                    'out' => $index >= 2 ? null : $start->copy()->setTime(17, 0)->addMinutes($index * 5),
                    'created_at' => $start, 'updated_at' => $start->copy()->setTime(17, 0),
                ]);
            }
        }
    }
}
