@extends('layouts.master')

@section('body')
@include('emp.style')
<section class="content">
<div class="container-fluid">
    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>SIGNATURE</b>
                    </h2>
                </div>  
                <div class="card-body">
                    <div class="d-flex justify-content-center">
                        <div class="col-md-4">
                            <img src="{{ asset('Uploads/esign-note.jpg') }}" alt="" srcset="" width="100%">
                            <hr>
                            <div class="p-1" style="border: 1px solid rgb(249, 237, 237);">
                                <img id="signature-preview" src="{{ $imageData }}" alt="E-SIGNATURE" width="100%" style="cursor: pointer;">
                                <label class="badge badge-secondary lbel w-100 text-center">E-SIGNATURE</label>
                            </div>
                        </div>
                    </div>
                    <input type="file" id="signature-file" accept="image/png" style="display: none;">
                </div>
            </div>                        
        </div>
    </div>
</div>
</section>
@endsection