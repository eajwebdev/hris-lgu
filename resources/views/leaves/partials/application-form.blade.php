{{--
    The employee's leave application (CS Form 6 as a screen): type of leave,
    its details, the dates.

    Field names and values are what LeaveApplicationController::LeaveAppCreate
    reads. The types that are switched off are switched off on purpose; they
    were unavailable before this screen was restyled.
--}}
@php
    // value => [label, legal basis, can be chosen]
    $types = [
        1  => ['Vacation Leave', 'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292', true],
        2  => ['Mandatory/Forced Leave', 'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292', true],
        3  => ['Sick Leave', 'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292', true],
        4  => ['Maternity Leave', 'R.A No. 11210/IRR issued by CSC, DOLE and SSS', false],
        5  => ['Paternity Leave', 'R.A No. 8187/CSC MC No. 71,s. 1998, as amended', false],
        6  => ['Special Privilege Leave', 'Sec. 21, Rule XVI, Omnibus Rules Implementing E.O No. 292', true],
        7  => ['Solo Parent Leave', 'R.A. No. 8972/CSC MC No. 8, s. 2004', false],
        15 => ['Wellness Leave', '', true],
        8  => ['Study Leave', 'Sec. 68, Rule XVI, Omnibus Rules Implementing E.O No. 292', false],
        9  => ['10-Day VAWC Leave', 'R.A No. 9262/CSC MO No. 15,s. 2005', false],
        10 => ['Rehabilitation Privilege', 'Sec. 55, Rule XVI, omnibus Rules Implementing E.O No. 292', false],
        11 => ['Special Leave Benefits for Women', 'R.A No. 9710/CSC MC No. 25,s. 2010', false],
        12 => ['Special Emergency (Calamity) Leave', 'CSC MC No. 2,s. 2012, as amended', false],
        13 => ['Adoption Leave', 'R.A. No. 8552', false],
        14 => ['Vacation Service Credit', 'R.A. No. 4670', true],
    ];

    $option = 'flex items-start gap-3 rounded-xl border border-line px-3.5 py-2.5 transition-colors has-checked:border-forest-600/50 has-checked:bg-forest-100 has-disabled:opacity-45 has-enabled:cursor-pointer has-enabled:hover:border-forest-600/40';
    $radio = 'mt-0.5 size-4 shrink-0 accent-forest-600';
    $heading = 'font-display text-lg font-semibold tracking-tight';
    $detail = 'mt-1.5 block h-9 w-full rounded-lg border border-line bg-paper px-3 text-ink outline-none transition-shadow read-only:opacity-50 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $field = 'mt-1 block h-10 w-full rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow read-only:bg-line/40 read-only:text-ink/70 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
