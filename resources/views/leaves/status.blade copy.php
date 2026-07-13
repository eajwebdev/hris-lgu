@extends('layouts.master')

@section('body')
@include('leaves.style')
<style>
    .modal-content {
        background: rgba(255, 255, 255, 0.515);
        border: none;
        box-shadow: none;
    }
    
    .modal-backdrop {
        background-color: transparent;
    }
</style>

<section class="content">
<div id="loading-spinner" style="display: none; position: fixed; z-index: 9999; background: rgba(0,0,0,0.5); top: 0; left: 0; width: 100%; height: 100%; text-align: center;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
        <div class="spinner-border text-light" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</div>
    
<div class="container-fluid">
    <div class="row">
        @include("leaves.side-menu")
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    @include("leaves.top-menu")
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @php
                            $leaveTypes = [
                                1 => 'Vacation Leave',
                                2 => 'Mandatory/Forced Leave',
                                3 => 'Sick Leave',
                                4 => 'Maternity Leave',
                                5 => 'Paternity Leave',
                                6 => 'Special Privilege Leave',
                                7 => 'Solo Parent Leave',
                                8 => 'Study Leave',
                                9 => '10-Day VAWC Leave',
                                10 => 'Rehabilitation Privilege',
                                11 => 'Special Leave Benefits for Women',
                                12 => 'Special Emergency (Calamity) Leave',
                                13 => 'Adoption Leave',
                                14 => 'Vacation Service Credit'
                            ];

                            $leavedetails = [
                                1 => 'Within the Philippines',
                                2 => 'Abroad',
                                3 => 'In Hospital',
                                4 => 'Out Patient',
                                5 => "Completion of Master's Degree",
                                6 => 'BAR/Board Examination Review',
                                7 => 'Monetization of Leave Credits',
                                8 => 'Terminal Leave'
                            ];

                            $access = auth()->guard($guard)->user()->access;
                            $accesarray = explode(',', $access);
                        @endphp
                        <div class="tab-pane active" id="timeline">
                            @foreach($leavesapp as $leaves)
                                <div class="timeline timeline-inverse">
                                    <!-- Step 1 -->
                                    <div class="time-label">
                                        <span class="bg-success"><i class="fas fa-user-circle"></i> @if($guard == "web") {{ strtoupper($employee->lname) }}, {{ strtoupper($employee->fname) }} {{ strtoupper($employee->suffix) }}. {{ strtoupper($employee->mname) }} @else me @endif &emsp;</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-stamp bg-info"></i>
                                        <div class="timeline-item">
                                            <span class="time time-{{ $leaves->id }}">{{ (isset($leaves->date_filing)) ? \Carbon\Carbon::parse($leaves->date_filing)->format('F j, Y h:i A') : '' }}</span>
                                            <h3 class="timeline-header"><a href="#">Leave Application</a></h3>
                                            <div class="timeline-body">
                                                <button type="button" class="btn btn-danger btn-round btn-sm" style="float: right;" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModal">
                                                    <i class="fas fa-file-pdf"></i>
                                                </button>
                                                <span class="badge badge-success"><b>#{{ $leaves->transnum }}</b></span><br> 
                                                <span><b>TYPE OF LEAVE TO AVAILED OF :</b> {{ $leaveTypes[$leaves->leave_type] }}</span><br>
                                                <span><b>DETAILS OF LEAVE :</b> {{ $leavedetails[$leaves->leave_purpose] ?? null }} @if($leaves->leave_detail) ({{ $leaves->leave_detail }}) @endif</span><br>
                                                <span><b>INCLUSIVE DATES :</b> {{ $leaves->date_range }}</span><br>
                                                <span><b>DAYS :</b> {{ ($leaves->emp_esign == 0) ? $leaves->days : ($leaves->days + $leaves->holiday) }}</span><br>
                                                
                                                <span><b>DAYS WITH PAY :</b> <span id="days-wpay{{ $leaves->id }}">{{ ($leaves->emp_esign !== 0) ? $leaves->days - $leaves->day_wpay : '' }}</span></span><br>
                                                <span><b>DAYS WITHOUT PAY:</b> <span id="days-withoutpay{{ $leaves->id }}">{{ ($leaves->emp_esign !== 0) ? $leaves->day_wpay : '' }}<span> </span><br>
                                                <span><b>HOLIDAYS:</b> <span id="days-withoutpay{{ $leaves->id }}">{{ ($leaves->emp_esign !== 0) ? $leaves->holiday : 0 }}<span> </span>

                                                @if($guard == "web")
                                                    <div class="timeline-footer mb-4" id="action-button0{{ $leaves->id }}" style="margin-top: -15px;">
                                                        <div class="float-right">
                                                            @if($leaves->emp_esign == 0)
                                                                <button class="btn btn-info btn-sm day-wpay" data-id="{{ $leaves->id }}" data-max="{{ $leaves->days }}"><i class="fas fa-circle-info"></i></button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($guard == "employee" && $leaves->employid == auth()->guard($guard)->user()->id)
                                                    <div class="timeline-footer" id="action-button0{{ $leaves->id }}" style="margin-top: -15px;">
                                                        @if($leaves->emp_esign == 1)
                                                            <div class="float-right mb-4">
                                                                <button type="button" class="btn btn-warning btn-sm cancelLeave" value="{{ $leaves->id }}" ><i class="fas fa-times"></i> Cancel</button>
                                                                

                                                                <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="0" data-max="{{ $leaves->days }}"><i class="fas fa-upload"></i> Upload</button>
                                                            </div>
                                                        @elseif($leaves->emp_esign == 0 && $leaves->hr_sign == null)
                                                            <div class="float-right">
                                                                <button type="button" class="btn btn-warning btn-sm cancelLeave" value="{{ $leaves->id }}" ><i class="fas fa-times"></i> Cancel</button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    @if($leaves->emp_esign == 2 && $leaves->status == 1)
                                                        <button class="btn btn-primary btn-sm undo-leave text-black float-right" data-id="{{ $leaves->id }}" data-to="1"><i class="fas fa-undo"></i> Undo</button>
                                                    @endif
                                                @endif
                                                <br>
                                            </div>
                                        </div>
                                    </div>   
                                    
                                    <div>
                                        @if($leaves->remarks_stat == 1)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                        <i id="status-icon{{ $leaves->id }}" class="fas {{ ($leaves->status == 1) ? 'fa-times bg-secondary' : (($leaves->status == 2 || $leaves->status == 3 || $leaves->status == 4) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time time-hr{{ $leaves->id }}">{{ (!empty($leaves->hr_sdate)) ? \Carbon\Carbon::parse($leaves->hr_sdate)->format('F j, Y h:i A') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($leavesapp['0']->hr_lname) }}, {{ strtoupper($leavesapp['0']->hr_fname) }} {{ isset($leavesapp['0']->hr_suffix) ? strtoupper($leavesapp['0']->hr_suffix).'.' : '' }} {{ isset($setting->hr_mname) ? strtoupper(substr($setting->hr_mname, 0, 1)) . '.' : ''}}</a><br>
                                                <span><i>Head, HRMO</i></span>
                                                @if($leaves->remarks_stat == 1)<br> 
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->remarks_details }}</p>
                                                    </div>
                                                @endif
                                                <div id="status-remarks-hrmo{{ $leaves->id }}"></div>
                                            </h3>

                                            @if($guard == "employee" || $leaves->status == 2)
                                                <div class="timeline-footer mb-4" id="action-button{{ $leaves->id }}" style="margin-top: -15px;">
                                                    <div class="float-right">
                                                        <button class="btn btn-primary btn-sm undo-leave text-black" data-id="{{ $leaves->id }}" data-to="2"><i class="fas fa-undo"></i> Undo</button>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($guard == "web")
                                                @if($leaves->status == 1 && $leaves->remarks_stat != 1 && $leaves->emp_esign == 2 && $accesarray[7] == 1)
                                                    <div class="timeline-footer mb-4" id="action-button{{ $leaves->id }}" style="margin-top: -15px;">
                                                        <div class="float-right">
                                                            <button class="btn btn-warning btn-sm return-leave text-black" data-id="{{ $leaves->id }}" data-to="1"><i class="fas fa-undo"></i> Return</button>
                                                            <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="1" data-max="{{ $leaves->days }}"><i class="fas fa-check"></i> Approve</button>
                                                            {{-- <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="1"><i class="fas fa-ban"></i> Disapprove</button> --}}
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>  

                                    <!-- Step 2 -->
                                    <div>
                                        @if($leaves->remarks_stat == 2)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                            <i id="status-icon1{{ $leaves->id }}" class="fas {{ ($leaves->status == 1 || $leaves->status == 2) ? 'fa-times bg-secondary' : (($leaves->status == 3 || $leaves->status == 4 || $leaves->status == 5) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time time-sup{{ $leaves->id }}">{{ (!empty($leaves->sup_sdate)) ? \Carbon\Carbon::parse($leaves->sup_sdate)->format('F j, Y h:i A') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($leaves->supervisor_lname) }}, {{ strtoupper($leaves->supervisor_fname) }} {{ isset($leaves->supervisor_suffix) ? strtoupper($leaves->supervisor_suffix).'.' : '' }} {{ isset($leaves->supervisor_mname) ? strtoupper(substr($leaves->supervisor_mname, 0, 1)) . '.' : ''}}</a><br>
                                                <span><i>Immediate Supervisor</i></span>
                                                @if($leaves->remarks_stat == 2)<br>
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->remarks_details }}</p>
                                                    </div>
                                                @endif
                                                <div id="status-remarks-supervisor{{ $leaves->id }}"></div>
                                            </h3>

                                            @if($guard == "employee" || $leaves->status == 3)
                                                <div class="timeline-footer mb-4" id="action-button{{ $leaves->id }}" style="margin-top: -15px;">
                                                    <div class="float-right">
                                                        <button class="btn btn-primary btn-sm undo-leave text-black" data-id="{{ $leaves->id }}" data-to="2"><i class="fas fa-undo"></i> Undo</button>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($guard == "employee")
                                                @if($leaves->supervisor == auth()->guard($guard)->user()->id && $leaves->status == 2 && $leaves->remarks_stat !== 2 && $leaves->emp_esign == 2)
                                                    <div class="timeline-footer mb-4" id="action-button1{{ $leaves->id }}">
                                                        <div class="float-right">
                                                            <button class="btn btn-warning btn-sm return-leave text-black" data-id="{{ $leaves->id }}" data-to="2"><i class="fas fa-undo"></i> Return</button>
                                                            <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="2" data-max="{{ $leaves->days }}"><i class="fas fa-check"></i> Approve</button>
                                                            <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="2"><i class="fas fa-ban"></i> Disapprove</button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>     
                        
                                    <div>
                                        @if($leaves->remarks_stat == 3)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                            <i id="status-icon2{{ $leaves->id }}" class="fas {{ ($leaves->status == 1 || $leaves->status == 2 || $leaves->status == 3) ? 'fa-times bg-secondary' : (($leaves->status == 3 || $leaves->status == 4) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time time-pres{{ $leaves->id }}">{{ (!empty($leaves->pres_sdate)) ? \Carbon\Carbon::parse($leaves->pres_sdate)->format('F j, Y h:i A') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($setting->sucpres_lname) }}, {{ strtoupper($setting->sucpres_fname) }} {{ isset($setting->sucpres_suffix) ? strtoupper($setting->sucpres_suffix).'.' : '' }}</a><br>
                                                <span><i>SUC President</i></span>
                                                @if($leaves->remarks_stat == 3)<br>
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->remarks_details }}</p>
                                                    </div>
                                                @endif
                                                <div id="status-remarks-presedent{{ $leaves->id }}"></div>
                                            </h3>
                                            @if($guard == "employee")
                                                @if($setting->suc_pres == auth()->guard($guard)->user()->id && $leaves->status == 3 && $leaves->remarks_stat !== 3)
                                                    <div class="timeline-footer mb-4" id="action-button2{{ $leaves->id }}" style="margin-top: -15px;">
                                                        <div class="float-right">
                                                            <button class="btn btn-warning btn-sm return-leave text-black" data-id="{{ $leaves->id }}" data-to="3"><i class="fas fa-undo"></i> Return</button>
                                                            <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="3" data-max="{{ $leaves->days }}"><i class="fas fa-check"></i> Approve</button>
                                                            <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="3"><i class="fas fa-ban"></i> Disapprove</button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <i id="status-icon3{{ $leaves->id }}" class="fas {{ ($leaves->status == 4 || $leaves->status == 5) ? 'fa-check bg-success' : 'fa-times bg-secondary' }} mt-3"></i>
                                        <button data-id="{{ $leaves->id }}" data-toggle="modal" data-target="{{ ($leaves->history == 2) ? '#pdfModal' : '' }}" 
                                            id="preview{{ $leaves->id }}" 
                                            class="btn {{ ($leaves->status == 4 || $leaves->status == 5) ? 'btn-danger' : 'btn-secondary' }} btn-sm mt-3 ml-5 download">
                                            <i class="fas fa-file-pdf"></i> Preview
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                            @foreach($leavesapphead as $leaves)
                                <div class="timeline timeline-inverse">
                                    <!-- Step 1 -->
                                    <div class="time-label">
                                        <span class="bg-success"><i class="fas fa-user-circle"></i> {{ strtoupper($leaves->employee_lname) }}, {{ strtoupper($leaves->employee_fname) }} {{ strtoupper($leaves->employee_suffix) }}. {{ strtoupper($leaves->employee_mname) }}</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-stamp bg-info"></i>
                                        <div class="timeline-item">
                                            <span class="time time-{{ $leaves->id }}">{{ (isset($leaves->date_filing)) ? \Carbon\Carbon::parse($leaves->date_filing)->format('F j, Y h:i A') : '' }}</span>
                                            <h3 class="timeline-header"><a href="#">Leave Application</a></h3>
                                            <div class="timeline-body">
                                                <span class="badge badge-success"><b>#{{ $leaves->transnum }}</b></span><br>    
                                                <span><b>TYPE OF LEAVE TO AVAILED OF :</b> {{ $leaveTypes[$leaves->leave_type] }}</span><br>
                                                <span><b>DETAILS OF LEAVE :</b> {{ $leavedetails[$leaves->leave_purpose] ?? null }} @if($leaves->leave_detail) ({{ $leaves->leave_detail }}) @endif</span><br>
                                                <span><b>INCLUSIVE DATES :</b> {{ $leaves->date_range }}</span><br>
                                                <span><b>DAYS :</b> {{ $leaves->days }}</span><br>
                                                
                                                <span><b>DAYS WITH PAY :</b> <span id="days-wpay{{ $leaves->id }}">{{ ($leaves->emp_esign !== 0) ? $leaves->days - $leaves->day_wpay : '' }}</span></span><br>
                                                <span><b>DAYS WITHOUT PAY:</b> <span id="days-withoutpay{{ $leaves->id }}">{{ ($leaves->emp_esign !== 0) ? $leaves->day_wpay : '' }}<span> </span>
                                                
                                                @if($guard == "web")
                                                    <div class="timeline-footer mb-4" id="action-button0{{ $leaves->id }}" style="margin-top: -15px;">
                                                        <div class="float-right">
                                                            @if($leaves->emp_esign == 0)
                                                                <button class="btn btn-info btn-sm day-wpay" data-id="{{ $leaves->id }}" data-max="{{ $leaves->days }}"><i class="fas fa-circle-info"></i></button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($guard == "employee" && $leaves->employid == auth()->guard($guard)->user()->id)
                                                    <div class="timeline-footer" id="action-button0{{ $leaves->id }}" style="margin-top: -15px;">
                                                        @if($leaves->emp_esign == 1)
                                                            <div class="float-right">
                                                                <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="0" data-max="{{ $leaves->days }}"><i class="fas fa-upload"></i> Upload</button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                                <br>
                                            </div>
                                        </div>
                                    </div>   
                                    
                                    <div>
                                        @if($leaves->remarks_stat == 1)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                        <i id="status-icon{{ $leaves->id }}" class="fas {{ ($leaves->status == 1) ? 'fa-times bg-secondary' : (($leaves->status == 2 || $leaves->status == 3 || $leaves->status == 4) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time time-hr{{ $leaves->id }}">{{ (!empty($leaves->hr_sdate)) ? \Carbon\Carbon::parse($leaves->hr_sdate)->format('F j, Y h:i A') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($leavesapphead['0']->hr_lname) }}, {{ strtoupper($leavesapphead['0']->hr_fname) }} {{ isset($leavesapphead['0']->hr_suffix) ? strtoupper($leavesapphead['0']->hr_suffix).'.' : '' }} {{ isset($setting->hr_mname) ? strtoupper(substr($setting->hr_mname, 0, 1)) . '.' : ''}}</a><br>
                                                <span><i>Head, HRMO</i></span>
                                                @if($leaves->remarks_stat == 1)<br>
                                                    <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                        <p>{{ $leaves->remarks_details }}</p>
                                                    </div>
                                                @endif
                                                <div id="status-remarks-hrmo{{ $leaves->id }}"></div>
                                            </h3>
                                            @if($guard == "web")
                                                @if($leaves->status == 1 && $leaves->remarks_stat != 1 && $leaves->emp_esign == 2 && $accesarray[7] == 1)
                                                    <div class="timeline-footer mb-4" id="action-button{{ $leaves->id }}" style="margin-top: -15px;">
                                                        <div class="float-right">
                                                            <button type="button" class="btn btn-danger btn-sm" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModal"><i class="fas fa-file-pdf"></i> View</button>
                                                            <button class="btn btn-warning btn-sm return-leave text-black" data-id="{{ $leaves->id }}" data-to="1"><i class="fas fa-undo"></i> Return</button>
                                                            <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="1" data-max="{{ $leaves->days }}"><i class="fas fa-check"></i> Approve</button>
                                                            {{-- <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="1"><i class="fas fa-ban"></i> Disapprove</button> --}}
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div> 

                                    <!-- Step 2 -->
                                    <div>
                                        @if($leaves->remarks_stat == 2)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                            <i id="status-icon1{{ $leaves->id }}" class="fas {{ ($leaves->status == 1 || $leaves->status == 2) ? 'fa-times bg-secondary' : (($leaves->status == 3 || $leaves->status == 4 || $leaves->status == 5) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time time-sup{{ $leaves->id }}">{{ (!empty($leaves->sup_sdate)) ? \Carbon\Carbon::parse($leaves->sup_sdate)->format('F j, Y h:i A') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($leaves->supervisor_lname) }}, {{ strtoupper($leaves->supervisor_fname) }} {{ isset($leaves->supervisor_suffix) ? strtoupper($leaves->supervisor_suffix).'.' : '' }} {{ isset($leaves->supervisor_mname) ? strtoupper(substr($leaves->supervisor_mname, 0, 1)) . '.' : ''}}</a><br>
                                                <span><i>Immediate Supervisor</i></span>
                                                @if($leaves->remarks_stat == 2)<br>
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->remarks_details }}</p>
                                                    </div>
                                                @endif
                                                <div id="status-remarks-supervisor{{ $leaves->id }}"></div>
                                            </h3>
                                            @if($guard == "employee")
                                                @if($leaves->supervisor == auth()->guard($guard)->user()->id && $leaves->status == 2 && $leaves->remarks_stat !== 2 && $leaves->emp_esign == 2)
                                                    <div class="timeline-footer mb-4" id="action-button1{{ $leaves->id }}">
                                                        <div class="float-right">
                                                            <button type="button" class="btn btn-danger btn-sm" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModal"><i class="fas fa-file-pdf"></i> View</button>
                                                            <button class="btn btn-warning btn-sm return-leave text-black" data-id="{{ $leaves->id }}" data-to="2"><i class="fas fa-undo"></i> Return</button>
                                                            <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="2" data-max="{{ $leaves->days }}"><i class="fas fa-check"></i> Approve</button>
                                                            <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="2"><i class="fas fa-ban"></i> Disapprove</button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>   
                        
                                    <div>
                                        @if($leaves->remarks_stat == 3)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                            <i id="status-icon2{{ $leaves->id }}" class="fas {{ ($leaves->status == 1 || $leaves->status == 2 || $leaves->status == 3) ? 'fa-times bg-secondary' : (($leaves->status == 3 || $leaves->status == 4) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time time-pres{{ $leaves->id }}">{{ (!empty($leaves->pres_sdate)) ? \Carbon\Carbon::parse($leaves->pres_sdate)->format('F j, Y h:i A') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($setting->sucpres_lname) }}, {{ strtoupper($setting->sucpres_fname) }} {{ isset($setting->sucpres_suffix) ? strtoupper($setting->sucpres_suffix).'.' : '' }}</a><br>
                                                <span><i>SUC President</i></span>
                                                @if($leaves->remarks_stat == 3)<br>
                                                    <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                        <p>{{ $leaves->remarks_details }}</p>
                                                    </div>
                                                @endif
                                                <div id="status-remarks-presedent{{ $leaves->id }}"></div>
                                            </h3>
                                            @if($guard == "employee")
                                                @if($setting->suc_pres == auth()->guard($guard)->user()->id && $leaves->status == 3 && $leaves->remarks_stat !== 3)
                                                    <div class="timeline-footer mb-4" id="action-button2{{ $leaves->id }}" style="margin-top: -15px;">
                                                        <div class="float-right">
                                                            <button type="button" class="btn btn-danger btn-sm" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModal"><i class="fas fa-file-pdf"></i> View</button>
                                                            <button class="btn btn-warning btn-sm return-leave text-black" data-id="{{ $leaves->id }}" data-to="3"><i class="fas fa-undo"></i> Return</button>
                                                            <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="3" data-max="{{ $leaves->days }}"><i class="fas fa-check"></i> Approve</button>
                                                            <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="3"><i class="fas fa-ban"></i> Disapprove</button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <i id="status-icon3{{ $leaves->id }}" class="fas {{ ($leaves->status == 4 || $leaves->status == 5) ? 'fa-check bg-success' : 'fa-times bg-secondary' }} mt-3"></i>
                                        <button data-id="{{ $leaves->id }}" data-toggle="modal" data-target="{{ ($leaves->history == 2) ? '#pdfModal' : '' }}" 
                                            id="preview{{ $leaves->id }}" 
                                            class="btn {{ ($leaves->status == 4 || $leaves->status == 5) ? 'btn-danger' : 'btn-secondary' }} btn-sm mt-3 ml-5 download">
                                            <i class="fas fa-file-pdf"></i> Preview
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    
                    </div>                    
                </div>
            </div>                        
        </div>
    </div>
</div>
<div class="modal fade" id="pdfModal" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <iframe id="pdfIframe" src="" width="100%" height="600px" style="border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
</section>
@endsection
