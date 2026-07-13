@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="row" style="padding-top: 10px;">
        <div class="col-lg-12">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>TARDINESS & UNDERTIME</b>
                    </h2>
                </div>
                <div class="card-body">
                    <form class="form-horizontal add-form" action="{{ route('tirednessSearch') }}" method="POST">
                        @csrf
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-3 col-sm-12">
                                    <label class="badge badge-secondary lbel">Employee Name</label><br>
                                    <select class="form-control form-control-sm {{ (auth()->guard($guard)->user()->role == "employee") ? '' : 'select2' }}" name="employee" id="employee"  @if(auth()->guard($guard)->user()->role == "employee") style="pointer-events: none;" @endif required>
                                        <option value="0" selected>ALL</option>
                                        @if(auth()->guard($guard)->user()->role !== "employee")
                                            @foreach($employeeall as $emp)
                                                <option value="{{ $emp->emp_ID }}" @if(isset($employee) && $employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
                                                    {{ $emp->lname }} {{ $emp->prefix }} {{ $emp->fname }} {{ isset($emp->mname) ? substr($emp->mname, 0, 1).'.' : '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>                                    
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="badge badge-secondary lbel">TO</label> 
                                    <input type="month" name="month" class="form-control form-control-sm" id="date" value="{{ ($month !== null) ? $month : date('Y-m') }}" required>
                                </div>
                                <div class="col-md-1 col-sm-6 d-flex align-items-end">
                                    <button class="btn btn-success btn-sm btn-block"><i class="fas fa-file-pdf"></i> Generate</button>
                                </div>
                            </div>
                        </div>                        
                    </form>
                    @php
                        $iframeSrc = request()->isMethod('post') && isset($employeeId, $month) ? route('pdfTirednes', ['employeeId' => $employeeId, 'month' => $month]) : '';
                    @endphp
                    <iframe src="{{ $iframeSrc }}" width="100%" height="600px"></iframe>
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