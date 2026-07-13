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
                        <b>GOVERNMENT ISSUED ID</b>
                    </h2>
                </div>
                @php
                    $govid = explode(',', $govids->govid);
                @endphp
                <div class="card-body">
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-12"><p class="text-muted"><b>Government Issued ID (i.e.Passport, GSIS, SSS, PRC, Driver's License, etc.) PLEASE INDICATE ID Number and Date of Issuance</b></p>
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-check">
                                            <label class="badge badge-secondary w-100">Government Issued ID:</label><input class="input-details updated-data" type="text" name="govid_0" data-array="0" value="{{ $govid[0] }}" id="govid-0">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <label class="badge badge-secondary w-100">ID/License/Passport No.:</label><input class="input-details updated-data" type="text" name="govid_1" data-array="1" value="{{ $govid[1] }}" id="govid-1">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <label class="badge badge-secondary w-100">Date/Place of Issuance:</label><input class="input-details updated-data" type="text" name="govid_2" data-array="2" value="{{ $govid[2] }}" id="govid-2">
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