@endphp
<form action="{{ route('LeaveAppCreate') }}" method="POST" id="leaveApplication" class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
    @csrf
    <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">

    <h2 class="{{ $heading }}">Type of leave to be availed of</h2>
    <div class="mt-3 grid gap-2 md:grid-cols-2">
        @foreach($types as $value => [$typeLabel, $basis, $available])
            <label class="{{ $option }}">
                <input type="radio" name="leave_type" value="{{ $value }}" required @disabled(!$available) class="{{ $radio }}">
                <span>
                    <span class="font-medium">{{ $typeLabel }}</span>
                    @if($basis)
                        <span class="block text-xs text-ink/55">{{ $basis }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>

    {{-- Each group below opens for the leave type it belongs to; the script
         at the bottom does the switching. --}}
    <h2 class="{{ $heading }} mt-8">Details of leave</h2>
    <div class="mt-3 grid gap-x-6 gap-y-5 md:grid-cols-2">
        <fieldset data-details-for="1">
            <legend class="text-xs font-medium text-ink/60">In case of Vacation / Special Privilege Leave</legend>
            <div class="mt-2 space-y-2">
                <label class="{{ $option }}">
                    <input type="radio" name="leave_purpose" value="1" required disabled class="{{ $radio }}">
                    <span class="font-medium">Within the Philippines</span>
                </label>
                <label class="{{ $option }}">
                    <input type="radio" name="leave_purpose" value="2" required disabled class="{{ $radio }}">
                    <span class="min-w-0 flex-1">
                        <span class="font-medium">Abroad (Specify)</span>
                        <input type="text" name="leave_detail[]" autocomplete="off" readonly aria-label="Where abroad" class="{{ $detail }}">
                    </span>
                </label>
            </div>
        </fieldset>

        <fieldset data-details-for="8">
            <legend class="text-xs font-medium text-ink/60">In case of Study Leave</legend>
            <div class="mt-2 space-y-2">
                <label class="{{ $option }}">
                    <input type="radio" name="leave_purpose" value="5" required disabled class="{{ $radio }}">
                    <span class="font-medium">Completion of Master's Degree</span>
                </label>
                <label class="{{ $option }}">
                    <input type="radio" name="leave_purpose" value="6" required disabled class="{{ $radio }}">
                    <span class="min-w-0 flex-1">
                        <span class="font-medium">BAR/Board Examination Review</span>
                        <input type="text" name="leave_detail[]" autocomplete="off" readonly aria-label="Which examination" class="{{ $detail }}">
                    </span>
                </label>
            </div>
        </fieldset>

        <fieldset data-details-for="3">
            <legend class="text-xs font-medium text-ink/60">In case of Sick Leave</legend>
            <div class="mt-2 space-y-2">
                <label class="{{ $option }}">
                    <input type="radio" name="leave_purpose" value="3" required disabled class="{{ $radio }}">
                    <span class="font-medium">In Hospital (Specify Illness)</span>
                </label>
                <label class="{{ $option }}">
                    <input type="radio" name="leave_purpose" value="4" required disabled class="{{ $radio }}">
                    <span class="min-w-0 flex-1">
                        <span class="font-medium">Out Patient (Specify Illness)</span>
                        <input type="text" name="leave_detail[]" autocomplete="off" readonly aria-label="Illness" class="{{ $detail }}">
                    </span>
                </label>
            </div>
        </fieldset>

        <fieldset data-details-other>
            <legend class="text-xs font-medium text-ink/60">Other purpose</legend>
            {{-- Stands for "no purpose given": checked whenever this group is
                 open and neither of its two options is. --}}
            <input type="radio" name="leave_purpose" value="" checked hidden data-purpose-none>
            <div class="mt-2 space-y-2">
                <label class="{{ $option }}">
                    <input type="radio" name="leave_purpose" value="7" disabled class="{{ $radio }}">
                    <span class="font-medium">Monetization of Leave Credits</span>
                </label>
                <label class="{{ $option }}">
                    <input type="radio" name="leave_purpose" value="8" disabled class="{{ $radio }}">
                    <span class="min-w-0 flex-1">
                        <span class="font-medium">Terminal Leave</span>
                        <input type="text" name="leave_detail[]" autocomplete="off" readonly aria-label="Terminal leave details" class="{{ $detail }}">
                    </span>
                </label>
            </div>
        </fieldset>
    </div>

    <h2 class="{{ $heading }} mt-8">Inclusive dates</h2>
    {{-- Posted as one field, "2026-10-05 to 2026-10-09" (or a single date for
         one day), which is what the rest of the leave module reads. --}}
    <input type="hidden" name="date_range" id="date_range">
    <div class="mt-3 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div>
            <label for="leaveFrom" class="{{ $label }}">From</label>
            <input type="date" id="leaveFrom" required min="{{ \Carbon\Carbon::now()->toDateString() }}" class="{{ $field }}">
        </div>
        <div>
            <label for="leaveTo" class="{{ $label }}">To</label>
            <input type="date" id="leaveTo" required min="{{ \Carbon\Carbon::now()->toDateString() }}" class="{{ $field }}">
        </div>
        <div>
            <label for="day" class="{{ $label }}">Days applied</label>
            <input type="text" id="day" name="days" autocomplete="off" readonly class="{{ $field }}">
        </div>
        <div>
            <label for="leaveFiling" class="{{ $label }}">Date of filing</label>
            <input type="date" id="leaveFiling" name="date_filing" value="{{ \Carbon\Carbon::now()->toDateString() }}" readonly class="{{ $field }}">
        </div>
    </div>
    <p class="mt-2 text-xs text-ink/55">Days applied counts Monday to Friday only.</p>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="h-10 cursor-pointer rounded-xl bg-forest-900 px-6 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">Submit</button>
    </div>
</form>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('leaveApplication');
    var types = Array.prototype.slice.call(form.querySelectorAll('input[name="leave_type"]'));
    var details = Array.prototype.slice.call(form.querySelectorAll('input[name="leave_detail[]"]'));
    var other = form.querySelector('[data-details-other]');
    var none = form.querySelector('[data-purpose-none]');

    /* ------------------------------------------------ details by leave type */
    // Opens or shuts one group: its radios, and the text box beside them.
    function setGroup(group, open) {
        group.querySelectorAll('input[type="radio"]:not([data-purpose-none])').forEach(function (radio) {
            radio.disabled = !open;
            if (!open) { radio.checked = false; }
        });
        group.querySelectorAll('input[type="text"]').forEach(function (text) {
            text.readOnly = !open;
            if (!open) { text.value = ''; }
        });
    }

    types.forEach(function (type) {
        type.addEventListener('change', function () {
            var chosen = type.value;

            // Vacation, Sick and Study each have their own pair of details...
            form.querySelectorAll('[data-details-for]').forEach(function (group) {
                setGroup(group, group.dataset.detailsFor === chosen);
            });

            // ...and every other type gets "Other purpose" instead, which
            // starts on "none".
            var usesOther = ['1', '3', '8'].indexOf(chosen) === -1;
            setGroup(other, usesOther);
            none.checked = usesOther;

            // Any type lifts the "not before today" limit on the dates and
            // clears them, as the range picker here did.
            from.removeAttribute('min');
            from.value = '';
            to.value = '';
            dates();
        });
    });

    // Only one of the four detail boxes is ever sent filled in.
    details.forEach(function (box) {
        box.addEventListener('input', function () {
            details.forEach(function (otherBox) { if (otherBox !== box) { otherBox.value = ''; } });
        });
    });

    /* --------------------------------------------------------------- dates */
    var from = document.getElementById('leaveFrom');
    var to = document.getElementById('leaveTo');
    var range = document.getElementById('date_range');
    var days = document.getElementById('day');

    // Working days in the range, both ends included.
    function weekdays(start, end) {
        var count = 0;
        for (var day = new Date(start + 'T00:00:00'), last = new Date(end + 'T00:00:00'); day <= last; day.setDate(day.getDate() + 1)) {
            if (day.getDay() !== 0 && day.getDay() !== 6) { count++; }
        }
        return count;
    }

    function dates() {
        if (from.value) { to.min = from.value; } else { to.min = from.min; }
        if (from.value && to.value && to.value < from.value) { to.value = ''; }

        if (from.value && to.value) {
            range.value = from.value === to.value ? from.value : from.value + ' to ' + to.value;
            days.value = weekdays(from.value, to.value);
        } else {
            range.value = '';
            days.value = '';
        }
    }

    from.addEventListener('change', dates);
    to.addEventListener('change', dates);
    dates();
})();
</script>
@endpush
