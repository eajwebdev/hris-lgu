@extends('layouts.app')

@php
    // What is waiting on HR, reached from the tiles on the dashboard. Five
    // queues, chosen by $type:
    //   1  leave applications, each somewhere along its four signatures
    //   2  eligibility, 3 work experience, 4 voluntary work, 5 learning and
    //      development: employees with a PDS entry HR has yet to review
    //
    // PendingController::readPending supplies the rows for the open queue and
    // the count beside every one of them. For leave, $cat narrows the list to
    // whoever the application is waiting on; it is part of the address.

    $isLeave = $type == 1;

    // In the order they have always been listed here (learning before voluntary).
    $queues = [
        1 => ['Leave applications', 'fa-calendar-check', $leaveappCount],
        2 => ['Eligibility', 'fa-certificate', $eliCount],
        3 => ['Work experience', 'fa-briefcase', $workexpCount],
        5 => ['Learning and development', 'fa-book', $learDevCount],
        4 => ['Voluntary work', 'fa-hand-holding-heart', $volWorkCount],
    ];

    // Where an employee's entries of each kind are reviewed.
    $reviewRoutes = [2 => 'eligibility', 3 => 'work-experience', 4 => 'voluntary-work', 5 => 'learning-dev'];

    // The leave filter: the address segment => what it shows.
    $waitingOn = [
        '0' => 'All in progress',
        '0.1' => 'New, not yet reviewed',
        '0.2' => 'Waiting on the employee',
        '1' => 'Waiting on HRMO',
        '2' => 'Waiting on the supervisor',
        '3' => 'Waiting on the Mayor / Vice Mayor',
        '4' => 'Approved',
        '5' => 'Disapproved',
    ];

    // "Santos, Maria Jr. C." from the four name columns under a prefix.
    $named = fn ($row, string $who) => trim(preg_replace('/\s+/', ' ',
        $row->{$who . 'lname'} . ', ' . $row->{$who . 'fname'} . ' ' . $row->{$who . 'suffix'} . ' '
        . (filled($row->{$who . 'mname'}) ? strtoupper(substr($row->{$who . 'mname'}, 0, 1)) . '.' : '')
    ), ' ,');

    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $rowAction = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors hover:bg-forest-100 hover:text-forest-700 focus-visible:outline-2 focus-visible:outline-sun-500';
    $chip = 'inline-flex h-7 items-center gap-1.5 rounded-full px-3 text-xs font-medium whitespace-nowrap';
@endphp

@section('breadcrumb', 'Pending')

@section('hero')
    <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Pending</h1>
    <p class="mt-1 max-w-2xl text-cream/70">Leave applications on their way through the signatories, and Personal Data Sheet entries waiting for HR's review.</p>
@endsection

@section('body')
{{-- The five queues. A count is orange while something is waiting in it. --}}
<nav aria-label="Queues" class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]">
    @foreach($queues as $queue => [$queueName, $icon, $count])
        <a href="{{ route('readPending', $queue) }}" @if($type == $queue) aria-current="page" @endif
           class="group inline-flex h-11 shrink-0 items-center gap-2.5 rounded-xl border border-line bg-surface pr-2.5 pl-4 font-medium text-ink/70 transition-colors hover:border-ink/30 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 aria-[current=page]:border-forest-900 aria-[current=page]:bg-forest-900 aria-[current=page]:text-cream dark:aria-[current=page]:border-forest-600 dark:aria-[current=page]:bg-forest-600">
            <span class="text-xs opacity-60"><i class="fas {{ $icon }}"></i></span>
            {{ $queueName }}
            <span class="grid h-6 min-w-6 place-items-center rounded-full px-1.5 text-xs tabular-nums {{ $count > 0 ? 'bg-sun-500 text-forest-950' : 'bg-ink/8 text-ink/55 group-aria-[current=page]:bg-cream/15 group-aria-[current=page]:text-cream/70' }}">{{ number_format($count) }}</span>
        </a>
    @endforeach
</nav>

