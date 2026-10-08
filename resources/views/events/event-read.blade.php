@extends('layouts.app')

@php
    $palette = event_palette();

    // Quick starts: a title and the colour it usually goes by.
    $presets = [
        ['Academic Council', 'bg-primary'],
        ['Admin Council', 'bg-info'],
        ['Convocation', 'bg-warning'],
        ['Trainings & Seminar', 'bg-success'],
        ['Orientation', 'bg-danger'],
        ['Meeting', 'bg-secondary'],
    ];

    $dialog = 'm-auto max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $field = 'mt-1 block h-10 w-full rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $danger = 'h-10 cursor-pointer rounded-xl border border-red-300 px-4 font-medium text-red-700 transition-colors hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:border-red-400/40 dark:text-red-300 dark:hover:bg-red-500/10';
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Events</h1>
            <p class="mt-1 text-cream/70">Click or drag on the calendar to add an event. Click an event to edit it, drag it to reschedule.</p>
        </div>
        @include('events.partials.tabs')
    </div>
@endsection

@section('body')
@include('partials.event-calendar')

<section class="rounded-2xl border border-line bg-surface">
    {{-- Quick starts. Dragging one onto a date, or pressing it, opens the new
         event form with its title and colour filled in. --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-line p-4" id="eventPresets">
        <p class="mr-1 text-ink/60">Quick start: drag onto a date, or press.</p>
        @foreach($presets as [$presetTitle, $presetColor])
            <button type="button" data-preset data-title="{{ $presetTitle }}" data-color="{{ $presetColor }}"
                    class="inline-flex h-9 cursor-grab items-center gap-2 rounded-lg border border-line px-3 transition-colors hover:border-forest-600/40 hover:bg-paper focus-visible:outline-2 focus-visible:outline-sun-500 active:cursor-grabbing">
                <span class="size-2.5 rounded-full" style="background: {{ $palette[$presetColor][1] }}"></span>
                {{ $presetTitle }}
            </button>
        @endforeach

        <button type="button" data-event-new class="{{ $primary }} ml-auto h-9 px-4">
            <i class="fas fa-plus mr-1"></i> New event
        </button>
    </div>

    <div class="p-4 sm:p-5">
        <div id="calendar"></div>
    </div>
</section>

{{-- One form for a new event and for an existing one; the script points it at
     eventCreate or eventUpdateSave and fills it in. --}}
<dialog id="eventDialog" aria-labelledby="eventDialogTitle" class="{{ $dialog }} w-[min(32rem,calc(100vw-2rem))]"
        data-create-url="{{ route('eventCreate') }}" data-update-url="{{ route('eventUpdateSave') }}">
    <form method="POST" action="{{ route('eventCreate') }}" class="p-6">
        @csrf
        <input type="hidden" name="id">

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="eventDialogTitle">New event</h2>
            <button type="button" data-dialog-close aria-label="Close" class="-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="mt-5 space-y-4">
            <div>
                <label for="eventTitle" class="{{ $label }}">Event title</label>
                <input type="text" id="eventTitle" name="title" required class="{{ $field }}">
            </div>
            <div>
                <label for="eventVenue" class="{{ $label }}">Venue</label>
                <input type="text" id="eventVenue" name="venue" required class="{{ $field }}">
            </div>
            <div>
                <label for="eventDept" class="{{ $label }}">Organizing department/s</label>
                <input type="text" id="eventDept" name="org_dept" required class="{{ $field }}">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="eventStart" class="{{ $label }}">Start</label>
                    <input type="datetime-local" id="eventStart" name="start" required class="{{ $field }}">
                </div>
                <div>
                    <label for="eventEnd" class="{{ $label }}">End</label>
                    <input type="datetime-local" id="eventEnd" name="end" class="{{ $field }}">
                </div>
            </div>

            {{-- Only when creating: this is what the attendee list is built
                 from, and the list is not rebuilt afterwards. --}}
            <div data-create-only>
                <label for="eventStatus" class="{{ $label }}">Who attends</label>
                <select id="eventStatus" name="emp_status" required class="{{ $field }} pr-8">
                    <option value="0">All employees</option>
                    @foreach ($status as $st)
                        <option value="{{ $st->id }}">{{ $st->status_name }} employees</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink/55">Sets the attendee list for the QR scanner. It cannot be changed once the event is saved.</p>
            </div>

            <fieldset>
                <legend class="{{ $label }}">Colour</legend>
                <div class="mt-2 flex gap-2.5">
                    @foreach($palette as $stored => [$colorName, $hex])
                        <label title="{{ $colorName }}" class="cursor-pointer">
                            <input type="radio" name="bg_color" value="{{ $stored }}" required class="peer sr-only" @checked($loop->first)>
                            <span class="block size-7 rounded-full ring-offset-2 ring-offset-surface transition-shadow peer-checked:ring-2 peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-sun-500" style="background: {{ $hex }}"></span>
                            <span class="sr-only">{{ $colorName }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-2">
            <button type="button" data-event-delete hidden class="{{ $danger }}"><i class="fas fa-trash mr-1"></i> Delete</button>
            <div class="ml-auto flex gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
                <button type="submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> <span data-save-label>Save event</span></button>
            </div>
        </div>
    </form>
</dialog>

{{-- Confirmation for deleting an event. --}}
<dialog id="eventDeleteDialog" aria-labelledby="eventDeleteTitle" class="{{ $dialog }} w-[min(26rem,calc(100vw-2rem))]">
    <div class="p-6">
        <h2 class="font-display text-xl font-semibold tracking-tight" id="eventDeleteTitle">Delete this event?</h2>
        <p class="mt-3 text-base font-semibold" data-delete-name></p>
        <p class="mt-2 leading-relaxed text-ink/70">This will remove the event and its attendance logs. This cannot be undone.</p>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="button" id="eventDeleteConfirm" class="h-10 cursor-pointer rounded-xl bg-red-700 px-5 font-medium text-white transition-colors hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Yes, delete it</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var jsonHeaders = { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };

    var dialog = document.getElementById('eventDialog');
    var form = dialog.querySelector('form');
    var deleteButton = dialog.querySelector('[data-event-delete]');
    var createOnly = dialog.querySelector('[data-create-only]');

    function pad(number) { return String(number).padStart(2, '0'); }

    // For a datetime-local field: "2026-10-09T08:00", in local time.
    function toInput(date) {
        if (!date) return '';
        var d = new Date(date);
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    // For the server: "2026-10-09 08:00:00".
    function toServer(date) { return date ? toInput(date).replace('T', ' ') + ':00' : ''; }

    // A preset dropped on the calendar sits there as a placeholder while its
    // form is open, and is taken off again if the form is closed unsaved.
    var placeholder = null;

    // event is a calendar event to edit, or null; start, end, title and color
    // prefill a new one.
    function openEditor(event, prefill) {
        prefill = prefill || {};
        var props = event ? event.extendedProps : {};

        form.reset();
        form.action = event ? dialog.dataset.updateUrl : dialog.dataset.createUrl;
        dialog.querySelector('#eventDialogTitle').textContent = event ? 'Edit event' : 'New event';
        dialog.querySelector('[data-save-label]').textContent = event ? 'Save changes' : 'Save event';

        form.elements['id'].value = event ? props.eventId : '';
        form.elements['title'].value = event ? event.title : (prefill.title || '');
        form.elements['venue'].value = event ? (props.venue || '') : '';
        form.elements['org_dept'].value = event ? (props.orgDept || '') : '';
        form.elements['start'].value = toInput(event ? event.start : prefill.start);
        form.elements['end'].value = toInput(event ? event.end : prefill.end);
        form.elements['bg_color'].value = (event ? props.bgColor : prefill.color) || 'bg-primary';

        // The attendee list is only chosen when the event is created.
        createOnly.hidden = !!event;
        form.elements['emp_status'].disabled = !!event;

        deleteButton.hidden = !event;
        deleteButton.dataset.eventId = event ? props.eventId : '';
        deleteButton.dataset.eventTitle = event ? event.title : '';

        dialog.showModal();
        form.elements['title'].focus();
    }

    dialog.addEventListener('close', function () {
        if (placeholder) { placeholder.remove(); placeholder = null; }
    });
    form.addEventListener('submit', function () { placeholder = null; });

    // Drag-to-reschedule and resize save straight away; if the server refuses,
    // the event goes back where it was.
    function saveDates(info) {
        var event = info.event, props = event.extendedProps;
        var body = new FormData();
        body.append('id', props.eventId);
        body.append('title', event.title || '');
        body.append('venue', props.venue || '');
        body.append('org_dept', props.orgDept || '');
        body.append('bg_color', props.bgColor || 'bg-primary');
        body.append('start', toServer(event.start));
        body.append('end', toServer(event.end));

        fetch(dialog.dataset.updateUrl, { method: 'POST', headers: jsonHeaders, body: body })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function () { hrisToast('success', 'Event rescheduled.'); })
            .catch(function () {
                info.revert();
                hrisToast('error', 'The event could not be rescheduled. It has been put back.');
            });
    }

    var calendar = hrisEventCalendar(document.getElementById('calendar'), {
        // Click a day or drag across a range to add an event.
        selectable: true,
        selectMirror: true,
        select: function (info) {
            var end = info.end;
            // An all-day range ends on the morning after its last day; step back
            // to that day, and drop the end altogether for a single day.
            if (info.allDay && end) {
                end = new Date(end.getTime() - 86400000);
                if (end <= info.start) { end = null; }
            }
            openEditor(null, { start: info.start, end: end });
            calendar.unselect();
        },

        eventClick: function (info) {
            info.jsEvent.preventDefault();
            openEditor(info.event);
        },

        editable: true,
        eventDrop: saveDates,
        eventResize: saveDates,

        // A preset dragged in from the row above.
        droppable: true,
        eventReceive: function (info) {
            placeholder = info.event;
            openEditor(null, { start: info.event.start, title: info.event.title, color: info.event.extendedProps.bgColor });
        }
    });

    /* -------------------------------------------------------------- presets */
    var presets = document.getElementById('eventPresets');

    new FullCalendar.Draggable(presets, {
        itemSelector: '[data-preset]',
        eventData: function (preset) {
            var color = hrisEventColor(preset.dataset.color);
            return {
                title: preset.dataset.title,
                backgroundColor: color,
                borderColor: color,
                extendedProps: { bgColor: preset.dataset.color }
            };
        }
    });

    presets.addEventListener('click', function (event) {
        var preset = event.target.closest('[data-preset]');
        if (preset) { openEditor(null, { title: preset.dataset.title, color: preset.dataset.color }); }
    });

    document.querySelector('[data-event-new]').addEventListener('click', function () { openEditor(null); });

    /* --------------------------------------------------------------- delete */
    var deleteDialog = document.getElementById('eventDeleteDialog');
    var deleteUrl = "{{ route('eventDestroy', ['id' => ':id']) }}";

    deleteButton.addEventListener('click', function () {
        deleteDialog.querySelector('[data-delete-name]').textContent = deleteButton.dataset.eventTitle;
        deleteDialog.showModal();
    });

    document.getElementById('eventDeleteConfirm').addEventListener('click', function () {
        var id = deleteButton.dataset.eventId;
        deleteDialog.close();
        if (!id) return;

        fetch(deleteUrl.replace(':id', encodeURIComponent(id)), { method: 'POST', headers: jsonHeaders })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function (data) {
                if (data.status !== 200) { return Promise.reject(); }
                var gone = calendar.getEventById(id);
                if (gone) { gone.remove(); }
                dialog.close();
                hrisToast('success', 'Event deleted.');
            })
            .catch(function () { hrisToast('error', 'The event could not be deleted. Please try again.'); });
    });
});
</script>
@endpush
