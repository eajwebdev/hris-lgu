<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('pdf_image')) {
    /**
     * Embed an image in a PDF as a data URI.
     *
     * PDF views used asset(), which hands dompdf an absolute URL — dompdf then
     * fetches every image over HTTP from the very server that is busy rendering
     * the PDF. On a single-worker server that deadlocks until the request times
     * out; everywhere else it is still a round-trip per image. Reading the file
     * straight off disk removes both problems.
     *
     * The encoded image is cached for the life of the request, so a header that
     * appears on every page is only read once.
     *
     * @param  string  $path  Path relative to public/, e.g. 'Uploads/dtr-header.png'
     */
    function pdf_image(string $path): string
    {
        static $cache = [];

        if (array_key_exists($path, $cache)) {
            return $cache[$path];
        }

        $file = public_path($path);

        if (!is_file($file)) {
            return $cache[$path] = '';
        }

        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'svg'         => 'image/svg+xml',
            default       => 'image/png',
        };

        return $cache[$path] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
    }
}

if (!function_exists('guard')) {
    function guard()
    {
        if (Auth::guard('web')->check()) {
            return 'web';
        } elseif (Auth::guard('employee')->check()) {
            return 'employee';
        }
        return null;
    }
}

if (!function_exists('shortEncrypt')) {
    function shortEncrypt($string)
    {
        $key = config('api.crypto.key', 'fA7xB93kL0pTzWmQ');
        $cipher = config('api.crypto.cipher', 'AES-128-ECB');
        return rtrim(strtr(base64_encode(openssl_encrypt($string, $cipher, $key, 0)), '+/', '-_'), '=');
    }
}

if (!function_exists('shortDecrypt')) {
    function shortDecrypt($encrypted)
    {
        $key = config('api.crypto.key', 'fA7xB93kL0pTzWmQ');
        $cipher = config('api.crypto.cipher', 'AES-128-ECB');
        $encrypted = strtr($encrypted, '-_', '+/');
        return openssl_decrypt(base64_decode($encrypted), $cipher, $key, 0);
    }
}

if (!function_exists('sidebar_menu')) {
    /**
     * The signed-in sidebar, as data.
     *
     * The menu is drawn by two shells while the app moves off AdminLTE:
     * partials/control.blade.php (AdminLTE markup) and layouts/app-sidebar
     * (Tailwind). Who sees what, and which link is lit, is decided here once so
     * the two cannot drift apart. Each renderer only chooses the markup.
     *
     * @return array<int, array{label: string, items: array<int, array<string, mixed>>}>
     */
    function sidebar_menu(string $guard): array
    {
        $user    = auth()->guard($guard)->user();
        $role    = $user->role;
        $isStaff = $role !== 'employee';           // HR / administrators
        $isAdmin = $role === 'Administrator';
        $isWeb   = $guard === 'web';
        $isFaceRegistrar = \App\Http\Middleware\EnsureFaceRegistrar::allows();

        $item = fn (string $label, string $title, string $url, string $icon, bool $active, array $children = []) => [
            'label' => $label, 'title' => $title, 'url' => $url, 'icon' => $icon,
            'active' => $active, 'children' => $children,
        ];
        $child = fn (string $label, string $url, bool $active) => ['label' => $label, 'url' => $url, 'active' => $active];
        $on = fn (string ...$patterns) => request()->is(...$patterns);

        $sections = [];

        $sections[] = ['label' => 'Main', 'items' => [
            $item('Dashboard', 'Dashboard', route('dashboard'), 'fas fa-gauge-high', $on('dashboard', 'myaccount', 'pending/*')),
        ]];

        if ($isStaff) {
            $sections[] = ['label' => 'Personnel', 'items' => [
                $item('Employees', 'Employees', route('emp_list'), 'fas fa-users', $on('employees', 'employees/*', 'tirdeness*', 'pds/*')),
                $item('Offices', 'Offices', route('officeList'), 'fas fa-building', $on('office*')),
            ]];
        }

        if ($role === 'employee') {
            $sections[] = ['label' => 'My Records', 'items' => [
                $item('PDS', 'Personal Data Sheet', route('empPDS'), 'fas fa-id-card', $on('pds', 'pds/*')),
            ]];
        }

        $time = [
            $item('DTR', 'Daily Time Record', route('dtr-read'), 'fas fa-clock', $on('dtr', 'dtr/*')),
        ];

        if ($isWeb) {
            $time[] = $item('Leave', 'Leave', route('leavesRead'), 'fas fa-calendar-check', $on('leave', 'leave/*', 'leaves*'));
        // Casual employees file leave too, so this is no longer
        // `emp_status == 1`. The eligible statuses live in config/leave.php;
        // isLeaveEligible() is the same check the dashboard panel and HR's
        // employee pickers use, so the nav link can never again disagree with
        // what the rest of the app will let the person do.
        } elseif ($user->isLeaveEligible()) {
            $time[] = $item('Leave', 'Leave', route('leavesReadEmp'), 'fas fa-calendar-check', $on('leave', 'leave/*'));
        }

        if ($isStaff) {
            $time[] = $item('Tardiness', 'Tardiness', route('readTiredness'), 'fas fa-hourglass-half', $on('tardiness*'));
        }

        if ($isFaceRegistrar) {
            $time[] = $item('Face Attendance', 'Face Attendance', route('attendanceMonitor'), 'fas fa-street-view', $on('attendance-admin*'));
            $time[] = $item('Events', 'Events', route('eventIndex'), 'fas fa-calendar-days', $on('event*'));
        }

        $sections[] = ['label' => 'Time & Leave', 'items' => $time];

        $sections[] = ['label' => 'Performance', 'items' => [
            $item('SPMS', 'SPMS', route('spms.drive'), 'fas fa-folder', $on('spms*')),
        ]];

        if ($isWeb) {
            $sections[] = ['label' => 'Recruitment', 'items' => [
                $item('Careers', 'Careers', '#', 'fas fa-briefcase', $on('career*', 'applications*', 'ete*', 'interview*'), [
                    // One entry, not two. A vacancy is published from the position
                    // it belongs to, so "Job Openings" no longer exists separately.
                    $child('Positions & Vacancies', route('positionDescriptionList'), $on('position-descriptions*', 'career')),
                    $child('Applications', route('appList'), $on('career/applications*')),
                    $child('Interview Assessment', route('interviewEvaluationList'), $on('interview*')),
                    $child('Selection Board', route('psbMembers'), $on('psb*')),
                ]),
            ]];
        }

        if ($isAdmin) {
            $sections[] = ['label' => 'Administration', 'items' => [
                $item('Users', 'Users', route('ulist'), 'fas fa-user-shield', $on('user*')),
                $item('Settings', 'Settings', route('settings'), 'fas fa-sliders', $on('settings')),
            ]];
        }

        return $sections;
    }
}

