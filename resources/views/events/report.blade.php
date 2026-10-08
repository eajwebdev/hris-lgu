@extends('layouts.app')

@php
    $field = 'mt-1 block h-10 w-full rounded-xl border border-line bg-paper pr-8 pl-3 text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';

    // Set once a report has been asked for (EventController::searchReport).
    $generated = isset($eventid, $statusid);
@endphp

@section('breadcrumb', 'Reports')

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Events</h1>
            <p class="mt-1 text-cream/70">Who clocked in and out of an event, as a printable list.</p>
        </div>
        @include('events.partials.tabs')
    </div>
@endsection

@section('body')
<form action="{{ route('searchReport') }}" method="POST" data-generates-pdf
      class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4">
    @csrf

    <div class="w-full sm:w-80">
        <label for="reportEvent" class="{{ $label }}">Event</label>
        <select name="eventid" id="reportEvent" required class="{{ $field }}">
            @forelse($events as $event)
                <option value="{{ $event->id }}" @selected($generated && $event->id == $eventid)>
                    {{ ucfirst($event->title) }} ({{ \Carbon\Carbon::parse($event->start)->format('M j, Y') }})
                </option>
            @empty
                <option value="" disabled selected>No events yet</option>
            @endforelse
        </select>
    </div>

    <div class="w-full sm:w-56">
        <label for="reportStatus" class="{{ $label }}">Employee status</label>
        <select name="statusid" id="reportStatus" required class="{{ $field }}">
            <option value="0" @selected($generated && $statusid == 0)>All</option>
            @foreach ($status as $st)
                <option value="{{ $st->id }}" @selected($generated && $statusid == $st->id)>{{ $st->status_name }}</option>
            @endforeach
        </select>
    </div>

    @include('partials.generate-button')
</form>

@include('partials.pdf-preview', [
    'pdfUrl' => $generated ? route('reportGenrate', ['eventid' => $eventid, 'statusid' => $statusid]) : null,
    'working' => 'Generating the attendance report',
    'prompt' => $events->isEmpty()
        ? 'There are no events yet. Add one on the Calendar tab first.'
        : 'Choose an event and an employee status, then select Generate.',
])
@endsection
