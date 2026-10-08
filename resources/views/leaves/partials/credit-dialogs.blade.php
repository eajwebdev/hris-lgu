{{--
    HR's dialogs for one employee's leave credits: add, deduct, edit either
    kind of entry, delete, and set the other balances. Opened from the
    balances panel and from the ledger rows.

      $otherBalances   column => [label, id of the figure], from the page
--}}
@php
    $dialog = 'm-auto max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $field = 'mt-1 block w-full rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow placeholder:text-ink/40 read-only:bg-line/40 read-only:text-ink/70 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $closeButton = '-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500';
@endphp

{{-- Add: a month's earned credits. Days worked picks the SL / VL earned from
     the CSC conversion table in the script below. --}}
<dialog id="creditAddDialog" aria-labelledby="creditAddTitle" class="{{ $dialog }} w-[min(34rem,calc(100vw-2rem))]">
    <form action="{{ route('leavesCreate') }}" method="POST" class="p-6">
        @csrf
        <input type="hidden" name="empid" value="{{ $employee->id }}">

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="creditAddTitle">Add leave credits</h2>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4" data-earned>
            <div>
                <label for="creditAddDate" class="{{ $label }}">For the month of</label>
                <input type="month" id="creditAddDate" name="date" required class="{{ $field }} h-10">
            </div>
            <div>
                <label for="creditAddDays" class="{{ $label }}">Days</label>
                <input type="number" id="creditAddDays" name="days" min="1" max="30" required data-earned-days class="{{ $field }} h-10">
            </div>
            <div>
                <label for="creditAddSl" class="{{ $label }}">Sick Leave</label>
                <input type="number" id="creditAddSl" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly data-earned-sl class="{{ $field }} h-10">
            </div>
            <div>
                <label for="creditAddVl" class="{{ $label }}">Vacation Leave</label>
                <input type="number" id="creditAddVl" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly data-earned-vl class="{{ $field }} h-10">
            </div>
        </div>

        <div class="mt-4">
            <label for="creditAddRemarks" class="{{ $label }}">Remarks</label>
            <textarea id="creditAddRemarks" name="remarks" rows="2" class="{{ $field }} py-2"></textarea>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" name="btn-submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </form>
</dialog>

{{-- Deduct --}}
<dialog id="creditDeductDialog" aria-labelledby="creditDeductTitle" class="{{ $dialog }} w-[min(28rem,calc(100vw-2rem))]">
    <form action="{{ route('leavescreditDeduct') }}" method="POST" class="p-6">
        @csrf
        <input type="hidden" name="empid" value="{{ $employee->id }}">
        <input type="hidden" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m') }}">

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="creditDeductTitle">Deduct leave credits</h2>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-4">
            <div>
                <label for="creditDeductSl" class="{{ $label }}">Sick Leave</label>
                <input type="number" id="creditDeductSl" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required class="{{ $field }} h-10">
            </div>
            <div>
                <label for="creditDeductVl" class="{{ $label }}">Vacation Leave</label>
                <input type="number" id="creditDeductVl" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required class="{{ $field }} h-10">
            </div>
        </div>

        <div class="mt-4">
            <label for="creditDeductRemarks" class="{{ $label }}">Remarks</label>
            <textarea id="creditDeductRemarks" name="remarks" rows="2" class="{{ $field }} py-2"></textarea>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" name="btn-submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </form>
</dialog>

{{-- Edit an added entry or the starting balance. For the starting balance
     the days are locked and SL / VL are typed in freely; for a month's
     credits it is the other way round. --}}
<dialog id="creditEditDialog" aria-labelledby="creditEditTitle" class="{{ $dialog }} w-[min(34rem,calc(100vw-2rem))]">
    <form action="{{ route('leavesUpdate') }}" method="POST" class="p-6">
        @csrf
        <input type="hidden" name="empid" value="{{ $employee->id }}">
        <input type="hidden" name="lcid">

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="creditEditTitle">Edit leave credits</h2>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-red-800 dark:bg-red-500/10 dark:text-red-300" data-credit-error hidden></p>

        <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4" data-earned>
            <div>
                <label for="creditEditDate" class="{{ $label }}">For the month of</label>
                <input type="month" id="creditEditDate" name="date" required class="{{ $field }} h-10">
            </div>
            <div>
                <label for="creditEditDays" class="{{ $label }}">Days</label>
                <input type="number" id="creditEditDays" name="days" min="1" max="30" required data-earned-days class="{{ $field }} h-10">
            </div>
            <div>
                <label for="creditEditSl" class="{{ $label }}">Sick Leave</label>
                <input type="number" id="creditEditSl" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly data-earned-sl class="{{ $field }} h-10">
            </div>
            <div>
                <label for="creditEditVl" class="{{ $label }}">Vacation Leave</label>
                <input type="number" id="creditEditVl" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly data-earned-vl class="{{ $field }} h-10">
            </div>
        </div>

        <div class="mt-4">
            <label for="creditEditRemarks" class="{{ $label }}">Remarks</label>
            <textarea id="creditEditRemarks" name="remarks" rows="2" class="{{ $field }} py-2"></textarea>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" name="btn-submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Update</button>
        </div>
    </form>
