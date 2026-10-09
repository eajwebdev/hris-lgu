{{--
    The working part of a Personal Data Sheet page that keeps a list of
    entries, each with a PDF and a review by HR: eligibility, work
    experience, voluntary work, learning and development. They are the same
    page four times over, so each view only describes itself, as $records:

      noun         "eligibility", for the wording
      article      "an", "a", or '' where the noun takes none
      fields       the form, as descriptions for emp/partials/field
      editing      the entry being changed, or null when adding
      create       where a new entry is posted
      update       where the edited one is
      cancelUrl    where HR's cancellation, with remarks, is posted
      listUrl      this page without an entry open, to leave editing by
      entries      the list, as descriptions for emp/partials/record-card

    An entry is added or changed by an ordinary form post (it may carry a
    file); the server sends the browser back here with a message. The form
    is folded away once there are entries, unless one is being edited or the
    last attempt was refused. Approving and deleting are done from the list
    without leaving the page; cancelling asks for remarks first.
--}}
@php
    $noun = $records['noun'];
    $editing = $records['editing'];
    $entries = $records['entries'];
    $open = $editing || $errors->any() || !count($entries);

    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'inline-flex h-10 cursor-pointer items-center rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $dialog = 'm-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $close = 'grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500';
@endphp
<div class="@container space-y-5" id="pdsRecords">
    <details class="group rounded-2xl border border-line bg-surface" @if($open) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 rounded-2xl px-5 py-4 focus-visible:outline-2 focus-visible:outline-sun-500 sm:px-6 [&::-webkit-details-marker]:hidden">
            <span>
                <span class="block font-display text-lg font-semibold tracking-tight">{{ $editing ? 'Edit this ' . $noun : 'Add ' . trim($records['article'] . ' ' . $noun) }}</span>
                <span class="block text-ink/60">
                    @if($editing)
                        Leave the attachment empty to keep the one already uploaded.
                    @else
                        HR reviews each entry after it is submitted.
                    @endif
                </span>
            </span>
            <span class="text-ink/45 transition-transform group-open:rotate-180"><i class="fas fa-angle-down"></i></span>
        </summary>

        <form method="POST" action="{{ $editing ? $records['update'] : $records['create'] }}" enctype="multipart/form-data" autocomplete="off"
              class="border-t border-line p-5 sm:p-6">
            @csrf
            @if($editing)
                <input type="hidden" name="id" value="{{ $editing->id }}">
            @endif
            <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">

            <div class="grid gap-4 @md:grid-cols-2 @4xl:grid-cols-4">
                @foreach($records['fields'] as $spec)
                    @include('emp.partials.field', ['spec' => $spec, 'record' => $editing, 'plain' => true])
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                @if($editing)
                    <a href="{{ $records['listUrl'] }}" class="{{ $secondary }}">Stop editing</a>
                @endif
                <button type="submit" name="btn-submit" class="{{ $primary }}">
                    <i class="fas fa-save mr-1"></i> {{ $editing ? 'Save changes' : 'Submit' }}
                </button>
            </div>
        </form>
    </details>

    @if(count($entries))
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-ink/60" data-record-count>{{ count($entries) }} {{ count($entries) == 1 ? 'entry' : 'entries' }}</p>

            <label class="relative w-full sm:w-72">
                <span class="sr-only">Search the entries</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
                <input type="search" data-record-search placeholder="Search" autocomplete="off"
                       class="h-10 w-full rounded-xl border border-line bg-surface pr-3 pl-9 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15">
            </label>
        </div>

        <ul class="space-y-4" data-record-list>
            @foreach($entries as $entry)
                @include('emp.partials.record-card', ['entry' => $entry])
            @endforeach
        </ul>

        <p class="rounded-2xl border border-dashed border-line px-5 py-10 text-center text-ink/55" data-record-none hidden>No entry matches that.</p>
    @else
        <p class="rounded-2xl border border-dashed border-line px-5 py-10 text-center text-ink/55">Nothing has been added yet.</p>
    @endif
</div>

{{-- The attachment, read without leaving the page. The frame is given its
     address when this opens and emptied when it closes. --}}
<dialog id="recordPdf" aria-labelledby="recordPdfTitle" class="{{ $dialog }} h-[calc(100dvh-2rem)] w-[min(64rem,calc(100vw-2rem))] flex-col overflow-hidden open:flex">
    <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-3">
        <h2 class="min-w-0 truncate font-display text-lg font-semibold tracking-tight" id="recordPdfTitle"></h2>
        <button type="button" data-dialog-close aria-label="Close" class="{{ $close }} -mr-2"><i class="fas fa-xmark"></i></button>
    </div>
    <iframe title="Attachment" class="min-h-0 w-full flex-1 border-0 bg-white"></iframe>
</dialog>

@if($guard == 'web')
    <dialog id="recordCancel" aria-labelledby="recordCancelTitle" class="{{ $dialog }} w-[min(28rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ $records['cancelUrl'] }}" class="p-6">
            @csrf
            <input type="hidden" name="id">

            <h2 class="font-display text-xl font-semibold tracking-tight" id="recordCancelTitle">Cancel this {{ $noun }}?</h2>
            <p class="mt-1 text-ink/60" data-cancel-name></p>

            <label for="recordCancelRemarks" class="mt-4 block text-xs font-medium text-ink/60">Remarks for the employee</label>
            <textarea id="recordCancelRemarks" name="remarks" rows="3" required placeholder="What needs correcting"
                      class="mt-1 block w-full rounded-xl border border-line bg-paper px-3 py-2 leading-relaxed text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15"></textarea>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Close</button>
                <button type="submit" class="h-10 cursor-pointer rounded-xl bg-red-700 px-5 font-medium text-white transition-colors hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Mark as canceled</button>
            </div>
        </form>
    </dialog>
@endif

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var page = document.getElementById('pdsRecords');
    var list = page.querySelector('[data-record-list]');

    // Money typed with its thousands marked: 25,000.
    page.addEventListener('input', function (event) {
        if (!event.target.matches('[data-thousands]')) return;
        event.target.value = event.target.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    });

    if (!list) return;

    var count = page.querySelector('[data-record-count]');
    var none = page.querySelector('[data-record-none]');
    var search = page.querySelector('[data-record-search]');

    function cards() { return Array.prototype.slice.call(list.querySelectorAll('[data-record]')); }

    function filter() {
        var query = search.value.trim().toLowerCase();
        var shown = 0;
        cards().forEach(function (card) {
            card.hidden = !!query && card.dataset.search.indexOf(query) === -1;
            if (!card.hidden) { shown++; }
        });
        none.hidden = shown > 0;
        none.textContent = cards().length ? 'No entry matches that.' : 'Nothing has been added yet.';
        count.textContent = cards().length + (cards().length === 1 ? ' entry' : ' entries');
    }

    search.addEventListener('input', filter);

    // Both endpoints answer 200 either way and say how it went in the body.
    function post(url) {
        return fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function (data) { return data.status === 200 ? data : Promise.reject(); });
    }

    /* ------------------------------------------------------------- the PDF */
    var pdf = document.getElementById('recordPdf');
    var frame = pdf.querySelector('iframe');
    pdf.addEventListener('close', function () { frame.removeAttribute('src'); });

    /* -------------------------------------------------------------- cancel */
    var cancel = document.getElementById('recordCancel');

    list.addEventListener('click', function (event) {
        var button = event.target.closest('button');
        if (!button) return;

        var card = button.closest('[data-record]');
        var title = card.dataset.recordTitle;

        if (button.hasAttribute('data-pdf-open')) {
            pdf.querySelector('#recordPdfTitle').textContent = title;
            frame.src = button.dataset.pdfOpen;
            pdf.showModal();
        }

        if (button.hasAttribute('data-record-cancel')) {
            cancel.querySelector('[name="id"]').value = card.dataset.record;
            cancel.querySelector('[name="remarks"]').value = '';
            cancel.querySelector('[data-cancel-name]').textContent = title;
            cancel.showModal();
        }

        if (button.hasAttribute('data-record-approve')) {
            hrisConfirm({ title: 'Approve this entry?', detail: title, button: 'Yes, approve' }).then(function (go) {
                if (!go) return;

                post(button.dataset.recordApprove)
                    .then(function () {
                        var status = card.querySelector('[data-record-status]');
                        status.dataset.status = '1';
                        status.textContent = 'Reviewed';

                        // Nothing left for HR to decide on it.
                        card.querySelectorAll('[data-record-approve], [data-record-cancel], [data-record-remarks]').forEach(function (spent) { spent.remove(); });
                        hrisToast('success', 'Approved.');
                    })
                    .catch(function () { hrisToast('error', 'It could not be approved. Please try again.'); });
            });
        }

        if (button.hasAttribute('data-record-delete')) {
            hrisConfirm({ title: 'Delete this entry?', detail: title + '. This cannot be undone.', button: 'Yes, delete', danger: true }).then(function (go) {
                if (!go) return;

                post(button.dataset.recordDelete)
                    .then(function () {
                        card.remove();
                        filter();
                        hrisToast('success', 'Deleted.');
                    })
                    .catch(function () { hrisToast('error', 'It could not be deleted. Please try again.'); });
            });
        }
    });
})();
</script>
@endpush
