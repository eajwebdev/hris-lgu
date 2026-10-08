@extends('layouts.app')

@php
    // One view for two routes: /office lists, /office/edit/{id} lists with the
    // editor already open on that office.
    $offEdit = $offEdit ?? null;

    // Name first, so typing a surname in the open list jumps to it.
    $people = $employee->sortBy(fn ($emp) => strtolower($emp->lname . ' ' . $emp->fname));

    $bannerButton = 'inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border px-4 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    // Height and padding are added where a field is used; two of each on one
    // element would leave the winner to stylesheet order.
    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $rowAction = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $dialog = 'm-auto max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $label = 'block text-xs font-medium text-ink/60';
@endphp

@if($offEdit)
    @section('breadcrumb', 'Edit')
@endif

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Offices</h1>
            <p class="mt-1 text-cream/70">
                {{ number_format($office->count()) }} on record
                <span class="mx-1.5 text-cream/30">|</span>
                {{ number_format($office->whereNotNull('office_head_id')->count()) }} with a head assigned
            </p>
        </div>

        <button type="button" data-office-new class="{{ $bannerButton }} border-cream bg-cream text-forest-900 hover:bg-white">
            <i class="fas fa-plus"></i> Add office
        </button>
    </div>
@endsection

@section('body')
<section class="rounded-2xl border border-line bg-surface" id="officeList" data-list data-list-sort-by="name">
    <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
        <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
            <span class="sr-only">Search offices</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" data-list-search placeholder="Search office, abbreviation, head" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
        </label>

        <p class="ml-auto text-ink/55" data-list-count aria-live="polite"></p>
    </div>

    {{-- relative: keeps the visually hidden labels in the cells inside this
         scroller, instead of widening the whole page on a phone. --}}
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    @foreach(['name' => 'Office', 'abbr' => 'Abbreviation', 'head' => 'Office head', 'oic' => 'OIC', 'staff' => 'Employees'] as $key => $heading)
                        <th scope="col" class="px-4 py-3 font-medium first:pl-5 {{ $key === 'oic' ? 'max-lg:hidden' : '' }} {{ $key === 'head' ? 'max-md:hidden' : '' }} {{ $key === 'staff' ? 'max-sm:hidden' : '' }}">
                            <button type="button" data-list-sort="{{ $key }}" class="-mx-1.5 inline-flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 transition-colors hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                                {{ $heading }} <i class="fas fa-sort text-[10px] opacity-50"></i>
                            </button>
                        </th>
                    @endforeach
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-line">
                @foreach($office as $row)
                    @php
                        $head = trim($row->efname . ' ' . $row->elname);
                        $oic = trim($row->ofname . ' ' . $row->olname);
                        $staff = (int) ($headcounts[$row->id] ?? 0);
                    @endphp
                    <tr id="tr-{{ $row->id }}" data-row
                        data-search="{{ strtolower($row->office_name . ' ' . $row->office_abbr . ' ' . $head . ' ' . $oic) }}"
                        data-name="{{ strtolower($row->office_name) }}" data-abbr="{{ strtolower($row->office_abbr) }}"
                        data-head="{{ strtolower($head) }}" data-oic="{{ strtolower($oic) }}" data-staff="{{ $staff ?: '' }}"
                        data-office-id="{{ $row->id }}" data-office-name="{{ $row->office_name }}" data-office-abbr="{{ $row->office_abbr }}"
                        data-office-head="{{ $row->office_head_id }}" data-office-oic="{{ $row->oic_id }}"
                        class="transition-colors hover:bg-paper/70">
                        <td class="px-4 py-3 pl-5 font-semibold">{{ $row->office_name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row->office_abbr }}</td>
                        <td class="px-4 py-3 max-md:hidden">
                            @if($head) {{ $head }} @else <span class="text-ink/40">Not assigned</span> @endif
                        </td>
                        <td class="px-4 py-3 max-lg:hidden">
                            @if($oic) {{ $oic }} @else <span class="text-ink/40">None</span> @endif
                        </td>
                        <td class="px-4 py-3 tabular-nums max-sm:hidden">
                            @if($staff) {{ number_format($staff) }} @else <span class="text-ink/40">0</span> @endif
                        </td>
                        <td class="px-4 py-3 pr-5">
                            <div class="flex justify-end gap-1">
                                <button type="button" title="Edit office" data-office-edit class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700">
                                    <i class="fas fa-pen"></i><span class="sr-only">Edit {{ $row->office_name }}</span>
                                </button>
                                <button type="button" title="Delete office" data-office-delete class="{{ $rowAction }} hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300">
                                    <i class="fas fa-trash"></i><span class="sr-only">Delete {{ $row->office_name }}</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No office matches that.</p>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
        <label class="flex items-center gap-2 text-ink/55">
            Rows
            <select data-list-size class="{{ $field }} h-9 pr-7 pl-3">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="0">All</option>
            </select>
        </label>

        <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
    </div>
</section>

{{-- Add and edit share this form; the script points it at officeCreate or
     officeUpdate and fills it from the row. Arriving on /office/edit/{id}
     opens it on that office. --}}
