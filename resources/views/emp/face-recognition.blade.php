@extends('layouts.master')

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Face Registration</h1>
            <p class="mt-1 max-w-2xl text-cream/70">Enrol a face for the attendance kiosk: front, left, right, then a little movement.</p>
        </div>

        @unless(\App\Http\Middleware\EnsureFaceRegistrar::allows())
            <a href="{{ route('dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-white px-4 font-semibold text-forest-900 shadow-md shadow-forest-950/25 transition-colors [text-shadow:none] hover:bg-cream focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        @endunless
    </div>
@endsection

@section('body')
@include('emp.style')
@php
    // Admin and HR arrive here from a PDS they are already working through, so
    // they keep that navigation. An employee arrives from their dashboard: the
    // PDS submenu is HR's record-keeping context, and framing enrolment inside
    // it is what made face registration look like part of an employee's
    // personal information form, which it is not.
    $inPdsContext = \App\Http\Middleware\EnsureFaceRegistrar::allows();
@endphp
<section class="content">
<div class="container-fluid">
    <div class="row">
        @if($inPdsContext)
            @include('emp.submenu-side')
            <div class="col-lg-9">
        @else
            <div class="col-lg-10 offset-lg-1">
        @endif
            {{-- The status panel and the webcam modal. Reaching this page at all
                 already required the face.self middleware, so there is no
                 unauthorised case to render here. --}}
            @include('emp.face-registration')
            </div>
    </div>
</div>
</section>
@endsection