</dialog>

{{-- Edit a deduction --}}
<dialog id="deductEditDialog" aria-labelledby="deductEditTitle" class="{{ $dialog }} w-[min(28rem,calc(100vw-2rem))]">
    <form action="{{ route('leavescreditDeductUpdate') }}" method="POST" class="p-6">
        @csrf
        <input type="hidden" name="empid" value="{{ $employee->id }}">
        <input type="hidden" name="lcid">
        <input type="hidden" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m') }}">

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="deductEditTitle">Edit deduction</h2>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-red-800 dark:bg-red-500/10 dark:text-red-300" data-credit-error hidden></p>

        <div class="mt-5 grid grid-cols-2 gap-4">
            <div>
                <label for="deductEditSl" class="{{ $label }}">Sick Leave</label>
                <input type="number" id="deductEditSl" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required class="{{ $field }} h-10">
            </div>
            <div>
                <label for="deductEditVl" class="{{ $label }}">Vacation Leave</label>
                <input type="number" id="deductEditVl" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required class="{{ $field }} h-10">
            </div>
        </div>

        <div class="mt-4">
            <label for="deductEditRemarks" class="{{ $label }}">Remarks</label>
            <textarea id="deductEditRemarks" name="remarks" rows="2" class="{{ $field }} py-2"></textarea>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" name="btn-submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </form>
</dialog>

{{-- The other balances. No Save button: each figure is stored as it is
     changed (EmployeeController::employeeUpdate), as before. --}}
<dialog id="balancesDialog" aria-labelledby="balancesTitle" class="{{ $dialog }} w-[min(28rem,calc(100vw-2rem))]">
    <div class="p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-display text-xl font-semibold tracking-tight" id="balancesTitle">Other leave balances</h2>
                <p class="mt-0.5 text-ink/60">Each figure is saved as soon as you change it.</p>
            </div>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="mt-4 divide-y divide-line">
            @foreach($otherBalances as $column => [$balanceLabel, $balanceId])
                <div class="flex items-center justify-between gap-4 py-2">
                    <label for="balance-{{ $column }}">{{ $balanceLabel }}</label>
                    <input type="number" id="balance-{{ $column }}" name="{{ $column }}" value="{{ $employee->{$column} }}"
                           step="0.001" min="0" max="30" placeholder="0"
                           data-balance-column="{{ $column }}" data-balance-figure="{{ $balanceId }}"
                           class="h-9 w-24 rounded-lg border border-line bg-paper px-2 text-center text-ink tabular-nums outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15">
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button" data-dialog-close class="{{ $secondary }}">Done</button>
        </div>
    </div>
</dialog>

{{-- Confirmation for deleting a ledger entry. --}}
<dialog id="creditDeleteDialog" aria-labelledby="creditDeleteTitle" class="{{ $dialog }} w-[min(26rem,calc(100vw-2rem))]">
    <div class="p-6">
        <h2 class="font-display text-xl font-semibold tracking-tight" id="creditDeleteTitle">Delete this entry?</h2>
        <p class="mt-3 text-base font-semibold" data-delete-what></p>
        <p class="mt-2 leading-relaxed text-ink/70">The employee's balance is put back to what it was without it. You won't be able to revert this.</p>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="button" id="creditDeleteConfirm" class="h-10 cursor-pointer rounded-xl bg-red-700 px-5 font-medium text-white transition-colors hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Yes, delete it</button>
        </div>
    </div>
</dialog>

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var jsonHeaders = { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };

    /* ------------------------------------------------- days -> SL / VL earned */
    // CSC conversion: leave credits earned for 1 to 30 days of service.
    var equivalences = [
        0.042, 0.083, 0.125, 0.167, 0.208, 0.250, 0.292, 0.333, 0.375,
        0.417, 0.458, 0.500, 0.542, 0.583, 0.625, 0.667, 0.708, 0.750,
        0.792, 0.833, 0.875, 0.917, 0.958, 1.000, 1.042, 1.083, 1.125,
        1.167, 1.208, 1.250
    ];

    document.querySelectorAll('[data-earned]').forEach(function (group) {
        var days = group.querySelector('[data-earned-days]');
        var sl = group.querySelector('[data-earned-sl]');
        var vl = group.querySelector('[data-earned-vl]');

        days.addEventListener('input', function () {
            // Locked while editing a starting balance: nothing to work out.
            if (days.readOnly) return;

            var count = parseInt(days.value, 10);
            if (isNaN(count) || count < 1) { sl.value = ''; vl.value = ''; return; }
            if (count > 30) { count = 30; days.value = 30; }

            sl.value = equivalences[count - 1].toFixed(3);
            vl.value = equivalences[count - 1].toFixed(3);
        });
    });

    /* ------------------------------------------------------- other balances */
    document.querySelectorAll('[data-balance-column]').forEach(function (input) {
        input.addEventListener('change', function () {
            fetch("{{ route('employeeUpdate') }}", {
                method: 'POST',
                headers: jsonHeaders,
                body: new URLSearchParams({ id: "{{ $employee->id }}", column: input.dataset.balanceColumn, value: input.value })
            })
                .then(function (response) { return response.ok ? response : Promise.reject(); })
                .then(function () {
                    document.getElementById(input.dataset.balanceFigure).textContent = input.value === '' ? '0' : input.value;
                })
                .catch(function () { hrisToast('error', 'That balance could not be saved. Please try again.'); });
        });
    });

    /* ------------------------------------------------------------ the ledger */
    var ledger = document.getElementById('creditLedger');
    if (!ledger) return;

    var editUrl = "{{ route('leavesEdit', ['id' => ':id']) }}";
    var deleteUrl = "{{ route('leavesDelete', ['id' => ':id', 'empid' => $employee->id]) }}";

    // Edit: a deduction has its own, smaller form. Either way the entry is
    // read back from the server rather than from the row.
    ledger.addEventListener('click', function (event) {
        var button = event.target.closest('[data-credit-edit]');
        if (!button) return;

        var row = button.closest('[data-row]');
        var dialog = document.getElementById(row.dataset.creditKind === 'deduction' ? 'deductEditDialog' : 'creditEditDialog');
        var form = dialog.querySelector('form');
        var error = dialog.querySelector('[data-credit-error]');

        form.reset();
        form.elements['lcid'].value = row.dataset.creditId;
        error.hidden = true;
        dialog.showModal();

        fetch(editUrl.replace(':id', encodeURIComponent(row.dataset.creditId)), { method: 'POST', headers: jsonHeaders })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function (response) {
                var entry = response.data;
                if (!entry) { return Promise.reject(); }

                form.elements['sl'].value = entry.earn_sl;
                form.elements['vl'].value = entry.earn_vl;
                form.elements['remarks'].value = entry.remarks || '';
                if (row.dataset.creditKind === 'deduction') return;

                form.elements['date'].value = entry.date || '';
                form.elements['days'].value = entry.days;

                // stat 0 is the starting balance.
                var starting = entry.stat == 0;
                form.elements['days'].readOnly = starting;
                form.elements['days'].required = !starting;
                ['sl', 'vl'].forEach(function (name) {
                    form.elements[name].readOnly = !starting;
                    if (starting) {
                        form.elements[name].removeAttribute('min');
                        form.elements[name].removeAttribute('max');
                    } else {
                        form.elements[name].min = 0;
                        form.elements[name].max = 30;
                    }
                });
            })
            .catch(function () {
                error.textContent = 'This entry could not be loaded. Close this and try again before changing anything.';
                error.hidden = false;
            });
    });

    // Delete
    var deleteDialog = document.getElementById('creditDeleteDialog');
    var doomed = null;

    ledger.addEventListener('click', function (event) {
        var button = event.target.closest('[data-credit-delete]');
        if (!button) return;

        doomed = button.closest('[data-row]');
        deleteDialog.querySelector('[data-delete-what]').textContent = doomed.dataset.creditSummary;
        deleteDialog.showModal();
    });

    document.getElementById('creditDeleteConfirm').addEventListener('click', function () {
        var row = doomed;
        doomed = null;
        deleteDialog.close();
        if (!row) return;

        fetch(deleteUrl.replace(':id', encodeURIComponent(row.dataset.creditId)), { method: 'POST', headers: jsonHeaders })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function (response) {
                document.getElementById('b-vl').textContent = response.vl;
                document.getElementById('b-sl').textContent = response.sl;
                row.remove();
                ledger.hrisList.render();
                hrisToast('success', 'Entry deleted.');
            })
            .catch(function () { hrisToast('error', 'The entry could not be deleted. Please try again.'); });
    });
})();
</script>
@endpush
