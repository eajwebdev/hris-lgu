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
    .vcenter{
        text-align: center;
        vertical-align: middle;
    }
</style>
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
                    <div class="row justify-content-end">
                        <div class="col-md-6"> <!-- HALF WIDTH -->

                            <form action="{{ route('leaveReport') }}" method="POST" target="_blank">
                                @csrf

                                <div class="row align-items-end mb-3">

                                    <!-- LEFT: Date Range -->
                                    <div class="col-md-11 mb-0">
                                        <div class="form-group mb-0">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="far fa-calendar-alt"></i>
                                                    </span>
                                                </div>
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    name="date"
                                                    id="dateRange"
                                                    placeholder="Select date or date range"
                                                    required
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT: Generate Button -->
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-danger btn-block">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                    </div>

                                </div>
                            </form>

                        </div>
                    </div>
                    @endif
                    <div class="tab-content">
                        <table class="table table-collapsed table-hover" id="leaveHistory">
                            <thead>
                                <tr>
                                    <th>LEAVE TYPE</th>
                                    <th class="vcenter">INCLUSIVE DATES</th>
                                    <th class="vcenter" width="50">DAYS APPLIED</th>
                                    <th class="vcenter" width="60">DAYS W/OUT PAY</th>
                                    <th class="vcenter">DATE OF FILING</th>
                                    <th class="vcenter">STATUS</th>
                                    <th class="vcenter">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $leavetype = [
                                        '1' => 'Vacation Leave',
                                        '2' => 'Mandatory/Forced Leave',
                                        '3' => 'Sick Leave',
                                        '4' => 'Maternity Leave',
                                        '5' => 'Paternity Leave',
                                        '6' => 'Special Privilege Leave',
                                        '7' => 'Solo Parent Leave',
                                        '8' => 'Study Leave',
                                        '9' => '10-Day VAWC Leave',
                                        '10' => 'Rehabilitation Privilege',
                                        '11' => 'Special Leave Benefits for Women',
                                        '12' => 'Special Emergency (Calamity) Leave',
                                        '13' => 'Adoption Leave',
                                        '14' => 'Vacation Service Credit',
                                        '15' => 'Wellness Leave'
                                    ];
                                @endphp
                                @foreach($leaveApplication as $leaves)
                                    @php
                                        if (strpos($leaves->date_range, 'to') !== false) {
                                            [$startDate, $endDate] = explode(' to ', $leaves->date_range);
                                            
                                            $formattedStartDate = \Carbon\Carbon::parse($startDate)->format('M d, Y');
                                            $formattedEndDate = \Carbon\Carbon::parse($endDate)->format('M d, Y');
                                        } else {
                                            $startDate = $leaves->date_range;
                                            $formattedStartDate = \Carbon\Carbon::parse($leaves->date_range)->format('M d, Y');
                                            $formattedEndDate = null;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ strtoupper($leavetype[$leaves->leave_type]) }}</td>
                                        <td data-order="{{ \Carbon\Carbon::parse($startDate)->format('Y-m-d') }}">
                                            @if($formattedEndDate)
                                                {{ strtoupper($formattedStartDate) }} - {{ strtoupper($formattedEndDate) }}
                                            @else
                                                {{ strtoupper($formattedStartDate) }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $leaves->days }}</td>
                                        <td class="text-center">{{ ($leaves->day_wpay) ? $leaves->day_wpay : '' }}</td>
                                        <td data-order="{{ isset($leaves->date_filing) ? \Carbon\Carbon::parse($leaves->date_filing)->format('Y-m-d') : '' }}">
                                            {{ isset($leaves->date_filing) ? strtoupper(\Carbon\Carbon::parse($leaves->date_filing)->format('M d, Y')) : '' }}
                                        </td>
                                        <td width="100">
                                            @if($leaves->remarks_stat == 0)
                                                <span class="badge badge-success">approved</span>
                                            @elseif($leaves->remarks_stat == 4)
                                                <span class="badge badge-danger">canceled</span>
                                                <div class="callout callout-danger remarks-details" style="padding: 4px !important; display:none;">
                                                    <p>{{ $leaves->remarks_details2 }}</p>
                                                </div>
                                            @else
                                                <span class="badge badge-danger">disapproved</span>
                                                <div class="callout callout-danger remarks-details" style="padding: 4px !important; display:none;">
                                                    <p>{{ $leaves->remarks_details }}</p>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($guard == "web")
                                                <button type="button" class="btn btn-warning btn-sm @if($leaves->remarks_stat !== 0) cancel @endif disapprove-leave" data-id="{{ $leaves->id }}" data-by="4" @if($leaves->remarks_stat !== 0) disabled @endif><i class="fas fa-times"></i></button>
                                            @endif
                                            <button type="button" class="btn btn-danger btn-sm" title="view" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModalHistory"><i class="fas fa-file-pdf"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach($leaveApplication1 as $leaves)
                                    @php
                                        if (strpos($leaves->date_range, 'to') !== false) {
                                            [$startDate, $endDate] = explode(' to ', $leaves->date_range);
                                            
                                            $formattedStartDate = \Carbon\Carbon::parse($startDate)->format('M d, Y');
                                            $formattedEndDate = \Carbon\Carbon::parse($endDate)->format('M d, Y');
                                        } else {
                                            $startDate = $leaves->date_range;
                                            $formattedStartDate = \Carbon\Carbon::parse($leaves->date_range)->format('M d, Y');
                                            $formattedEndDate = null;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ strtoupper($leavetype[$leaves->leave_type]) }}</td>
                                        <td data-order="{{ \Carbon\Carbon::parse($startDate)->format('Y-m-d') }}">
                                            @if($formattedEndDate)
                                                {{ strtoupper($formattedStartDate) }} - {{ strtoupper($formattedEndDate) }}
                                            @else
                                                {{ strtoupper($formattedStartDate) }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $leaves->days }}</td>
                                        <td class="text-center">{{ ($leaves->day_wpay) ? $leaves->day_wpay : '' }}</td>
                                        <td data-order="{{ isset($leaves->date_filing) ? \Carbon\Carbon::parse($leaves->date_filing)->format('Y-m-d') : '' }}">
                                            {{ isset($leaves->date_filing) ? strtoupper(\Carbon\Carbon::parse($leaves->date_filing)->format('M d, Y')) : '' }}
                                        </td>
                                        <td width="100">
                                            @if($leaves->remarks_stat == 0)
                                                <span class="badge badge-success">approved</span>
                                            @elseif($leaves->remarks_stat == 4)
                                                <span class="badge badge-danger">canceled</span>
                                                <div class="callout callout-danger remarks-details" style="padding: 4px !important; display:none;">
                                                    <p>{{ $leaves->remarks_details1 }}</p>
                                                </div>
                                            @else
                                                <span class="badge badge-danger">disapproved</span>
                                                <div class="callout callout-danger remarks-details" style="padding: 4px !important; display:none;">
                                                    <p>{{ $leaves->remarks_details }}</p>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($guard == "web")
                                                <button type="button" class="btn btn-warning btn-sm @if($leaves->remarks_stat !== 0) cancel @endif disapprove-leave" data-id="{{ $leaves->id }}" data-by="4" @if($leaves->remarks_stat !== 0) disabled @endif><i class="fas fa-times"></i></button>
                                            @endif
                                            <button type="button" class="btn btn-danger btn-sm" title="view" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModalHistory"><i class="fas fa-file-pdf"></i></button>
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
<div class="modal fade" id="pdfModal" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <iframe id="pdfIframe" src="" width="100%" height="600px" style="border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pdfModalHistory" tabindex="-1" role="dialog" aria-labelledby="pdfModalHistoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <iframe id="pdfIframeHistory" src="" width="100%" height="600px" style="border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
</section>
<style>
    td:hover .remarks-details {
        display: block !important;
    }
</style>
@endsection
