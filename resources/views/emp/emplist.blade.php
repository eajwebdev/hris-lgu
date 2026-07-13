@extends('layouts.master')

@section('body')
<style>
    .custom-label {
        width: 45px;
        padding: 0px;
        padding-left: 5px;
        text-align: center; /* Center align the text */
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="card-header">
                        <div class="card-tools">
                            <a href="{{ route('empQr') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-qrcode"></i> 
                            </a>
                            <a href="{{ route('genEmp') }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-file-pdf"></i> 
                            </a>
                            <a href="{{ route('empAdd') }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-user-plus"></i> ADD NEW
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-collapsed table-hover" id="example1">
                                <thead>
                                    <tr>
                                        <th>NO.</th>
                                        <th>Full Name</th>
                                        <th>Emp_ID</th> 
                                        <th>Status</th>
                                        <th>Email</th>
                                        <th>Service</th>
                                        <th>Date Hired</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead> 
                                <tbody>
                                    @php $cnt = 1; @endphp
                                    @foreach ($employee as $emp)
                                    @php
                                        $hireDate = $emp->date_hired;
                                        $currentDate = date('Y-m-d'); 

                                        $startDate = new DateTime($hireDate);
                                        $endDate = new DateTime($currentDate);

                                        $interval = $startDate->diff($endDate);
                                        
                                        $years = $interval->y;
                                        $months = $interval->m;
                                    @endphp
                                        <tr id="tr-{{ $emp->id }}">
                                            <td>{{ $cnt++ }}</td>
                                            <td><b>{{ $emp->lname }}, {{ $emp->fname }} {{ $emp->suffix }} {{ isset($emp->mname) ? strtoupper(substr($emp->mname, 0, 1)).'.' : '' }}</b><br><i>{{ $emp->position}}</i> </td>
                                            <td>{{ $emp->emp_ID}}</td>
                                            <td>
                                            @if($emp->partime_rate > 0)
                                                Part-time/JO
                                            @elseif($emp->emp_status == 2)
                                                {{ $emp->status_name }} ({{ $emp->qual }})
                                            @else
                                                {{ $emp->status_name }}
                                            @endif
                                            </td>
                                            <td>{{ $emp->org_email }}</td>
                                            <td>{{ $years.' years' .' '. $months. ' months' }}</td>
                                            <td>{{ isset($hireDate) ? date('F d, Y', strtotime($hireDate)) : '' }}</td>
                                            <td class="text-center">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox"
                                                        class="custom-control-input"
                                                        onchange="openToggleDialog(this, '{{ $emp->fname }} {{ $emp->mname }} {{ $emp->lname }}', {{ $emp->id }})"
                                                        id="switch{{ $emp->id }}"
                                                        {{ $emp->stat_1 == 1 ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="switch{{ $emp->id }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <div class='d-flex align-items-center'>
                                                    @if($emp->emp_status == 1)
                                                    <a href="{{ route('leavesRead', $emp->id) }}" title="Leave Credits" class='btn btn-success btn-xs employee_edit mr-1' style='width: 30px;' value="{{ $emp->id }}">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </a>
                                                    @else
                                                    <a href="#" title="Leave Credits" class='btn btn-secondary btn-xs employee_edit mr-1' style='width: 30px;' value="{{ $emp->id }}">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </a>
                                                    @endif
                                                    <a href="{{ route('PDS', $emp->id) }}" title="PDS" class='btn btn-info btn-xs employee_edit mr-1' style='width: 30px;' value="{{ $emp->id }}">
                                                        <i class='fas fa-file-alt'></i>
                                                    </a>
                                                    <a title="Working Hours" data-toggle="modal" data-target="#officialTime" onclick="OfficialTime('{{ $emp->emp_ID }}')" class='btn btn-primary btn-xs mr-1' style='width: 30px;'>
                                                        <i class='fas fa-clock'></i>
                                                    </a>
                                                    {{-- <button type='button' class='btn btn-danger btn-xs employee_delete' style='width: 30px;' value="{{ $emp->id }}">
                                                        <i class='fas fa-trash'></i>
                                                    </button> --}}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody> 
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="officialTime">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">    
            <div class="card-header">
                <h2 class="card-title text-success1">
                    <b>OFFICIAL WORKING HOURS</b>
                </h2>
            </div>        
            <div class="card-body bg-form">
                <form class="form-horizontal add-form" action="{{ route('OfficialTimeCreate') }}" method="POST">
                    @csrf
                    <div class="form-group mtop">
                        {{-- Monday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <input type="hidden" name="empid" class="form-control form-control-sm">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>MON.</b></span>
                                        </div>
                                        <input type="time" name="mon_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="mon_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="mon_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="mon_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        {{-- Tuesday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>TUE.</b></span>
                                        </div>
                                        <input type="time" name="tue_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="tue_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="tue_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="tue_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        {{-- Wendesday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>WED.</b></span>
                                        </div>
                                        <input type="time" name="wed_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="wed_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="wed_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="wed_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        {{-- Thursday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>THU.</b></span>
                                        </div>
                                        <input type="time" name="thu_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="thu_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="thu_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="thu_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        {{-- Thursday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label custom-label"><b>FRI.</b></span>
                                        </div>
                                        <input type="time" name="fri_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="fri_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="fri_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="fri_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        
                        <div class="form-row" style="float: right;">
                            <div class="col-md-12">
                                <button class="btn btn-success"><i class="fas fa-save"></i> SAVE</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="toggleConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Confirm Action</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="confirmMessage" style="font-size: 16px;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmToggle">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
<script>
let pendingCheckbox = null;
let pendingEmpId = null;
let pendingNewState = null;
function openToggleDialog(checkbox, fullname, empId) {
    // Save original values
    pendingNewState = checkbox.checked;
    pendingEmpId = empId;
    pendingCheckbox = checkbox;
    // Immediately revert so UI does NOT visually toggle yet
    checkbox.checked = !pendingNewState;
    const action = pendingNewState ? "enable" : "disable";
    document.getElementById("confirmMessage").innerHTML =
        "Are you sure you want to <b>" + action + "</b> this employee's account?<br><br>" +
        "<span class='text-dark font-weight-bold' style='font-size:18px;'>" + fullname + "</span>";
    $("#toggleConfirmModal").modal("show");
}
document.getElementById("confirmToggle").onclick = function () {
    $("#toggleConfirmModal").modal("hide");
    // Now apply the new intended state
    pendingCheckbox.checked = pendingNewState;
    toggleStat(pendingNewState, pendingEmpId);
    pendingCheckbox = null;
};
</script>
@endsection