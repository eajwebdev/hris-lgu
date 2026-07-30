@extends('layouts.master')

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
                <div class="d-flex align-items-center mb-3">
                    <a href="{{ route('dashboard') }}" class="text-muted mr-3" title="Back to dashboard">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h5 class="mb-0 font-weight-bold">Face Registration</h5>
                </div>
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
