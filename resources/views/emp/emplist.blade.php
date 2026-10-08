@extends('layouts.app')

@php
    $activeCount = $employee->where('stat_1', 1)->count();
    $statusNames = $employee->pluck('status_name')->filter()->unique()->sort()->values();

    $bannerButton = 'inline-flex h-10 items-center gap-2 rounded-xl border px-4 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    // Height and padding are added where a field is used; two of each on one
    // element would leave the winner to stylesheet order.
    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $rowAction = 'grid size-9 place-items-center rounded-lg text-ink/55 transition-colors hover:bg-forest-100 hover:text-forest-700 focus-visible:outline-2 focus-visible:outline-sun-500';
    $dialog = 'm-auto max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Employees</h1>
            <p class="mt-1 text-cream/70">
                {{ number_format($employee->count()) }} on record
                <span class="mx-1.5 text-cream/30">|</span>
                {{ number_format($activeCount) }} with an active account
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('empQr') }}" target="_blank" class="{{ $bannerButton }} border-cream/25 hover:border-cream hover:bg-cream hover:text-forest-900">
                <i class="fas fa-qrcode"></i> QR codes
            </a>
            <a href="{{ route('genEmp') }}" target="_blank" class="{{ $bannerButton }} border-cream/25 hover:border-cream hover:bg-cream hover:text-forest-900">
                <i class="fas fa-file-pdf"></i> List as PDF
            </a>
            <a href="{{ route('empAdd') }}" class="{{ $bannerButton }} border-cream bg-cream text-forest-900 hover:bg-white">
                <i class="fas fa-user-plus"></i> Add new
            </a>
        </div>
    </div>
@endsection

@section('body')
{{-- Every employee is in the page; the shell's list script (data-list, in
     layouts/app-scripts) searches, sorts and pages them. --}}
