@extends('layouts.app')

@php
    // System settings: one row (settings.id = 1), saved by the form at the top
    // in one go (MasterController::systemSettingUpdate). The attendance
    // stations underneath are records of their own, each saved separately
    // (AttendanceAdminController).

    $people = $employees->map(fn ($emp) => [$emp->id, ucfirst($emp->lname) . ', ' . ucfirst($emp->fname)]);

    // The three officials: field => [label, what the role does]
    $officials = [
        'mayor' => ['Mayor', 'Approves leave applications.'],
        'vice_mayor' => ['Vice Mayor', 'May approve leave when the Mayor is unavailable.'],
        'hr' => ['HR Head', 'Signs the leave form for the HR office.'],
    ];

    $card = 'rounded-2xl border border-line bg-surface p-5 sm:p-6';
    $heading = 'font-display text-lg font-semibold tracking-tight';
    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $cell = $field . ' block h-10 w-full px-3';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $rowAction = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $switch = 'relative inline-flex cursor-pointer items-center';
@endphp

@section('hero')
    <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Settings</h1>
    <p class="mt-1 max-w-2xl text-cream/70">Who signs and approves, who may see what, and where attendance is expected from.</p>
@endsection

@section('body')
<form method="POST" action="{{ route('settingsUpdate') }}" id="settingsForm" class="space-y-5">
    @csrf

    <section class="{{ $card }}">
        <h2 class="{{ $heading }}">Officials</h2>
        <p class="mt-0.5 text-ink/60">The people named on leave forms. Leave approvals need a Mayor and an HR Head to be set.</p>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            @foreach($officials as $name => [$officialLabel, $does])
                <div>
                    <label for="official-{{ $name }}" class="{{ $label }}">{{ $officialLabel }}</label>
                    <select id="official-{{ $name }}" name="{{ $name }}" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                        <option value="">Not assigned</option>
                        @foreach($people as [$id, $person])
                            <option value="{{ $id }}" @selected($settings->{$name} == $id)>{{ $person }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-ink/55">{{ $does }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }}">Time and attendance</h2>

        <div class="mt-5 max-w-sm">
            <label for="timeRestriction" class="{{ $label }}">Time entry restriction</label>
            <select id="timeRestriction" name="te_rstrct_lvl" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                <option value="0" @selected((string) $settings->te_rstrct_lvl === '0')>None</option>
                <option value="1" @selected((string) $settings->te_rstrct_lvl === '1')>Partial restriction</option>
                <option value="2" @selected((string) $settings->te_rstrct_lvl === '2')>Full restriction</option>
            </select>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            @include('partials.checklist', [
                'label' => 'HR kiosk access',
                'name' => 'hr_kiosk[]',
                // Stored by employee number, not record id.
                'options' => $employees->map(fn ($emp) => [$emp->emp_ID, ucfirst($emp->lname) . ', ' . ucfirst($emp->fname)])->all(),
                'selected' => $kioskAccess ?? [],
                'hint' => 'Employees who may operate the HR kiosk.',
            ])

            @include('partials.checklist', [
                'label' => 'DTR full access',
                'name' => 'dtr_acct[]',
                'options' => $people->all(),
                'selected' => $dtrFullAccess ?? [],
                'hint' => 'Gives these employees the HR view of the DTR, but only for their <strong class="font-semibold">own office</strong>. They can read and print the daily time record of anyone sharing their office and no one outside it. An employee with no office set keeps seeing only their own.',
            ])
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }}">Email</h2>
        {{-- The HR head has no address field here: theirs comes from the
             employee chosen as HR Head above. --}}
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div>
                <label for="recordsEmail" class="{{ $label }}">Records office email</label>
                <input type="email" id="recordsEmail" name="records_office_email" value="{{ $settings->records_office_email }}" placeholder="Enter email" class="{{ $cell }} mt-1">
            </div>
            <div>
                <label for="jobPortalEmail" class="{{ $label }}">Job portal email</label>
                <input type="email" id="jobPortalEmail" name="job_portal_email" value="{{ $settings->job_portal_email }}" placeholder="Enter email" class="{{ $cell }} mt-1">
            </div>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }}">System and kiosk</h2>

        <div class="mt-4 divide-y divide-line">
            @foreach([
                ['maintenance', 'System maintenance mode', $settings->maintenance],
                ['sync_backups', 'HR kiosk backtrack sync', $settings->sync_backups],
            ] as [$name, $switchLabel, $on])
                <div class="flex items-center justify-between gap-4 py-3">
                    <label for="switch-{{ $name }}" class="font-medium">{{ $switchLabel }}</label>
                    <span class="{{ $switch }}">
                        <input type="checkbox" id="switch-{{ $name }}" name="{{ $name }}" value="1" class="peer absolute inset-0 z-10 cursor-pointer opacity-0" @checked($on)>
                        <span class="h-6 w-11 rounded-full bg-ink/20 transition-colors peer-checked:bg-forest-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-sun-500"></span>
                        <span class="pointer-events-none absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex items-center justify-end gap-4">
        <p class="text-ink/55">Saves everything above. Stations below are saved one at a time.</p>
        <button type="submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save settings</button>
    </div>
</form>

{{-- Where face-portal punches are expected from. Punches made elsewhere still
     record; they just carry a distance flag on the Face Attendance monitor.
     Each row is a form of its own. A <form> cannot wrap table cells, so each
     row's form sits in its first cell and the inputs point at it by id. --}}
<section class="mt-8 rounded-2xl border border-line bg-surface" id="stations">
    <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-2 px-5 pt-5 sm:px-6 sm:pt-6">
        <div class="max-w-3xl">
            <h2 class="{{ $heading }}">Attendance stations</h2>
            <p class="mt-0.5 leading-relaxed text-ink/60">
                Employees can clock in from anywhere. Each punch is compared against the stations below,
                and anything outside every radius is flagged on the Face Attendance monitor.
            </p>
        </div>
        <a href="{{ route('attendanceMonitor') }}" class="font-medium text-forest-700 underline-offset-2 hover:underline">Open the punch monitor</a>
    </div>

    <div class="relative mt-4 overflow-x-auto">
        <table class="w-full min-w-[48rem] text-left">
            <thead class="border-y border-line text-xs text-ink/55">
                <tr>
                    <th scope="col" class="px-2 py-3 pl-5 font-medium sm:pl-6">Name</th>
                    <th scope="col" class="w-40 px-2 py-3 font-medium">Latitude</th>
                    <th scope="col" class="w-40 px-2 py-3 font-medium">Longitude</th>
                    <th scope="col" class="w-32 px-2 py-3 font-medium">Radius (m)</th>
                    <th scope="col" class="w-20 px-2 py-3 text-center font-medium">Active</th>
                    <th scope="col" class="w-32 px-2 py-3 pr-5 text-right font-medium sm:pr-6">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach($stations ?? [] as $station)
                    @php
                        $rowForm = 'station-edit-' . $station->id;
                    @endphp
                    <tr>
                        <td class="px-2 py-2.5 pl-5 sm:pl-6">
                            <form method="POST" action="{{ route('stationUpdate', $station->id) }}" id="{{ $rowForm }}">@csrf</form>
                            <input form="{{ $rowForm }}" type="text" name="name" value="{{ $station->name }}" aria-label="Name" required class="{{ $cell }}">
                        </td>
                        <td class="px-2 py-2.5"><input form="{{ $rowForm }}" type="number" step="any" name="lat" value="{{ $station->lat }}" aria-label="Latitude" required class="{{ $cell }}"></td>
                        <td class="px-2 py-2.5"><input form="{{ $rowForm }}" type="number" step="any" name="lng" value="{{ $station->lng }}" aria-label="Longitude" required class="{{ $cell }}"></td>
                        <td class="px-2 py-2.5"><input form="{{ $rowForm }}" type="number" name="radius_m" value="{{ $station->radius_m }}" min="20" max="100000" aria-label="Radius in metres" required class="{{ $cell }}"></td>
                        <td class="px-2 py-2.5 text-center">
                            <input form="{{ $rowForm }}" type="hidden" name="active" value="0">
                            <input form="{{ $rowForm }}" type="checkbox" name="active" value="1" aria-label="Active" class="size-4 accent-forest-600" @checked($station->active)>
                        </td>
                        <td class="px-2 py-2.5 pr-5 sm:pr-6">
                            <div class="flex justify-end gap-1">
                                <button form="{{ $rowForm }}" type="submit" title="Save changes" class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700">
                                    <i class="fas fa-save"></i><span class="sr-only">Save {{ $station->name }}</span>
                                </button>
                                <form method="POST" action="{{ route('stationDelete', $station->id) }}" data-confirm-danger
                                      data-confirm="Remove this station?" data-confirm-detail="{{ $station->name }}. Past punches keep their record." data-confirm-button="Yes, remove it">
                                    @csrf
                                    <button type="submit" title="Remove station" class="{{ $rowAction }} hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300">
                                        <i class="fas fa-trash"></i><span class="sr-only">Remove {{ $station->name }}</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach

                {{-- A new station --}}
                <tr class="bg-paper/60">
                    <td class="px-2 py-2.5 pl-5 sm:pl-6">
                        <form method="POST" action="{{ route('stationStore') }}" id="station-add-form">@csrf</form>
                        <input form="station-add-form" type="text" name="name" placeholder="New station, e.g. Municipal Hall" aria-label="Name of the new station" required class="{{ $cell }}">
                    </td>
                    <td class="px-2 py-2.5"><input form="station-add-form" type="number" step="any" name="lat" id="new-station-lat" placeholder="9.7292" aria-label="Latitude" required class="{{ $cell }}"></td>
                    <td class="px-2 py-2.5"><input form="station-add-form" type="number" step="any" name="lng" id="new-station-lng" placeholder="122.9080" aria-label="Longitude" required class="{{ $cell }}"></td>
                    <td class="px-2 py-2.5"><input form="station-add-form" type="number" name="radius_m" value="150" min="20" max="100000" aria-label="Radius in metres" required class="{{ $cell }}"></td>
                    <td class="px-2 py-2.5 text-center">
                        <input form="station-add-form" type="checkbox" name="active" value="1" aria-label="Active" class="size-4 accent-forest-600" checked>
                    </td>
                    <td class="px-2 py-2.5 pr-5 sm:pr-6">
                        <div class="flex justify-end gap-1">
                            <button type="button" id="use-my-location" title="Fill the coordinates from this device's location" class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700 disabled:cursor-wait disabled:opacity-50">
                                <i class="fas fa-location-crosshairs"></i><span class="sr-only">Use this device's location</span>
                            </button>
                            <button form="station-add-form" type="submit" class="h-9 cursor-pointer rounded-lg bg-forest-900 px-3 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">Add</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="border-t border-line px-5 py-3 text-xs text-ink/55 sm:px-6">
        The target button fills the new station's coordinates from wherever this device is, the natural way to register the building you are standing in.
    </p>
</section>
@endsection

@push('scripts')
<script>
    // Fill the new-station coordinates from wherever this browser is.
    document.getElementById('use-my-location').addEventListener('click', function () {
        var button = this;

        if (!navigator.geolocation) {
            hrisToast('error', 'This browser cannot read a location. Enter the coordinates manually.');
            return;
        }

        button.disabled = true;

        navigator.geolocation.getCurrentPosition(function (position) {
            document.getElementById('new-station-lat').value = position.coords.latitude.toFixed(7);
            document.getElementById('new-station-lng').value = position.coords.longitude.toFixed(7);
            button.disabled = false;
        }, function () {
            hrisToast('error', 'Could not read the location. Enter the coordinates manually.');
            button.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000 });
    });
</script>
@endpush
