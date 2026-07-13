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
                        <b>REFERENCES</b>
                    </h2>
                </div>
                @php
                    $refname = explode(';', $references->refname);
                    $refadd = explode(';', $references->refadd);
                    $reftelno = explode(';', $references->reftelno);
                @endphp
                <div class="card-body">
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-12"><p class="text-muted"><b>REFERENCES (Person not related by consanguinity or affinity to applicant /appointee)</b></p>
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-check">
                                            <label class="badge badge-secondary w-100">NAME</label><input class="input-details updated-data" type="text" name="refname_0" data-array="0" value="{{ $refname[0] }}" id="refname-0">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <label class="badge badge-secondary w-100">ADDRESS</label><input class="input-details updated-data" type="text" name="refadd_0" data-array="0" value="{{ $refadd[0] }}" id="refadd-0">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <label class="badge badge-secondary w-100">TELEPHONE NO.</label><input class="input-details updated-data" type="text" name="reftelno_0" data-array="0" value="{{ $reftelno[0] }}" id="reftelno-0">
                                        </div>
                                    </div>


                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="input-details updated-data" type="text" name="refname_1" data-array="1" value="{{ $refname[1] }}" id="refname-1">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="input-details updated-data" type="text" name="refadd_1" data-array="1" value="{{ $refadd[1] }}" id="refadd-1">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="input-details updated-data" type="text" name="reftelno_1" data-array="1" value="{{ $reftelno[1] }}" id="reftelno-1">
                                        </div>
                                    </div>

                                    
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="input-details updated-data" type="text" name="refname_2" data-array="2" value="{{ $refname[2] }}" id="refname-2">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="input-details updated-data" type="text" name="refadd_1" data-array="2" value="{{ $refadd[2] }}" id="refadd-2">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="input-details updated-data" type="text" name="reftelno_2" data-array="2" value="{{ $reftelno[2] }}" id="reftelno-2">
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>                    
                </div>
            </div>                        
        </div>
    </div>
</div>
</section>
@endsection