<section class="rounded-2xl border border-line bg-surface" id="employeeList" data-list data-list-sort-by="name">
    <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
        <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
            <span class="sr-only">Search employees</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" data-list-search placeholder="Search name, ID, position, email" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
        </label>

        <label>
            <span class="sr-only">Employment status</span>
            <select data-list-filter="status" class="{{ $field }} h-10 pr-8 pl-3">
                <option value="">All statuses</option>
                @foreach($statusNames as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="sr-only">Account</span>
            <select data-list-filter="active" class="{{ $field }} h-10 pr-8 pl-3">
                <option value="">All accounts</option>
                <option value="1">Active</option>
                <option value="0">Disabled</option>
            </select>
        </label>

        <p class="ml-auto text-ink/55" data-list-count aria-live="polite"></p>
    </div>

    {{-- relative: the visually hidden labels in the cells are absolutely
         positioned, and without a positioned ancestor in here they escape
         this scroller and widen the whole page on a phone. --}}
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    @foreach(['name' => 'Employee', 'id' => 'Employee ID', 'status' => 'Status', 'email' => 'Email', 'hired' => 'Date hired'] as $key => $heading)
                        <th scope="col" class="px-4 py-3 font-medium first:pl-5 {{ $key === 'email' ? 'max-lg:hidden' : '' }} {{ $key === 'hired' ? 'max-md:hidden' : '' }} {{ $key === 'status' ? 'max-sm:hidden' : '' }}">
                            <button type="button" data-list-sort="{{ $key }}" class="-mx-1.5 inline-flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 transition-colors hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                                {{ $heading }} <i class="fas fa-sort text-[10px] opacity-50"></i>
                            </button>
                        </th>
                    @endforeach
                    <th scope="col" class="px-4 py-3 font-medium">Account</th>
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-line">
                @foreach ($employee as $emp)
                    @php
                        $fullName = trim($emp->lname . ', ' . $emp->fname . ' ' . (isset($emp->mname) ? strtoupper(substr($emp->mname, 0, 1)) . '.' : ''));
                        $spokenName = trim($emp->fname . ' ' . $emp->mname . ' ' . $emp->lname);
                        $hired = $emp->date_hired ? \Carbon\Carbon::parse($emp->date_hired) : null;
                        $service = $hired ? $hired->diff(now()) : null;
                    @endphp
                    <tr id="tr-{{ $emp->id }}" data-row
                        data-search="{{ strtolower($fullName . ' ' . $emp->emp_ID . ' ' . $emp->position . ' ' . $emp->org_email . ' ' . $emp->status_name) }}"
                        data-name="{{ strtolower($fullName) }}" data-id="{{ strtolower($emp->emp_ID) }}"
                        data-status="{{ $emp->status_name }}" data-email="{{ strtolower($emp->org_email) }}"
                        data-hired="{{ $hired?->toDateString() }}" data-active="{{ $emp->stat_1 == 1 ? 1 : 0 }}"
                        class="transition-colors hover:bg-paper/70">
                        <td class="px-4 py-3 pl-5">
                            <p class="font-semibold">{{ $fullName }}</p>
                            <p class="mt-0.5 text-xs text-ink/55">{{ $emp->position ?: 'No position set' }}</p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap tabular-nums">{{ $emp->emp_ID }}</td>
                        <td class="px-4 py-3 whitespace-nowrap max-sm:hidden">{{ $emp->status_name ?: '--' }}</td>
                        <td class="px-4 py-3 text-ink/75 max-lg:hidden">{{ $emp->org_email ?: '--' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap max-md:hidden">
                            @if($hired)
                                <p>{{ $hired->format('F d, Y') }}</p>
                                <p class="mt-0.5 text-xs text-ink/55">{{ $service->y }} {{ $service->y == 1 ? 'year' : 'years' }} {{ $service->m }} {{ $service->m == 1 ? 'month' : 'months' }}</p>
                            @else
                                <span class="text-ink/40">Not set</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            {{-- Flipping this only asks; the account changes when
                                 the dialog below is confirmed. --}}
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" class="peer sr-only" data-account-switch
                                       data-employee-id="{{ $emp->id }}" data-employee-name="{{ $spokenName }}"
                                       aria-label="Account active: {{ $spokenName }}"
                                       {{ $emp->stat_1 == 1 ? 'checked' : '' }}>
                                <span class="h-6 w-11 rounded-full bg-ink/20 transition-colors peer-checked:bg-forest-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-sun-500"></span>
                                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                            </label>
                        </td>
                        <td class="px-4 py-3 pr-5">
                            <div class="flex justify-end gap-1">
                                @if($emp->emp_status == 1)
                                    <a href="{{ route('leavesRead', $emp->id) }}" title="Leave credits" class="{{ $rowAction }}">
                                        <i class="fas fa-calendar-check"></i><span class="sr-only">Leave credits</span>
                                    </a>
                                @else
                                    <span title="Leave credits are unavailable for this employee" aria-disabled="true"
                                          class="grid size-9 cursor-not-allowed place-items-center rounded-lg text-ink/25">
                                        <i class="fas fa-calendar-check"></i>
                                    </span>
                                @endif

                                <a href="{{ route('PDS', $emp->id) }}" title="Personal Data Sheet" class="{{ $rowAction }}">
                                    <i class="fas fa-file-lines"></i><span class="sr-only">Personal Data Sheet</span>
                                </a>

                                <button type="button" title="Working hours" data-hours-for="{{ $emp->emp_ID }}" data-employee-name="{{ $spokenName }}" class="{{ $rowAction }} cursor-pointer">
                                    <i class="fas fa-clock"></i><span class="sr-only">Working hours</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No employee matches that.</p>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
        <label class="flex items-center gap-2 text-ink/55">
            Rows
            <select data-list-size class="{{ $field }} h-9 pr-7 pl-3">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="0">All</option>
            </select>
        </label>

        <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
    </div>
</section>

{{-- Official working hours for one employee. Filled from OfficialTimeRead when
     it opens; saving is an ordinary form post. --}}
<dialog id="hoursDialog" aria-labelledby="hoursTitle" class="{{ $dialog }} w-[min(40rem,calc(100vw-2rem))]">
    <form action="{{ route('OfficialTimeCreate') }}" method="POST">
        @csrf
        <input type="hidden" name="empid">

        <div class="flex items-start justify-between gap-4 px-6 pt-5">
            <div class="min-w-0">
                <h2 class="font-display text-xl font-semibold tracking-tight" id="hoursTitle">Official working hours</h2>
                <p class="mt-0.5 truncate text-ink/60" data-hours-name></p>
            </div>
            <button type="button" data-dialog-close aria-label="Close" class="-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <p class="mx-6 mt-4 rounded-xl bg-red-50 px-4 py-3 text-red-800 dark:bg-red-500/10 dark:text-red-300" data-hours-error hidden></p>

        <div class="grid grid-cols-[auto_repeat(4,minmax(0,1fr))] items-center gap-x-2 gap-y-2 px-6 pt-4 pb-1" data-hours-grid>
            <span></span>
            @foreach(['Morning in', 'Morning out', 'Afternoon in', 'Afternoon out'] as $slot)
                <span class="text-xs font-medium text-ink/55">{{ $slot }}</span>
            @endforeach

            @foreach(['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri'] as $day => $dayLabel)
                <span class="pr-2 font-medium">{{ $dayLabel }}</span>
                @foreach(['mornin' => 'morning in', 'mornout' => 'morning out', 'noonin' => 'afternoon in', 'noonout' => 'afternoon out'] as $part => $partLabel)
                    <input type="time" name="{{ $day }}_{{ $part }}" aria-label="{{ $dayLabel }} {{ $partLabel }}" required class="{{ $field }} h-10 w-full min-w-0 px-2">
                @endforeach
            @endforeach
        </div>

        <div class="flex justify-end gap-2 px-6 py-5">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </form>
</dialog>

{{-- Confirmation for the account switch. --}}
<dialog id="accountDialog" aria-labelledby="accountTitle" class="{{ $dialog }} w-[min(26rem,calc(100vw-2rem))]">
    <div class="p-6">
        <h2 class="font-display text-xl font-semibold tracking-tight" id="accountTitle">Confirm action</h2>
        <p class="mt-2 leading-relaxed text-ink/70">
            Are you sure you want to <strong class="font-semibold text-ink" data-account-action></strong> this employee's account?
        </p>
        <p class="mt-3 text-base font-semibold" data-account-name></p>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="button" id="accountConfirm" class="{{ $primary }}">Confirm</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var list = document.getElementById('employeeList');

    /* ------------------------------------------------------ account switch */
    // The switch is put back the moment it is flipped; it only really moves
    // once the dialog is confirmed and the server has agreed.
    var accountDialog = document.getElementById('accountDialog');
    var pending = null;

    list.addEventListener('change', function (event) {
        var box = event.target.closest('[data-account-switch]');
        if (!box) return;

        pending = { box: box, enable: box.checked };
        box.checked = !pending.enable;

        accountDialog.querySelector('[data-account-action]').textContent = pending.enable ? 'enable' : 'disable';
        accountDialog.querySelector('[data-account-name]').textContent = box.dataset.employeeName;
        accountDialog.showModal();
    });

    document.getElementById('accountConfirm').addEventListener('click', function () {
        var change = pending;
        pending = null;
        accountDialog.close();
        if (!change) return;

        fetch("{{ route('toggleAcctStat') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new URLSearchParams({ id: change.box.dataset.employeeId, stat_1: change.enable ? 1 : 2 })
        })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function (data) {
                if (!data.success) { return Promise.reject(); }
                change.box.checked = change.enable;
                change.box.closest('[data-row]').dataset.active = change.enable ? '1' : '0';
            })
            .catch(function () { hrisToast('error', 'The account could not be changed. Please try again.'); });
    });

    /* ------------------------------------------------------ working hours */
    var hoursDialog = document.getElementById('hoursDialog');
    var hoursError = hoursDialog.querySelector('[data-hours-error]');
    var hoursFields = Array.prototype.slice.call(hoursDialog.querySelectorAll('input[type="time"]'));
    var hoursUrl = "{{ route('OfficialTimeRead', ['empid' => ':empid']) }}";

    list.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-hours-for]');
        if (!opener) return;

        var empId = opener.dataset.hoursFor;
        hoursDialog.querySelector('input[name="empid"]').value = empId;
        hoursDialog.querySelector('[data-hours-name]').textContent = opener.dataset.employeeName;
        hoursFields.forEach(function (field) { field.value = ''; });
        hoursError.hidden = true;
        hoursDialog.showModal();

        fetch(hoursUrl.replace(':empid', encodeURIComponent(empId)), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function (response) {
                if (!response.success) { return Promise.reject(); }
                // Someone may have opened another employee's hours meanwhile.
                if (hoursDialog.querySelector('input[name="empid"]').value !== empId) return;

                hoursFields.forEach(function (field) {
                    // Stored with seconds; the field reads better without them.
                    field.value = String(response.data[field.name] || '').slice(0, 5);
                });
            })
            .catch(function () {
                hoursError.textContent = 'The saved hours could not be loaded. Close this and try again before changing anything.';
                hoursError.hidden = false;
            });
    });
})();
</script>
@endpush
