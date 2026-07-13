@extends('layouts.master')

@section('body')
@include('leaves.style')
<section class="content">
<div class="container-fluid">
    <div class="row">
        @include("leaves.side-menu")
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    @include("leaves.top-menu")
                </div>
                <div class="card-body">
                @if($guard == "web")
                    @if(count($leaves) == 0)
                    <div class="form-row lbel">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-12 ">
                                    <div class="card  p-4">
                                        <h2 class="text-warning font-weight-bold text-center">Input Leave Credit Balance to Start</h2>
                                        <p class="text-muted text-center">Please enter employee leave credit balance below to proceed.</p>
                                        <form class="form-horizontal" action="{{ route('leavesCreate') }}" method="POST">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-3 col-sm-4 mb-3"></div>
                                        
                                                <div class="col-md-3 col-sm-4 mb-3">
                                                    <div class="form-check">
                                                        <label class="badge badge-secondary">Sick Leave</label>
                                                        <input type="hidden" name="empid" value="{{ $employee->id }}">
                                                        <input class="form-control form-control-sm" type="number" name="sl" step="0.001" min="0" max="{{ (count($leaves) == 0) ? '' : 30 }}" placeholder="0.00" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-3 col-sm-4">
                                                    <div class="form-check">
                                                        <label class="badge badge-secondary">Vacation Leave</label>
                                                        <input class="form-control form-control-sm" type="number" name="vl" step="0.001" min="0" required>
                                                    </div>
                                                </div>
                                        
                                                <div class="col-md-3 col-sm-4"></div>
                                        
                                                <div class="col-md-3"></div>

                                                <div class="col-md-6 col-sm-4 mb-3">
                                                    <div class="form-check">
                                                        <label class="badge badge-secondary">Remarks</label>
                                                        <textarea class="form-control form-control-sm" type="text" name="remarks" step="0.001" rows="3"></textarea>
                                                    </div>
                                                </div>

                                                <div class="col-md-6"></div>
                                                
                                                <div class="col-md-3 text-right">
                                                    <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-save"></i> submit
                                                    </button>
                                                </div>
                                        
                                                <div class="col-md-3"></div>
                                            </div>
                                        </form>
                                    </div>
                                </div>                                                             
                                <div class="col-3">
                       
                                </div>
                            </div>
                        </div>
                    </div>    
                    @else
                    <button class="btn btn-sm btn-info float-right mb-2" data-toggle="modal" data-target="#leaveModal"><i class="fas fa-plus"></i></button>
                    <button class="btn btn-sm btn-warning float-right mb-2 mr-1" data-toggle="modal" data-target="#leaveModalDeduct"><i class="fas fa-minus"></i></button>
                    <div class="table-responsive ">
                        <table class="table table-collapsed table-hover" id="example3">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>VL</th>
                                    <th>For the Month of</th>
                                    <th>Remarks</th>
                                    <th>Date</th>
                                    <th></th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead> 
                            <tbody>
                                @foreach($leaves as $leave)
                                @php $date = ($leave->created_at) ? \Carbon\Carbon::parse($leave->created_at)->format('F d, Y') : '' @endphp
                                    <tr id="tr-{{ $leave->id }}">
                                        <td class="text-center">{{ $leave->earn_sl }}</td>
                                        <td class="text-center">{{ $leave->earn_vl }}</td>
                                        <td>{{ \Carbon\Carbon::parse($leave->date)->format('F Y') }}</td>
                                        <td>{{ $leave->remarks }}</td>
                                        <td>{{ $date }}</td>
                                        <td class="text-center">@if($leave->stat == 0) <span class="badge badge-warning">(starting Balance)</span> @elseif($leave->stat == 1 && $leave->days == 0) <span class="badge badge-danger">deducted</span> @else <span class="badge badge-success">addedd</span> @endif</td>
                                        <td  width="100" class="text-center">
                                            <a href="#" class="btn btn-info btn-sm mb-2 leaves_edit" data-id="{{ $leave->id }}" title="Edit" data-toggle="modal" data-target="{{ ($leave->stat == 1 && $leave->days == 0) ?  '#leaveModalDeductEdit ' : '#leaveEditModal' }}  ">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button class="btn {{ ($leave->stat == 0) ? 'btn-secondary' : 'btn-danger leaves_delete' }} btn-sm mb-2" value="{{ $leave->id }}" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr> 
                                @endforeach
                            </tbody>
                        </table>                    
                    </div>
                    @endif
                @else
                <form class="form-horizontal add-form" action="{{ route('LeaveAppCreate') }}" method="POST">
                    @csrf
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-6">
                                <label class="badge badge-secondary lbel">TYPE OF LEAVE TO AVAILED OF</label><br>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="1" name="leave_type" id="vacation-leave" required>
                                    <label class="form-check-label" for="vacation-leave">
                                        <b>Vacation Leave</b><span class="ft">(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span>
                                    </label>
                                    <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="2" name="leave_type" required>
                                    <label class="form-check-label" for="radio2">
                                        <b>Mandatory/Forced Leave</b> <span class="ft">(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="3" name="leave_type" id="sick-leave" required>
                                    <label class="form-check-label" for="sick-leave">
                                        <b>Sick Leave</b> <span class="ft">(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="4" name="leave_type" disabled required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Maternity Leave</b> <span class="ft">(R.A No. 11210/IRR issued by CSC, DOLE and SSS)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="5" name="leave_type" disabled required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Paternity Leave</b> <span class="ft">(R.A No. 8187/CSC MC No. 71,s. 1998, as amended)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="6" name="leave_type" required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Special Privilege Leave</b> <span class="ft">(Sec. 21, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="7" name="leave_type" disabled required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Solo Parent Leave</b> <span class="ft">(R.A. No. 8972/CSC MC No. 8, s. 2004)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="15" name="leave_type" required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Wellness Leave</b> <span class="ft"></span>
                                    </label>
                                </div>
                            </div>   
                            <div class="col-md-6"><br>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="8" name="leave_type" id="study-leave" disabled required>
                                    <label class="form-check-label" for="study-leave">
                                        <b>Study Leave</b><span class="ft">(Sec. 68, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="9" name="leave_type" disabled required>
                                    <label class="form-check-label" for="radio3">
                                        <b>10-Day VAWC Leave</b> <span class="ft">(R.A No. 9262/CSC MO No. 15,s. 2005)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="10" name="leave_type" disabled required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Rehabilitation Privilege</b> <span class="ft">(Sec. 55, Rule XVI, omnibus Rules Implementing E.O No. 292)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="11" name="leave_type" disabled required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Special Leave Benefits for Women</b> <span class="ft">(R.A No. 9710/CSC MC No. 25,s. 2010)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="12" name="leave_type" disabled required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Special Emergency (Calamity) Leave</b> <span class="ft">(CSC MC No. 2,s. 2012, as amended)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="13" name="leave_type" disabled required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Adoption Leave</b> <span class="ft">(R.A. No. 8552)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input leave-type" type="radio" value="14" name="leave_type" required>
                                    <label class="form-check-label" for="radio3">
                                        <b>Vacation Service Credit</b> <span class="ft">(R.A. No. 4670)</span>
                                    </label>
                                </div>
                            </div> 
                            <div class="col-md-6">
                                <label class="badge badge-secondary lbel mt-2">DETAILS OF LEAVE</label><br>
                                <i>In case of Vacation/Special Privilege Leave</i>
                                <div class="form-check w-100">
                                    <input class="form-check-input vacation-check" type="radio" value="1" name="leave_purpose" required disabled>
                                    <label class="form-check-label" for="within-philippines">
                                        <b>Within the Philippines</b>
                                    </label>
                                </div>
                                <div class="form-check w-100">
                                    <input class="form-check-input vacation-check" type="radio" value="2" name="leave_purpose" id="abroad" required disabled>
                                    <label class="form-check-label" for="abroad">
                                        <b>Abroad (Specify)</b>
                                        <input class="input-details vacation-leave ml-5" type="text" id="leaves_1" name="leave_detail[]" autocomplete="off" >
                                    </label>
                                </div>                                   
                                <i>In case of Sick Leave</i>
                                <div class="form-check w-100">
                                    <input class="form-check-input sick-leave-detail" type="radio" value="3" name="leave_purpose" id="in-hospital" required disabled>
                                    <label class="form-check-label" for="in-hospital">
                                        <b>In Hospital (Specify Illness)</b>
                                    </label>
                                </div>
                                <div class="form-check w-100">
                                    <input class="form-check-input sick-leave-detail" type="radio" value="4" name="leave_purpose" id="out-patient" required disabled>
                                    <label class="form-check-label" for="out-patient">
                                        <b>Out Patient (Specify Illness)</b> <input class="input-details sick-leave ml-2" type="text" id="leaves_2" name="leave_detail[]">
                                    </label>
                                </div>
                            </div>  
                            <div class="col-md-6">
                                <br>
                                <i>In case of Study Leave</i>
                                <div class="form-check w-100">
                                    <input class="form-check-input leave-check" type="radio" value="5" name="leave_purpose" required disabled>
                                    <label class="form-check-label" for="radio1">
                                        <b>Completion of Master's Degree</b>
                                    </label>
                                </div>
                                <div class="form-check w-100">
                                    <input class="form-check-input leave-check" type="radio" value="6" name="leave_purpose" required disabled>
                                    <label class="form-check-label" for="radio1">
                                        <b>BAR/Board Examination Review</b>
                                        <input class="input-details study-leave ml-2" type="text" id="leaves_3" name="leave_detail[]" autocomplete="off" >
                                    </label>
                                </div>
                                <i>Other Purpose</i>
                                <input class="form-check-input" type="radio" value="" name="leave_purpose" style="display: none;" checked id="monetizationdefault">    
                                <div class="form-check w-100 purpose-detail">
                                    <input class="form-check-input" type="radio" value="7" name="leave_purpose" id="monetization" disabled>
                                    
                                    <label class="form-check-label" for="monetization">
                                        <b>Monetization of Leave Credits</b>
                                    </label>
                                </div> 
                                <div class="form-check w-100 purpose-detail">
                                    <input class="form-check-input" type="radio" value="8" name="leave_purpose" id="terminal-leave" disabled>
                                    <label class="form-check-label" for="terminal-leave">
                                        <b>Terminal Leave</b> <input class="input-details ml-5" type="text" id="leaves_4" name="leave_detail[]" autocomplete="off" >
                                    </label>
                                </div>
                            </div>  
                            <div class="col-md-6">
                                <label class="badge badge-secondary text-wrap text-center lbel mb-1 mt-2">INCLUSIVE DATES</label>
                                <div style="display: flex; justify-content: space-between;">
                                    <input type="text" id="date_range" name="date_range" class="form-control form-control-sm" placeholder="Select Date Range" required>
                                </div>
                            </div>  

                            <div class="col-md-3">
                                <label class="badge badge-secondary text-wrap text-center lbel mb-1 mt-2">DAYS APPLIED</label>
                                <input type="text" id="day" name="days" class="form-control form-control-sm" autocomplete="off" style="flex: 1; margin-right: 5px;" readonly>
                            </div>         
                            <div class="col-md-3">
                                <label class="badge badge-secondary text-wrap text-center lbel mb-1 mt-2">DATE OF FILING</label>
                                <input type="date" name="date_filing" class="form-control form-control-sm" value="{{ \Carbon\Carbon::now()->toDateString() }}" readonly>
                            </div>                                     
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success float-right">Submit</button>
                </form>                
                @endif
            </div>                        
        </div>
    </div>
</div>
</section>
@include("leaves.modal")
@endsection