{{--
    What can be done to a leave application, for the Status and History
    screens: read its form, sign or approve it, return it, refuse it with a
    reason, cancel it, and (HR) check its days. The pages only mark up
    buttons inside an element carrying data-leave="<id>":

      [data-leave-form="<url>"]      the form as a PDF, in a dialog
      [data-leave-post="<url>"]      ask (data-ask, data-ask-detail,
                                     data-ask-button, data-ask-danger), then
                                     post data-params
      [data-leave-reason="<by>"]     ask for a reason, then refuse: 2 the
                                     supervisor, 3 the Mayor / Vice Mayor,
                                     4 HR cancelling an approved leave
      [data-leave-days-open]         HR's first look: days without pay and
                                     holidays

    Every one of them changes where an application stands, so the page is
    simply loaded again afterwards, with a word about what happened.

      $withDays   include the days dialog (Status, for HR)
--}}
@php
    $dialog = 'm-auto max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $field = 'mt-1 block w-full rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 disabled:cursor-wait disabled:opacity-60 dark:ring-1 dark:ring-cream/15';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $closeButton = '-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500';
@endphp

{{-- The leave form, read without leaving the page. The frame gets its
     address on opening and is emptied on closing. --}}
<dialog id="leaveFormDialog" aria-labelledby="leaveFormTitle"
        class="m-auto h-[calc(100dvh-2rem)] w-[min(64rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60 open:flex">
    <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-3">
        <h2 class="min-w-0 truncate font-display text-lg font-semibold tracking-tight" id="leaveFormTitle"></h2>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500"><i class="fas fa-xmark"></i></button>
    </div>
    <iframe title="Leave form" class="min-h-0 w-full flex-1 border-0 bg-white"></iframe>
</dialog>

{{-- A refusal always carries its reason. --}}
<dialog id="leaveReasonDialog" aria-labelledby="leaveReasonTitle" class="{{ $dialog }} w-[min(30rem,calc(100vw-2rem))]">
    <form method="dialog" class="p-6" data-url="{{ route('leaveDisapprove') }}">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="font-display text-xl font-semibold tracking-tight" id="leaveReasonTitle"></h2>
                <p class="mt-1 text-ink/60" data-reason-what></p>
            </div>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="mt-5">
            <label for="leaveReason" class="{{ $label }}">Reason</label>
            <textarea id="leaveReason" name="remarks" rows="4" required class="{{ $field }} py-2"></textarea>
            <p class="mt-1.5 text-xs text-ink/55" data-reason-note></p>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Keep it</button>
            <button type="submit" class="h-10 cursor-pointer rounded-xl bg-red-700 px-5 font-medium text-white transition-colors hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 disabled:cursor-wait disabled:opacity-60" data-reason-submit></button>
        </div>
    </form>
</dialog>

@if($withDays ?? false)
    {{-- HR's first look at a new application. Holidays inside the dates come
         off the days, days without pay are not charged to leave credits, and
         the form then goes to the employee to sign. --}}
    <dialog id="leaveDaysDialog" aria-labelledby="leaveDaysTitle" class="{{ $dialog }} w-[min(30rem,calc(100vw-2rem))]">
        <form method="dialog" class="p-6" data-url="{{ route('leaveWpay') }}">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="font-display text-xl font-semibold tracking-tight" id="leaveDaysTitle">Check the days</h2>
                    <p class="mt-1 text-ink/60" data-days-what></p>
                </div>
                <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4">
                <div>
                    <label for="leaveHolidays" class="{{ $label }}">Holidays in the dates</label>
                    <input type="number" id="leaveHolidays" name="holiday" min="0" step="any" value="0" required class="{{ $field }} h-10">
                </div>
                <div>
                    <label for="leaveWithoutPay" class="{{ $label }}">Days without pay</label>
                    <input type="number" id="leaveWithoutPay" name="day_wpay" min="0" step="any" value="0" required class="{{ $field }} h-10">
                </div>
            </div>
            <p class="mt-3 rounded-xl bg-forest-100 px-3 py-2 text-forest-900" data-days-sum aria-live="polite"></p>
            <p class="mt-3 text-xs text-ink/55">Holidays are taken off the days applied for. Days without pay are not charged to leave credits. The form then goes to the employee to e-sign.</p>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Not now</button>
                <button type="submit" class="{{ $primary }}">Send to the employee</button>
            </div>
        </form>
    </dialog>