<dialog id="officeDialog" aria-labelledby="officeTitle" class="{{ $dialog }} w-[min(30rem,calc(100vw-2rem))]"
        data-create-url="{{ route('officeCreate') }}" data-update-url="{{ route('officeUpdate') }}"
        @if($offEdit) data-open-on="{{ $offEdit->id }}" @endif>
    <form method="POST" action="{{ route('officeCreate') }}" class="p-6">
        @csrf
        <input type="hidden" name="oid">

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="officeTitle">Add office</h2>
            <button type="button" data-dialog-close aria-label="Close" class="-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="mt-5 space-y-4">
            <div>
                <label for="officeName" class="{{ $label }}">Office name</label>
                <input type="text" id="officeName" name="OfficeName" required autocomplete="off" data-capitalise class="{{ $field }} mt-1 block h-10 w-full px-3">
            </div>

            <div>
                <label for="officeAbbr" class="{{ $label }}">Abbreviation</label>
                <input type="text" id="officeAbbr" name="OfficeAbbreviation" required autocomplete="off" data-capitalise class="{{ $field }} mt-1 block h-10 w-full px-3">
            </div>

            <div>
                <label for="officeHead" class="{{ $label }}">Office head <span data-head-required hidden>(required)</span></label>
                <select id="officeHead" name="office_head_id" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    <option value="">Not assigned</option>
                    @foreach($people as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->lname }}, {{ $emp->fname }} ({{ $emp->emp_ID }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="officeOic" class="{{ $label }}">Officer in charge (OIC)</label>
                <select id="officeOic" name="oic_id" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    <option value="">None</option>
                    @foreach($people as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->lname }}, {{ $emp->fname }} ({{ $emp->emp_ID }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" name="btn-submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </form>
</dialog>

{{-- Confirmation for deleting an office. --}}
<dialog id="officeDeleteDialog" aria-labelledby="officeDeleteTitle" class="{{ $dialog }} w-[min(26rem,calc(100vw-2rem))]">
    <div class="p-6">
        <h2 class="font-display text-xl font-semibold tracking-tight" id="officeDeleteTitle">Delete this office?</h2>
        <p class="mt-3 text-base font-semibold" data-delete-name></p>
        <p class="mt-2 leading-relaxed text-ink/70">You won't be able to revert this.</p>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="button" id="officeDeleteConfirm" class="h-10 cursor-pointer rounded-xl bg-red-700 px-5 font-medium text-white transition-colors hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Yes, delete it</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var list = document.getElementById('officeList');

    /* ---------------------------------------------------------- add / edit */
    var dialog = document.getElementById('officeDialog');
    var form = dialog.querySelector('form');
    var head = form.elements['office_head_id'];

    // row is the table row to edit, or null for a new office.
    function openEditor(row) {
        form.reset();
        form.action = row ? dialog.dataset.updateUrl : dialog.dataset.createUrl;
        dialog.querySelector('#officeTitle').textContent = row ? 'Edit office' : 'Add office';

        form.elements['oid'].value = row ? row.dataset.officeId : '';
        form.elements['OfficeName'].value = row ? row.dataset.officeName : '';
        form.elements['OfficeAbbreviation'].value = row ? row.dataset.officeAbbr : '';
        head.value = row ? row.dataset.officeHead : '';
        form.elements['oic_id'].value = row ? row.dataset.officeOic : '';

        // officeUpdate insists on a head; officeCreate does not.
        head.required = !!row;
        dialog.querySelector('[data-head-required]').hidden = !row;

        dialog.showModal();
        form.elements['OfficeName'].focus();
    }

    document.querySelector('[data-office-new]').addEventListener('click', function () { openEditor(null); });

    list.addEventListener('click', function (event) {
        var edit = event.target.closest('[data-office-edit]');
        if (edit) { openEditor(edit.closest('[data-row]')); }
    });

    if (dialog.dataset.openOn) {
        var requested = document.getElementById('tr-' + dialog.dataset.openOn);
        if (requested) { openEditor(requested); }
    }

    // First letter of each word in capitals as it is typed, as before.
    form.querySelectorAll('[data-capitalise]').forEach(function (input) {
        input.addEventListener('input', function () {
            var caret = input.selectionStart;
            input.value = input.value.split(' ').map(function (word) {
                return word.substr(0, 1).toUpperCase() + word.substr(1);
            }).join(' ');
            input.setSelectionRange(caret, caret);
        });
    });

    /* -------------------------------------------------------------- delete */
    var deleteDialog = document.getElementById('officeDeleteDialog');
    var deleteUrl = "{{ route('officeDelete', ['id' => ':id']) }}";
    var doomed = null;

    list.addEventListener('click', function (event) {
        var button = event.target.closest('[data-office-delete]');
        if (!button) return;

        doomed = button.closest('[data-row]');
        deleteDialog.querySelector('[data-delete-name]').textContent = doomed.dataset.officeName;
        deleteDialog.showModal();
    });

    document.getElementById('officeDeleteConfirm').addEventListener('click', function () {
        var row = doomed;
        doomed = null;
        deleteDialog.close();
        if (!row) return;

        fetch(deleteUrl.replace(':id', encodeURIComponent(row.dataset.officeId)), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function () {
                row.remove();
                list.hrisList.render();
                hrisToast('success', 'Office deleted.');
            })
            .catch(function () { hrisToast('error', 'The office could not be deleted. Please try again.'); });
    });
})();
</script>
@endpush
