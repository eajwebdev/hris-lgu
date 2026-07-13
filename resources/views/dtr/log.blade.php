@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="row" style="padding-top: 10px;">
        <div class="col-md-2">
            @include('dtr.submenu')
        </div>
        <div class="col-lg-10">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>LOGS</b>
                    </h2>
                </div>
                <div class="card-body">
                    <form class="form-horizontal add-form" action="{{ route('dtrLogspost') }}" method="POST">
                        @csrf
                        <div class="form-group mtop">
                            <div class="form-row">
                                @if($guard == "web")
                                    <div class="col-md-3 col-sm-12">
                                        <label class="badge badge-secondary lbel">Employee Name</label><br>
                                        <select class="form-control form-control-sm {{ (auth()->guard($guard)->user()->role == "employee") ? '' : 'select2' }}" name="employee" id="employee"  @if(auth()->guard($guard)->user()->role == "employee") style="pointer-events: none;" @endif required>
                                            <option disabled selected>Select</option>
                                            @if(auth()->guard($guard)->user()->role !== "employee")
                                                @foreach($employeeall as $emp)
                                                    <option value="{{ $emp->emp_ID }}" @if(($data != null) && $emp->emp_ID == $data['employeeId']) selected @endif>
                                                        {{ strtoupper(ucwords($emp->lname)) }}
                                                        {{ strtoupper(ucwords($emp->prefix)) }}
                                                        {{ strtoupper(ucwords($emp->fname)) }}
                                                        {{ strtoupper(ucwords($emp->mname)) }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="{{ $employeeall->emp_ID }}" selected>
                                                    {{ strtoupper(ucwords($employeeall->lname)) }}
                                                    {{ strtoupper(ucwords($employeeall->prefix)) }}
                                                    {{ strtoupper(ucwords($employeeall->fname)) }}
                                                    {{ strtoupper(ucwords($employeeall->mname)) }}
                                                </option>
                                            @endif
                                        </select>                                    
                                    </div>
                                @else
                                    @if($acctstat == 1)
                                    <div class="col-md-3 col-sm-12">
                                        <label class="badge badge-secondary lbel">Employee Name</label><br>
                                        <select class="form-control form-control-sm select2" name="employee" id="employee" required>
                                            <option disabled selected>Select</option>
                                                @foreach($employeeall as $emp)
                                                    <option value="{{ $emp->emp_ID }}" @if(isset($employee) && $employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
                                                        {{ $emp->lname }}
                                                        {{ $emp->prefix }}
                                                        {{ $emp->fname }}
                                                        {{ isset($emp->mname) ?substr($emp->mname, 0, 1).'.' : '' }}
                                                    </option>
                                                @endforeach
                                        </select>                                    
                                    </div>
                                    @endif
                                @endif
                                <input type="text" name="acctstat" value="{{ $acctstat }}" hidden>
                                <div class="col-md-3 col-sm-6">
                                    <label class="badge badge-secondary lbel">From</label>
                                    <input type="date" name="date_from" class="form-control form-control-sm" id="inc_date1" value="{{ ($data != null) ? $data['dateFrom'] : '' }}" required>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="badge badge-secondary lbel">To</label>
                                    <input type="date" name="date_to" class="form-control form-control-sm" id="inc_date2" value="{{ ($data != null) ? $data['dateTo'] : '' }}" required>
                                </div>
                                <div class="col-md-1 col-sm-6 d-flex align-items-center">
                                    <div>
                                        <label class="badge badge-secondary lbel d-block">Overtime</label>
                                        <input type="checkbox" value="1" name="overtime" class="form-control form-control-sm" style="margin-top: 9px;" {{ ($data['overtime'] ?? 0) == 1 ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success btn-sm btn-block"><i class="fas fa-file-pdf"></i> Generate</button>
                                </div>
                            </div>
                        </div>                        
                    </form> 
                    @if(isset($data))     
                        <iframe src="{{ ($data == null) ? '' : route('logDtrView', ['employeeId' => $data['employeeId'] ?? 0, 'dateFrom' => $data['dateFrom'] ?? null, 'dateTo' => $data['dateTo'] ?? null, 'overtime' => $data['overtime'] ?? null]) }}" width="100%" height="600px"></iframe>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
@endsection