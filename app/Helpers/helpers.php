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

if (!function_exists('privacy_notice')) {
    /**
     * The Data Privacy Notice, as content.
     *
     * The notice is drawn in three places that cannot share markup: the
     * dialogs on layouts/app (Tailwind), the dialogs on layouts/master, and
     * the PDF (data-privacy.blade.php, inline styles for dompdf). The wording
     * is kept here once so the three cannot drift; each renderer only decides
     * how it looks.
     *
     * Text may carry <strong> and <a>; it is authored here, never user input.
     * Each section is a heading, an optional opening paragraph, and optional
     * bullet points.
     */
    function privacy_notice(): array
    {
        return [
            'title' => 'LGU MABINAY HRIS DATA PRIVACY NOTICE',
            'basis' => 'In Compliance with Republic Act No. 10173 (Data Privacy Act of 2012)',
            'intro' => 'The <strong>Local Government Unit of Mabinay (Municipality of Mabinay)</strong>, as the Personal Information Controller (PIC), together with <strong>EAJ Web Development Services</strong> as the System Developer and Technical Service Provider, are committed to protecting and respecting your personal data privacy in full compliance with <strong><a href="https://privacy.gov.ph/data-privacy-act/" target="_blank">Republic Act No. 10173</a> (Data Privacy Act of 2012)</strong>, its Implementing Rules and Regulations (IRR), and guidelines issued by the National Privacy Commission (NPC).',
            'sections' => [
                [
                    'heading' => 'Scope &amp; Categories of Data Collected',
                    'lead' => 'To provide an integrated Human Resource Information System (HRIS), daily timekeeping, and strategic performance evaluation, the system collects and processes the following information:',
                    'items' => [
                        '<strong>Personal Identifiers:</strong> Full name, employee ID number, birth date, gender, civil status, contact information, residential address, and department assignment.',
                        '<strong>Employment &amp; Career Records:</strong> Personal Data Sheet (PDS / CS Form 212), position title, employment status (Regular, Permanent, Casual, JO, COS), appointment details, and service history.',
                        '<strong>Time &amp; Attendance Logs:</strong> Biometric facial recognition data, digital time logs, Daily Time Records (DTR), leave applications, travel orders, and pass slips.',
                        '<strong>Strategic Performance Records (SPMS):</strong> Office Performance Commitment &amp; Review (OPCR), Individual Performance Commitment &amp; Review (IPCR), target metrics, actual accomplishments, and evaluation ratings.',
                    ],
                ],
                [
                    'heading' => 'Purpose of Collection &amp; Processing',
                    'lead' => 'All personal data collected through the system is processed strictly for legitimate municipal government operations and administration, including:',
                    'items' => [
                        'Maintaining accurate digital personnel profiles and Personal Data Sheets (PDS) in the HRIS portal.',
                        'Tracking daily attendance, processing official leave requests, and generating DTR logs for payroll.',
                        'Evaluating staff performance under the Civil Service Commission (CSC) Strategic Performance Management System (SPMS).',
                        'Complying with statutory reporting requirements enforced by the Civil Service Commission (CSC), Commission on Audit (COA), GSIS, and Pag-IBIG.',
                    ],
                ],
                [
                    'heading' => 'System Developer &amp; Technical Security Controls',
                    'lead' => 'The software architecture, database design, and technical maintenance of the LGU Mabinay HRIS system are engineered and managed by <strong>EAJ Web Development Services</strong>. Technical security measures enforced include:',
                    'items' => [
                        '<strong>Role-Based Access Control:</strong> Strict permissions limiting access to authorized personnel, office heads, and HR administrators.',
                        '<strong>Data Security &amp; Encryption:</strong> Encrypted password storage, HTTPS encrypted data transmission, and protected database backups.',
                        '<strong>System Integrity:</strong> Continuous maintenance, software optimization, and privacy-by-design standards implemented by <strong>EAJ Web Development Services</strong>.',
                    ],
                ],
                [
                    'heading' => 'Data Retention &amp; Custody',
                    'lead' => 'All physical and electronic records are held under the primary custody of the <strong>Human Resource Management Office (HRMO)</strong> of the Municipality of Mabinay. Data is retained only for as long as necessary to fulfill statutory duties and government audit requirements.',
                    'items' => [],
                ],
                [
                    'heading' => 'Rights of Data Subjects',
                    'lead' => 'Under Republic Act No. 10173, employees and data subjects have the right to be informed, to access their personal records, to request correction of inaccuracies, and to lodge inquiries regarding their data processing with the HRMO.',
                    'items' => [],
                ],
                [
                    'heading' => 'Consent &amp; Acknowledgment',
                    'lead' => 'By accessing or submitting information through the LGU Mabinay HRIS Portal, you acknowledge that you have read this notice and voluntarily consent to the collection, processing, and storage of your personal data by the Municipality of Mabinay HRMO and technical management by <strong>EAJ Web Development Services</strong>.',
                    'items' => [],
                ],
            ],
            'issuer' => 'Municipality of Mabinay &bull; Human Resource Management Office (HRMO)',
            'developer' => 'EAJ Web Development Services',
        ];
    }
}

if (!function_exists('pds_sections')) {
    /**
     * The pages of one employee's Personal Data Sheet, as data.
     *
     * The list is drawn by two shells while the PDS moves off AdminLTE:
     * emp/submenu-side (Bootstrap) and emp/partials/pds-side (Tailwind). Where
     * each entry goes, which one is open and which sections have been filled
     * in is decided here once; each renderer only chooses the markup.
     *
     * HR opens a named employee's record and an employee opens their own, so
     * the same entry has two addresses.
     *
     * `done` is null for an entry that has nothing to fill in. `group` is
     * 'form' for the sections of CS Form 212, 'print' for the two PDFs and
     * 'identity' for what the employee is recognised by.
     *
     * @param  array|null  $columnstatus  EmployeeController::columnStat(); the
     *                                    signature and face pages do not load it
     * @return array<int, array{label: string, url: string, icon: string, active: bool, done: ?bool, group: string, newTab: bool}>
     */
    function pds_sections($employee, ?string $guard, ?array $columnstatus = null): array
    {
        $url = fn (string $route) => $guard === 'web' ? route($route, $employee->id) : route($route);
        $on = fn (string ...$patterns) => request()->is(...$patterns);

        $flag = fn (string $key) => ($columnstatus[$key] ?? 0) == 1;
        $any = fn (string $key) => isset($columnstatus[$key]) && count($columnstatus[$key]) > 0;

        $entry = fn (string $label, string $url, string $icon, bool $active, ?bool $done, string $group = 'form') => [
            'label' => $label, 'url' => $url, 'icon' => $icon, 'active' => $active,
            'done' => $done, 'group' => $group, 'newTab' => $group === 'print',
        ];

        $sections = [
            // Always counted as filled: the record cannot exist without it.
            $entry('Personal Information', $guard === 'web' ? route('PDS', $employee->id) : route('empPDS'), 'fas fa-user', $on('pds', 'pds/personal-info/*'), true),
            $entry('Family Background', $url('familybg'), 'fas fa-users', $on('pds/family-bg*'), $flag('colfamstat')),
            $entry('Educational Background', $url('educbg'), 'fas fa-graduation-cap', $on('pds/educ-bg*'), $flag('coleducstat')),
            $entry('Eligibility', $url('eligibility'), 'fas fa-certificate', $on('pds/eligibility*'), $any('eligibility')),
            $entry('Work Experience', $url('work-experience'), 'fas fa-briefcase', $on('pds/work-experience*'), $any('workexperience')),
            $entry('Voluntary Work', $url('voluntary-work'), 'fas fa-hand-holding-heart', $on('pds/voluntary-work*'), $any('voluntaryworks')),
            $entry('Learning and Development', $url('learning-dev'), 'fas fa-book', $on('pds/learning-dev*'), $any('learningdev')),
            $entry('Other Information', $url('otherInfo'), 'fas fa-info-circle', $on('pds/other-info*'), $flag('colotherinfo')),
            $entry('Other Information Questions', $url('infoQuestion'), 'fas fa-question-circle', $on('pds/info-question*'), $flag('colinfoquestion')),
            $entry('References', $url('references'), 'fas fa-address-book', $on('pds/references*'), $flag('colreferences')),
            $entry('Government Issued ID', $url('govids'), 'fas fa-id-card', $on('pds/government-id*'), $flag('colgovids')),

            $entry('Preview Personal Data Sheet', $url('generatepds'), 'fas fa-eye', false, null, 'print'),
            $entry('Attachment to CS Form No. 212', $url('genpdsAtthachment'), 'fas fa-eye', false, null, 'print'),

            $entry('E-Signature', $url('signature'), 'fas fa-signature', $on('pds/signature*'), null, 'identity'),
        ];

        // Registrars only. The PDS is the HR-facing record, so enrolling a
        // face from here belongs to Admin and HR; an employee reaches their
        // own enrolment from the dashboard. This only hides the link: the
        // route runs on 'face.self', so an employee opening their own page
        // directly is allowed and one naming somebody else's id gets a 403.
        if (\App\Http\Middleware\EnsureFaceRegistrar::allows()) {
            $sections[] = $entry('Face Recognition', $url('faceRecognition'), 'fas fa-user-shield', $on('pds/face-recognition*'), (bool) $employee->faceSummary()['registered'], 'identity');
        }

        return $sections;
    }
}

if (!function_exists('leave_type_names')) {
    /**
     * The kinds of leave, by the number stored in leave_applications.leave_type.
     *
     * @return array<int, string>
     */
    function leave_type_names(): array
    {
        return [
            1 => 'Vacation Leave',
            2 => 'Mandatory/Forced Leave',
            3 => 'Sick Leave',
            4 => 'Maternity Leave',
            5 => 'Paternity Leave',
            6 => 'Special Privilege Leave',
            7 => 'Solo Parent Leave',
            8 => 'Study Leave',
            9 => '10-Day VAWC Leave',
            10 => 'Rehabilitation Privilege',
            11 => 'Special Leave Benefits for Women',
            12 => 'Special Emergency (Calamity) Leave',
            13 => 'Adoption Leave',
            14 => 'Vacation Service Credit',
            15 => 'Wellness Leave',
        ];
    }
}

if (!function_exists('leave_other_balances')) {
    /**
     * The leave balances kept beside Vacation and Sick Leave, for the balances
     * panel on the three leave screens and HR's dialog that sets them:
     * employees column => [label, id of the figure in the panel].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    function leave_other_balances(): array
    {
        return [
            'special_pl'     => ['Special Privilege Leave', 'special-pl'],
            'solo_pl'        => ['Solo Parent Leave', 'solo-pl'],
            'study_leave'    => ['Study Leave', 'study-leave'],
            'vawc_leave'     => ['10-Day VAWC Leave', 'vawc-leave'],
            'rehab_leave'    => ['Rehabilitation Privilege', 'rehab-leave'],
            'benefits_leave' => ['Special Leave Benefits for Women', 'benefits-leave'],
            'calamity_leave' => ['Special Emergency (Calamity) Leave', 'calamity-leave'],
            'adopt_leave'    => ['Adoption Leave', 'adopt-leave'],
            'servcred_leave' => ['Vacation Service Credit', 'servcred-leave'],
            'well_leave'     => ['Wellness Leave', 'wellness-leave'],
        ];
    }
}
