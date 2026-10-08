@extends('layouts.app')

@php
    // The two folders: [short name, what it stands for, what is inside, link, open to this account]
    $folders = [
        ['OPCR', 'Office Performance Commitment and Review',
         'An office\'s targets for a semester, cascaded to its employees and rated at the end of it.',
         route('spms.opcr'), $isHead],
        ['IPCR', 'Individual Performance Commitment and Review',
         $isHead
            ? 'Each employee\'s own targets, accomplishments and ratings, and the printable rating form.'
            : 'Your own targets, accomplishments and ratings, and the printable rating form.',
         route('spms.ipcr'), true],
    ];
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">SPMS</h1>
            <p class="mt-1 text-cream/70">Strategic Performance Management System: commitments, accomplishments and ratings.</p>
        </div>
        @include('spms.partials.tabs')
    </div>
@endsection

@section('body')
<div class="grid gap-4 md:grid-cols-2">
    @foreach($folders as [$short, $long, $inside, $url, $open])
        @php
            $tag = $open ? 'a' : 'div';
        @endphp
        <{{ $tag }} @if($open) href="{{ $url }}" @else aria-disabled="true" @endif
            class="group flex flex-col rounded-2xl border bg-surface p-6 {{ $open ? 'border-line transition-colors hover:border-forest-600/40 focus-visible:outline-2 focus-visible:outline-sun-500' : 'border-dashed border-line' }}">
            <span class="flex items-start justify-between gap-4">
                <span class="grid size-14 place-items-center rounded-2xl text-2xl {{ $open ? 'bg-sun-100 text-sun-700' : 'bg-line/60 text-ink/40' }}">
                    <i class="fas {{ $open ? 'fa-folder' : 'fa-lock' }}"></i>
                </span>
                @unless($open)
                    <span class="rounded-full bg-line/60 px-2.5 py-1 text-xs font-medium text-ink/65">Office heads only</span>
                @endunless
            </span>

            <span class="mt-5 block font-display text-2xl font-semibold tracking-tight {{ $open ? '' : 'text-ink/50' }}">{{ $short }}</span>
            <span class="block text-ink/60">{{ $long }}</span>
            <span class="mt-3 block leading-relaxed text-ink/70">{{ $inside }}</span>

            @if($open)
                <span class="mt-5 inline-flex items-center gap-2 font-medium text-forest-700">
                    Open <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
                </span>
            @else
                <span class="mt-5 block text-ink/55">OPCR is reserved for office heads and HR. Your own commitments are under IPCR.</span>
            @endif
        </{{ $tag }}>
    @endforeach
</div>
@endsection
