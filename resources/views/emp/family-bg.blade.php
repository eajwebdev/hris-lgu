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
                        <b>FAMILY BACKGROUND</b>
                    </h2>
                </div>
                <div class="card-body bg-form">
                        <div class="form-group mtop">
                            <div class="form-row lbel">
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Spouse Surname</label>
                                    <input type="text" value="{{ $familyBg->spouse_sname }}" name="spouse_sname" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Spouse First Name</label>
                                    <input type="text" value="{{ $familyBg->spouse_fname }}" name="spouse_fname" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Spouse Middle Name</label>
                                    <input type="text" value="{{ $familyBg->spouse_mname }}" name="spouse_mname" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Spouse Extension</label>
                                    <input type="text" value="{{ $familyBg->spouse_ext }}" name="spouse_ext" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            </div>
                            
                            @php
                                $names = explode(',', $familyBg->name_child);
                                $dates = explode(',', $familyBg->date_birth);
                            @endphp
                            
                            <div id="form-container">
                                @foreach($names as $index => $name)
                                    @if(isset($dates[$index]))
                                        <div class="form-row mt-3 lbel" data-index="{{ $index }}">
                                            <div class="col-md-6">
                                                @if($loop->first)<label class="badge badge-secondary text-wrap lbel w-100">Child's Name</label>@endif
                                                <input type="text" value="{{ trim($name) }}" name="name_child[]" class="form-control form-control-sm update-child update-field-array" data-index="{{ $index }}" placeholder="N/A">
                                            </div>
                                            
                                            <div class="col-md-5">
                                                @if($loop->first)<label class="badge badge-secondary text-wrap lbel w-100">Date of Birth</label>@endif
                                                <input type="date" value="{{ trim($dates[$index]) }}" name="date_birth[]" class="form-control form-control-sm update-child update-field-array" data-index="{{ $index }}" placeholder="N/A">
                                            </div>
                                            
                                            @if($index > 0)
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete">
                                                        <i class="fas fa-trash fa-sm"></i>
                                                    </button>    
                                                </div>
                                            @endif
                                            
                                            @if($loop->first) 
                                                <div class="col-md-1">
                                                    <button id="add-row-familybg" class="btn btn-success btn-sm" style="margin-top: 21px;">
                                                        <i class="fas fa-plus fa-sm"></i>
                                                    </button>    
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>                            
                            
                            <div class="form-row mt-3 lbel">
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Occupation</label>
                                    <input type="text" value="{{ $familyBg->occupation }}" name="occupation" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Business Name</label>
                                    <input type="text" value="{{ $familyBg->bus_name }}" name="bus_name" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Business Address</label>
                                    <input type="text" value="{{ $familyBg->bus_address }}" name="bus_address" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Telephone</label>
                                    <input type="text" value="{{ $familyBg->telephone }}" name="telephone" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            </div>
                            
                            <div class="form-row mt-3">
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Father's Surname</label>
                                    <input type="text" value="{{ $familyBg->father_sname }}" name="father_sname" data-column-id="{{ $empid }}" data-column-name="father_sname" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Father's First Name</label>
                                    <input type="text" value="{{ $familyBg->father_fname }}" name="father_fname" data-column-id="{{ $empid }}" data-column-name="father_fname" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Father's Middle Name</label>
                                    <input type="text" value="{{ $familyBg->father_mname }}" name="father_mname" data-column-id="{{ $empid }}" data-column-name="father_mname" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary text-wrap lbel">Father's Extension</label>
                                    <input type="text" value="{{ $familyBg->father_ext }}" name="father_ext" data-column-id="{{ $empid }}" data-column-name="father_ext" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            </div>
                    
                            <div class="form-row mt-3">
                                <div class="col-md-3">
                                    <span class="text-success1">MOTHE'S MAIDEN NAME</span><br>
                                    <label class="badge badge-secondary text-wrap lbel">Mother's Surname</label>
                                    <input type="text" value="{{ $familyBg->mother_sname }}" name="mother_sname" data-column-id="{{ $empid }}" data-column-name="mother_sname" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3"><br>
                                    <label class="badge badge-secondary text-wrap lbel">Mother's First Name</label>
                                    <input type="text" value="{{ $familyBg->mother_fname }}" name="mother_fname" data-column-id="{{ $empid }}" data-column-name="mother_fname" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3"><br>
                                    <label class="badge badge-secondary text-wrap lbel">Mother's Middle Name</label>
                                    <input type="text" value="{{ $familyBg->mother_mname }}" name="mother_mname" data-column-id="{{ $empid }}" data-column-name="mother_mname" class="form-control form-control-sm update-field" placeholder="N/A">
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