@endif

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var FLASH = 'hris:leave-flash';

    // What the last action did, said once the page is back.
    window.addEventListener('load', function () {
        try {
            var said = sessionStorage.getItem(FLASH);
            if (said) { sessionStorage.removeItem(FLASH); hrisToast('success', said); }
        } catch (error) { /* no storage: the page itself shows the change */ }
    });

    function reloadSaying(message) {
        try { sessionStorage.setItem(FLASH, message); } catch (error) { /* as above */ }
        window.location.reload();
    }

    // Posts and settles on the reply, or fails with something worth showing.
    function post(url, params) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(params)
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (response.ok && data.success !== false) return data;

                var why = data.error || data.message || '';
                if (why === 'Insufficient leave credits') why = 'There are not enough leave credits for this. Check the balances.';
                throw new Error(response.status >= 500 ? '' : why);
            });
        });
    }

    // One action at a time: the button shows it is working until the page
    // reloads or the action fails.
    function send(button, url, params, done) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');

        return post(url, params)
            .then(function () { reloadSaying(done); })
            .catch(function (error) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                hrisToast('error', error.message || 'That did not go through. Please try again.');
            });
    }

    function about(element) {
        return element.closest('[data-leave]');
    }

    /* -------------------------------------------------------- the leave form */
    var formDialog = document.getElementById('leaveFormDialog');
    var frame = formDialog.querySelector('iframe');
    formDialog.addEventListener('close', function () { frame.removeAttribute('src'); });

    /* ------------------------------------------------------------ the reason */
    var reasonDialog = document.getElementById('leaveReasonDialog');
    var reasonForm = reasonDialog.querySelector('form');
    var refusing = null;     // { id, by }

    var refusals = {
        cancel: ['Cancel this leave', 'Cancel the leave', 'The leave credits it used are given back, and it stays in History marked as cancelled.', 'Leave cancelled.'],
        disapprove: ['Disapprove this leave application', 'Disapprove', 'The employee sees this reason. The application moves to History.', 'Leave application disapproved.']
    };

    reasonForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!refusing) return;

        var wording = refusals[refusing.by === '4' ? 'cancel' : 'disapprove'];
        send(reasonForm.querySelector('[data-reason-submit]'), reasonForm.dataset.url,
            { id: refusing.id, by: refusing.by, remarks: reasonForm.elements.remarks.value.trim() }, wording[3]);
    });

    /* -------------------------------------------------------------- the days */
    var daysDialog = document.getElementById('leaveDaysDialog');
    var daysForm = daysDialog && daysDialog.querySelector('form');
    var checking = null;     // { id, days }

    function sumDays() {
        var holidays = parseFloat(daysForm.elements.holiday.value) || 0;
        var unpaid = parseFloat(daysForm.elements.day_wpay.value) || 0;
        var left = checking.days - holidays;
        var paid = left - unpaid;
        var wrong = left < 0 ? 'That is more holidays than days applied for.' : (paid < 0 ? 'That is more days without pay than the days left.' : '');

        daysForm.elements.holiday.setCustomValidity(left < 0 ? wrong : '');
        daysForm.elements.day_wpay.setCustomValidity(left >= 0 && paid < 0 ? wrong : '');
        daysForm.querySelector('[data-days-sum]').textContent = wrong
            || (round(left) + (left === 1 ? ' day' : ' days') + ' of leave: ' + round(paid) + ' with pay, ' + round(unpaid) + ' without.');
    }

    function round(number) {
        return String(Math.round(number * 1000) / 1000);
    }

    if (daysForm) {
        daysForm.addEventListener('input', sumDays);
        daysForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!checking) return;

            send(daysForm.querySelector('[type="submit"]'), daysForm.dataset.url,
                { id: checking.id, day_wpay: daysForm.elements.day_wpay.value, holiday: daysForm.elements.holiday.value },
                'Days set. The application is with the employee to sign.');
        });
    }

    /* ------------------------------------------------------------ the buttons */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-leave-form], [data-leave-post], [data-leave-reason], [data-leave-days-open]');
        if (!button) return;

        var leave = about(button);
        if (!leave) return;

        if (button.hasAttribute('data-leave-form')) {
            formDialog.querySelector('#leaveFormTitle').textContent = 'Leave form, ' + leave.dataset.leaveLabel;
            frame.src = button.dataset.leaveForm;
            formDialog.showModal();
            return;
        }

        if (button.hasAttribute('data-leave-reason')) {
            refusing = { id: leave.dataset.leave, by: button.dataset.leaveReason };
            var wording = refusals[refusing.by === '4' ? 'cancel' : 'disapprove'];

            reasonForm.reset();
            reasonDialog.querySelector('#leaveReasonTitle').textContent = wording[0];
            reasonDialog.querySelector('[data-reason-what]').textContent = leave.dataset.leaveLabel;
            reasonDialog.querySelector('[data-reason-submit]').textContent = wording[1];
            reasonDialog.querySelector('[data-reason-note]').textContent = wording[2];
            reasonDialog.showModal();
            return;
        }

        if (button.hasAttribute('data-leave-days-open')) {
            checking = { id: leave.dataset.leave, days: parseFloat(leave.dataset.leaveDays) || 0 };

            daysForm.reset();
            daysDialog.querySelector('[data-days-what]').textContent = leave.dataset.leaveLabel + ' · ' + round(checking.days) + (checking.days === 1 ? ' day' : ' days') + ' applied for';
            sumDays();
            daysDialog.showModal();
            return;
        }

        hrisConfirm({
            title: button.dataset.ask,
            detail: button.dataset.askDetail,
            button: button.dataset.askButton,
            danger: button.hasAttribute('data-ask-danger')
        }).then(function (yes) {
            if (yes) send(button, button.dataset.leavePost, JSON.parse(button.dataset.params || '{}'), button.dataset.done);
        });
    });
})();
</script>
@endpush
