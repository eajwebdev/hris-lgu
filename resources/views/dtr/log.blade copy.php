@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="row" style="padding-top: 10px;">
        <div class="col-2">
            @include('dtr.submenu')
        </div>
        <div class="col-lg-10">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>Logs</b>
                    </h2>
                </div>
                <div class="card-body">
                    <form class="form-horizontal add-form" action="{{ route('dtrLogspost') }}" method="POST">
                        @csrf
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-4 col-sm-12">
                                    <label class="badge badge-secondary lbel">Employee Name</label><br>
                                    <select class="form-control form-control-sm {{ (auth()->guard($guard)->user()->role == "employee") ? '' : 'select2' }}" name="employee" id="employee"  @if(auth()->guard($guard)->user()->role == "employee") style="pointer-events: none;" @endif required>
                                        <option disabled selected>Select</option>
                                        @if(auth()->guard($guard)->user()->role !== "employee")
                                            @foreach($employeeall as $emp)
                                                <option value="{{ $emp->emp_ID }}" @if(isset($employee) && $employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
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
                                <div class="col-md-3 col-sm-6">
                                    <label class="badge badge-secondary lbel">From</label>
                                    <input type="date" name="date_from" class="form-control form-control-sm" id="date_from" value="{{ isset($employee) ? $date_from : '' }}" required>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="badge badge-secondary lbel">To</label>
                                    <input type="date" name="date_to" class="form-control form-control-sm" id="date_to" value="{{ isset($employee) ? $date_to : '' }}" required>
                                </div>
                                <div class="col-md-2 col-sm-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success btn-sm btn-block">Filter <i class="fas fa-filter"></i> </button>
                                </div>
                            </div>
                        </div>                        
                    </form>                    
                    <div class="container">
                    <table class="table table-sm">
                        <tbody>
                            @php
                            if (!function_exists('convertTo12HourFormat')) {
                                function convertTo12HourFormat($time) {
                                    return date("g:i:s A", strtotime($time));
                                }
                            }
                        
                            // Array of campuses
                            $campuses = [
                                '1' => 'CPSU Main',
                                '2' => 'CPSU Candoni',
                                '3' => 'CPSU Cauayan',
                                '4' => 'CPSU Hinigaran',
                                '5' => 'CPSU Hinoba-an',
                                '6' => 'CPSU Ilog',
                                '7' => 'CPSU San Carlo',
                                '8' => 'CPSU Sipalay',
                                '9' => 'CPSU Victorias',
                                '10' => 'CPSU Murcia',
                                '11' => 'CPSU Valladolid',
                                '12' => 'CPSU Moises Padilla',
                            ];
                        
                            // Group logs by date
                            $logsGroupedByDate = [];
                            foreach ($processedLogs as $employeeId => $logs) {
                                foreach ($logs as $log) {
                                    // Group logs by the 'date' key
                                    $logsGroupedByDate[$log['date']][] = $log;
                                }
                            }
                        
                            // Sort logs by date
                            ksort($logsGroupedByDate);
                        @endphp
                        
                        @foreach ($logsGroupedByDate as $date => $logs)
                            <table class="table table-sm">
                                <th>
                                    <tr>
                                        <th>{{ date('F j, Y', strtotime($date)) }}</th>
                                    </tr>
                                </th>
                                <tbody>
                                    @foreach ($logs as $log)
                                        @if ($log['type'] == 'time_in')
                                            <tr>
                                                <td>
                                                    <b>{{ strtoupper(ucwords($log['fname'])) }} {{ strtoupper(ucwords($log['lname'])) }} {{ strtoupper(ucwords($log['suffix'])) }}</b>
                                                    <span class="text-success">logged in</span> at {{ $campuses[$log['device_in_campus']] ?? 'Unknown' }}, {{ $log['device_in_label'] }} at {{ convertTo12HourFormat($log['time']) }}.
                                                </td>
                                            </tr>
                                        @elseif ($log['type'] == 'time_out' && !empty($log['time']))
                                            <tr>
                                                <td>
                                                    <b>{{ strtoupper(ucwords($log['fname'])) }} {{ strtoupper(ucwords($log['lname'])) }} {{ strtoupper(ucwords($log['suffix'])) }}</b>
                                                    <span class="text-danger">logged out</span> at {{ $campuses[$log['device_out_campus']] ?? 'Unknown' }}, {{ $log['device_out_label'] }} at {{ convertTo12HourFormat($log['time']) }}.
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        @endforeach
                                                                  
                        </tbody>
                                               
                    </table>  
                      
                    </div>
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