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
                        <b>OTHER INFORMATION</b>
                    </h2>
                </div>
                <div class="card-body bg-form">
                    <div class="form-group mtop">
                        @php
                            $skillshob = explode(',', $otherinfo->skills_hob);
                            $recognition = explode(',', $otherinfo->recognition);
                            $memorg = explode(',', $otherinfo->mem_org);
                        @endphp
                        
                        <div id="form-container">
                            @foreach($skillshob as $index => $name)
                                @if(isset($memorg[$index]))
                                    <div class="form-row mt-3 lbel" data-index="{{ $index }}">
                                        <div class="col-md-3">
                                            @if($loop->first)<label class="badge badge-secondary text-wrap lbel w-100">Special Skills and Hobbies</label>@endif
                                            <input type="text" value="{{ trim($name) }}" name="skills_hob[]" class="form-control form-control-sm update-child update-field-array" data-index="{{ $index }}" placeholder="N/A">
                                        </div>
                    
                                        <div class="col-md-4">
                                            @if($loop->first)<label class="badge badge-secondary text-wrap lbel w-100">Non-Academic Dstinctions / Recognition (Write in Full)</label>@endif
                                            <input type="text" value="{{ trim($recognition[$index]) }}" name="recognition[]" class="form-control form-control-sm update-child update-field-array" data-index="{{ $index }}" placeholder="N/A">
                                        </div>
                                        
                                        <div class="col-md-4">
                                            @if($loop->first)<label class="badge badge-secondary text-wrap lbel w-100">Membership in Association / Organization (Write in Full)</label>@endif
                                            <input type="text" value="{{ trim($memorg[$index]) }}" name="mem_org[]" class="form-control form-control-sm update-child update-field-array" data-index="{{ $index }}" placeholder="N/A">
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
                    </div>                    
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection