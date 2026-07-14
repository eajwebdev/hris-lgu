@extends('layouts.master')

@section('body')
@include('emp.style')
<section class="content">
<div class="container-fluid">
    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            {{-- The status panel and the webcam modal. Reaching this page at all
                 already required the face.registrar middleware, so there is no
                 unauthorised case to render here. --}}
            @include('emp.face-registration')
        </div>
    </div>
</div>
</section>
@endsection