<section class="mt-4 rounded-2xl border border-line bg-surface" data-list>
    <div class="flex flex-wrap items-end gap-3 border-b border-line p-4">
        @if($isLeave)
            <div class="w-full sm:w-72">
                <label for="pendingFilter" class="{{ $label }}">Show</label>
                <select id="pendingFilter" data-pending-filter="{{ route('readPending', ['type' => 1, 'cat' => ':cat']) }}" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    @foreach($waitingOn as $value => $shows)
                        <option value="{{ $value }}" @selected((string) ($cat ?? '0') === (string) $value)>{{ $shows }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <label class="relative w-full sm:w-72">
            <span class="sr-only">Search</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" data-list-search placeholder="Search a name" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
        </label>

        @if($isLeave)
            {{-- The report of leave applications filed between two dates, as a
                 PDF in a new tab. The controller reads one field, "from to
                 to" (or a single day); the script below writes it from the
                 two dates. --}}
            <form action="{{ route('leaveReport') }}" method="POST" target="_blank" id="leaveReport" class="flex flex-wrap items-end gap-2 lg:ml-auto">
                @csrf
                <input type="hidden" name="date">
                <div>
                    <label for="reportFrom" class="{{ $label }}">Leave report, from</label>
                    <input type="date" id="reportFrom" required class="{{ $field }} mt-1 block h-10 px-3">
                </div>
                <div>
                    <label for="reportTo" class="{{ $label }}">To</label>
                    <input type="date" id="reportTo" class="{{ $field }} mt-1 block h-10 px-3">
                </div>
                <button type="submit" title="Open the report as a PDF" class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-line px-4 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                    <i class="fas fa-file-pdf text-xs text-ink/50"></i> PDF
                </button>
            </form>
        @endif
    </div>

    {{-- relative: keeps the visually hidden labels in the cells inside this
         scroller instead of widening the page on a phone. --}}
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    <th scope="col" class="px-4 py-3 pl-5 font-medium">Employee</th>
                    @if($isLeave)
                        <th scope="col" class="px-4 py-3 font-medium">Signatures</th>
                        <th scope="col" class="px-4 py-3 font-medium">Status</th>
                    @endif
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach($employees as $emp)
                    @if($isLeave)
                        @php
                            $filer = $named($emp, 'employee_');

                            // The four signatures, in order, and whether each
                            // is on the form yet. The employee's is the only
                            // one with a state of its own (emp_esign); the
                            // rest follow from how far the status has moved.
                            $trail = [
                                ['Employee', $filer, $emp->status > 1 || $emp->emp_esign == 2],
                                ['HRMO', $named($emp, 'hr_'), $emp->status >= 2],
                                ['Supervisor', $named($emp, 'supervisor_'), $emp->status >= 3],
                                ['Mayor / Vice Mayor', $named($emp, 'approver_'), $emp->status == 4],
                            ];
                            $refused = $emp->history != 1 && $emp->remarks_stat != 0;
                            // Just filed: HR has yet to look it over and send
                            // it back to the employee to sign, so it is not
                            // waiting on any signature yet.
                            $isNew = $emp->status == 1 && $emp->emp_esign == 0;
                            // Whose turn it is: the first signature still missing.
                            $turn = $refused || $isNew ? null : collect($trail)->search(fn ($step) => !$step[2]);
                        @endphp
                        <tr data-row data-search="{{ strtolower($filer . ' ' . $emp->transnum) }}" class="transition-colors hover:bg-paper/70">
                            <td class="px-4 py-4 pl-5 align-top">
                                <p class="font-semibold whitespace-nowrap">{{ $filer }}</p>
                                <p class="mt-0.5 text-xs whitespace-nowrap text-ink/55">Filed {{ \Carbon\Carbon::parse($emp->date_filing)->diffForHumans() }}</p>
                            </td>

                            <td class="px-4 py-4 align-top">
                                {{-- A line of four stops. Signed ones are
                                     filled in; the one the form is waiting at
                                     is ringed in orange. --}}
                                <ol class="flex min-w-[34rem]">
                                    @foreach($trail as $at => [$role, $signer, $signed])
                                        <li class="min-w-0 flex-1">
                                            <div class="flex items-center">
                                                <span class="grid size-6 shrink-0 place-items-center rounded-full text-[10px]
                                                    {{ $signed ? 'bg-forest-600 text-cream' : ($turn === $at ? 'border-2 border-sun-500 text-sun-700' : 'border border-ink/25 text-transparent') }}">
                                                    <i class="fas {{ $signed ? 'fa-check' : 'fa-pen' }}"></i>
                                                </span>
                                                @unless($loop->last)
                                                    <span class="mx-1.5 h-px flex-1 {{ $signed ? 'bg-forest-600' : 'bg-line' }}"></span>
                                                @endunless
                                            </div>
                                            <p class="mt-1.5 pr-3 text-xs {{ $turn === $at ? 'font-medium text-sun-700' : 'text-ink/55' }}">
                                                {{ $role }}<span class="sr-only">: {{ $signed ? 'signed' : ($turn === $at ? 'waiting on them' : 'not yet') }}</span>
                                            </p>
                                            <p class="truncate pr-3 text-sm {{ $signed || $turn === $at ? '' : 'text-ink/50' }}" title="{{ $signer }}">{{ $signer ?: 'Not assigned' }}</p>
                                        </li>
                                    @endforeach
                                </ol>
                            </td>

                            <td class="px-4 py-4 align-top">
                                @if($isNew)
                                    <span class="{{ $chip }} bg-sun-500 text-forest-950">New</span>
                                @elseif($emp->history == 1)
                                    <span class="{{ $chip }} bg-sun-100 text-sun-700">Ongoing</span>
                                @elseif($refused)
                                    <span class="{{ $chip }} bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">Disapproved</span>
                                @else
                                    <span class="{{ $chip }} bg-forest-100 text-forest-800">Complete</span>
                                @endif
                            </td>

                            <td class="px-4 py-4 pr-5 align-top">
                                <div class="flex justify-end gap-1">
                                    <button type="button" title="Leave form (PDF)" class="{{ $rowAction }}"
                                            data-leave-form="{{ route('previewLeave', $emp->id) }}" data-leave-name="{{ $filer }}">
                                        <i class="fas fa-file-pdf"></i><span class="sr-only">Leave form of {{ $filer }}</span>
                                    </button>
                                    <a href="{{ route('leaveStatus', $emp->employid) }}" target="_blank" title="Open this employee's leave status" class="{{ $rowAction }}">
                                        <i class="fas fa-arrow-up-right-from-square"></i><span class="sr-only">Open the leave status of {{ $filer }}</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @else
                        @php
                            $person = $named($emp, '');
                        @endphp
                        <tr data-row data-search="{{ strtolower($person . ' ' . $emp->emp_ID) }}" class="transition-colors hover:bg-paper/70">
                            <td class="px-4 py-3 pl-5">
                                <p class="font-semibold">{{ $person }}</p>
                                <p class="mt-0.5 text-xs text-ink/55">{{ $emp->position ?: 'No position set' }}</p>
                            </td>
                            <td class="px-4 py-3 pr-5 text-right">
                                <a href="{{ route($reviewRoutes[$type], $emp->id) }}" target="_blank"
                                   class="inline-flex h-9 items-center gap-2 rounded-lg px-3 font-medium text-ink/70 transition-colors hover:bg-forest-100 hover:text-forest-800 focus-visible:outline-2 focus-visible:outline-sun-500">
                                    Review <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    @if(count($employees))
        <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>Nobody here matches that.</p>
    @else
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-forest-100 text-xl text-forest-700"><i class="fas fa-check"></i></span>
            <p class="mt-4 font-medium">Nothing is waiting here.</p>
            <p class="mt-1 text-ink/55">
                {{ $isLeave ? 'No leave application matches what is being shown.' : 'Every ' . strtolower($queues[$type][0]) . ' entry has been reviewed.' }}
            </p>
        </div>
    @endif
</section>

@if($isLeave)
    {{-- The leave form, read without leaving the list. The frame gets its
         address on opening and is emptied on closing. --}}
    <dialog id="leaveFormDialog" aria-labelledby="leaveFormTitle"
            class="m-auto h-[calc(100dvh-2rem)] w-[min(64rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60 open:flex">
        <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-3">
            <h2 class="min-w-0 truncate font-display text-lg font-semibold tracking-tight" id="leaveFormTitle"></h2>
            <button type="button" data-dialog-close aria-label="Close" class="-mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500"><i class="fas fa-xmark"></i></button>
        </div>
        <iframe title="Leave form" class="min-h-0 w-full flex-1 border-0 bg-white"></iframe>
    </dialog>
@endif
@endsection

@if($isLeave)
@push('scripts')
<script>
(function () {
    // Choosing what to show goes to that address.
    var filter = document.querySelector('[data-pending-filter]');
    filter.addEventListener('change', function () {
        window.location.href = filter.dataset.pendingFilter.replace(':cat', filter.value);
    });

    // The report takes its dates as one field: a day, or "from to to".
    var report = document.getElementById('leaveReport');
    report.addEventListener('submit', function () {
        var from = document.getElementById('reportFrom').value;
        var to = document.getElementById('reportTo').value;
        report.elements.date.value = to && to !== from ? from + ' to ' + to : from;
    });

    var dialog = document.getElementById('leaveFormDialog');
    var frame = dialog.querySelector('iframe');

    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-leave-form]');
        if (!opener) return;

        dialog.querySelector('#leaveFormTitle').textContent = 'Leave form, ' + opener.dataset.leaveName;
        frame.src = opener.dataset.leaveForm;
        dialog.showModal();
    });

    dialog.addEventListener('close', function () { frame.removeAttribute('src'); });
})();
</script>
@endpush
@endif