if (!function_exists('sees_job_applications')) {
    /**
     * Whether the signed-in account gets the job-application alerts in the
     * top bar.
     *
     * Administrators and HR administrators — the people the recruitment routes
     * admit — always do. An employee does when they are the HR head named in
     * Settings or sit on the Personnel Selection Board. This used to be a
     * hardcoded list of personal addresses carried over from a previous
     * deployment, so the bell never appeared for anyone.
     */
    function sees_job_applications(?string $guard): bool
    {
        if ($guard === 'web') {
            return \App\Http\Middleware\EnsureFaceRegistrar::allows();
        }

        $employeeId = auth()->guard('employee')->id();

        return (bool) $employeeId && (
            (int) optional(\App\Models\Setting::first())->hr === (int) $employeeId
            || \App\Models\PsbMember::active()->where('employee_id', $employeeId)->exists()
        );
    }
}

if (!function_exists('unread_employee_notifications')) {
    /**
     * The signed-in employee's own unread notifications, newest first.
     *
     * Controller::share hands every view the whole employee-facing list; the
     * top bar (both shells) shows only the rows addressed to this person.
     */
    function unread_employee_notifications($notifications, string $guard): \Illuminate\Support\Collection
    {
        $employee = \App\Models\Employee::find(auth()->guard($guard)->user()->id);

        if (!$employee) {
            return collect();
        }

        return collect($notifications)
            ->where('notifempid', $employee->emp_ID)
            ->where('notifstat', 0)
            ->sortByDesc('notif_created_at');
    }
}

if (!function_exists('breadcrumb_trail')) {
    /**
     * Where the current page sits in the menu, for the breadcrumb in the
     * layouts/app top bar: the lit sidebar entry, then the lit entry of its
     * submenu if it has one. Empty when no entry claims the page.
     *
     * Reading it off sidebar_menu() means a page gets a breadcrumb just by
     * being in the menu; a view adds a trailing crumb of its own (a record's
     * name, "Edit") with @section('breadcrumb', ...).
     *
     * @param  array  $menu  The result of sidebar_menu()
     * @return array<int, array{label: string, url: ?string}>
     */
    function breadcrumb_trail(array $menu): array
    {
        foreach ($menu as $section) {
            foreach ($section['items'] as $item) {
                $child = collect($item['children'])->firstWhere('active', true);

                // The submenu entry counts on its own: Careers is not lit on
                // every page its entries cover (position-descriptions, psb).
                if (!$item['active'] && !$child) {
                    continue;
                }

                // A parent that only unfolds a submenu has nowhere to link to.
                $trail = [['label' => $item['title'], 'url' => $item['children'] ? null : $item['url']]];

                if ($child) {
                    $trail[] = ['label' => $child['label'], 'url' => $child['url']];
                }

                return $trail;
            }
        }

        return [];
    }
}

if (!function_exists('event_palette')) {
    /**
     * The colours an event can be given: stored value => [name, colour].
     *
     * Events keep the Bootstrap class their colour was first picked as
     * (events.bg_color), so that is still the stored value; the calendars on
     * layouts/app draw from the hex beside it. script/masterScript carries its
     * own copy of the same six for the pages still on the old shell.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    function event_palette(): array
    {
        return [
            'bg-primary'   => ['Blue', '#007bff'],
            'bg-info'      => ['Teal', '#17a2b8'],
            'bg-warning'   => ['Yellow', '#ffc107'],
            'bg-success'   => ['Green', '#28a745'],
            'bg-danger'    => ['Red', '#dc3545'],
            'bg-secondary' => ['Grey', '#6c757d'],
        ];
    }
}
