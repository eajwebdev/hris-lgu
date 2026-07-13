@extends('layouts.master')

@section('body')
<style>
    .circle {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 10px;
    }
    .span-fix {
        display: inline-block;
        width: 125px;
        text-align: left;
    }
</style>
<section class="content">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header" style="padding: 6px !important; background-color: #3B8682 !important;">
                    <i class="fas fa-spinner text-light"></i><b class="text-light"> PENDING</b>
                </div>
                <div class="card-footer p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="{{ route('readPending', 1) }}" class="nav-link">
                                <i class="{{ request()->is('pending/1') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-calendar-check" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/1') ? 'text-dark' : 'text-muted' }} text-bold">Leave Application</span>
                                <span class="float-right badge badge-warning" class="">{{ number_format($leaveappCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 2) }}" class="nav-link">
                                <i class="{{ request()->is('pending/2') ? 'text-dark' : 'text-muted' }} pr-2 fas fas fa-certificate" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/2') ? 'text-dark' : 'text-muted' }} text-bold">Eligibility</span>
                                <span class="float-right badge badge-warning" class="">{{ number_format($eliCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 3) }}" class="nav-link">
                                <i class="{{ request()->is('pending/3') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-briefcase" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/3') ? 'text-dark' : 'text-muted' }} text-bold">Work Experience</span>
                                <span class="float-right badge badge-warning" class="">{{ number_format($workexpCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 5) }}" class="nav-link">
                                <i class="{{ request()->is('pending/5') ? 'text-dark' : 'text-muted' }} pr-2 fas fas fa-book" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/5') ? 'text-dark' : 'text-muted' }} text-bold">Learning and Development</span>
                                <span class="float-right badge badge-warning" class="">{{ number_format($learDevCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 4) }}" class="nav-link">
                                <i class="{{ request()->is('pending/4') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-hand-holding-heart" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/4') ? 'text-dark' : 'text-muted' }} text-bold">Voluntary Work</span>
                                <span class="float-right badge badge-warning" class="">{{ number_format($volWorkCount) }}</span>
                            </a>
                        </li> 
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card">
            <div class="card-header" style="background-color: #3B8682 !important;">
                <h3 class="card-title"></h3>
                <div class="card-tools d-flex justify-content-between align-items-center w-100">
                    <!-- Dropdown list on the left -->
                    @if($type == 1)
                    <div class="p-1" style="flex: 1; margin-left: -12px;">
                        <select class="form-control form-control-sm" style="width: 20%;" onchange="redirectToPendingLeave(this)">
                            <option value="0" {{ ($cat == 0) ? 'selected' : '' }}>All</option>
                            <option value="0.1" {{ ($cat == 0.1) ? 'selected' : '' }}>Waiting...</option>
                            <option value="0.2" {{ ($cat == 0.2) ? 'selected' : '' }}>Employee</option>
                            <option value="1" {{ ($cat == 1) ? 'selected' : '' }}>HRMO</option>
                            <option value="2" {{ ($cat == 2) ? 'selected' : '' }}>Supervisor</option>
                            <option value="3" {{ ($cat == 3) ? 'selected' : '' }}>MAYOR / VICE MAYOR</option>
                            <option value="4" {{ ($cat == 4) ? 'selected' : '' }}>APPROVED</option>
                            <option value="5" {{ ($cat == 5) ? 'selected' : '' }}>DISAPPROVED</option>
                        </select>
                    </div>
                    <form 
                        action="{{ route('leaveReport') }}" method="POST" class="input-group w-50" 
                        target="_blank" style="float: right;">
                        @csrf
                        <input type="text" id="date_range" name="date" placeholder="SELECT DATE"class="form-control form-control-sm">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                        </div>
                    </form>
                    @else
                    <!-- Search input on the right -->
                    <div class="input-group input-group-sm" style="width: 20%; flex: 0 0 auto; margin-left: auto;">
                        <input type="text" name="table_search" class="form-control" placeholder="Search">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
                <div class="card-body table-responsive p-0" style="height: 500px;">
                    @if($type == 1)
                    <div class="input-group input-group-sm m-2" style="width: 20%; flex: 0 0 auto; margin-left: 1rem; float: right;">
                        <input type="text" name="table_search" class="form-control float-right" placeholder="Search" autocomplete="off">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div> 
                    @endif
                    <table class="table table-head-fixed text-nowrap">
                        <tbody>
                            <thead> 
                                <tr>
                                    @if($type == 1)
                                    <th width="70%">SIGNATORIES STATUS</th>
                                    <th width="15%">DURATION</th>
                                    <th width="15%">REMARKS</th>
                                    @else
                                    <th width="80%">FULL NAME</th>
                                    @endif
                                    <th width="20%" class="text-center">ACTION</th>
                                </tr>
                            </thead>
                            @if($type == 1)
                                @foreach ($employees as $emp)
                                <tr>
                                    @if($type != 1)
                                    <td>
                                        {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                                    </td>
                                    @endif
                                    <td>
                                        @if($emp->status == 1)
                                            <div class="d-flex flex-wrap align-items-center">
                                                <!-- Employee E-sign Status -->
                                                <div class="mr-1">
                                                    <span class="badge bg-{{ in_array($emp->emp_esign, [0, 1]) ? 'danger' : 'success' }}">
                                                        <i class="fas fa-{{ in_array($emp->emp_esign, [0, 1]) ? 'times' : 'check' }}"></i> 
                                                    </span>
                                                </div>

                                                <!-- Employee Status -->
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user"></i> {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- HRMO Status -->
                                                <span class="badge bg-danger mr-1">
                                                    <i class="fas fa-times"></i> 
                                                </span>
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-tie"></i> {{ $emp->hr_lname }}, {{ $emp->hr_fname }} {{ $emp->hr_suffix }} {{ isset($emp->hr_mname) ? strtoupper(substr($emp->hr_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Immediate Supervisor Status -->
                                                <span class="badge bg-danger mr-1">
                                                    <i class="fas fa-times"></i> 
                                                </span>
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-check"></i> {{ $emp->supervisor_lname }}, {{ $emp->supervisor_fname }} {{ $emp->supervisor_suffix }} {{ isset($emp->supervisor_mname) ? strtoupper(substr($emp->supervisor_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Mayor / Vice Mayor Status -->
                                                <span class="badge bg-danger mr-1">
                                                    <i class="fas fa-times"></i> 
                                                </span>
                                                <div>
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-shield"></i> {{ $emp->approver_lname }}, {{ $emp->approver_fname }} {{ $emp->approver_suffix }} {{ isset($emp->approver_mname) ? strtoupper(substr($emp->approver_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @elseif($emp->status == 2)
                                        <div class="d-flex flex-wrap align-items-center">
                                                <!-- Employee E-sign Status -->
                                                <div class="mr-1">
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> 
                                                    </span>
                                                </div>

                                                <!-- Employee Status -->
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user"></i> {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- HRMO Status -->
                                                <span class="badge bg-{{ ($emp->status == 2) ? 'success' : 'danger' }} mr-1">
                                                    <i class="fas fa-{{ ($emp->status == 2) ? 'check' : 'times' }}"></i> 
                                                </span>
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-tie"></i> {{ $emp->hr_lname }}, {{ $emp->hr_fname }} {{ $emp->hr_suffix }} {{ isset($emp->hr_mname) ? strtoupper(substr($emp->hr_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Immediate Supervisor Status -->
                                                <span class="badge bg-danger mr-1">
                                                    <i class="fas fa-times"></i> 
                                                </span>
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-check"></i> {{ $emp->supervisor_lname }}, {{ $emp->supervisor_fname }} {{ $emp->supervisor_suffix }} {{ isset($emp->supervisor_mname) ? strtoupper(substr($emp->supervisor_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Mayor / Vice Mayor Status -->
                                                <span class="badge bg-danger mr-1">
                                                    <i class="fas fa-times"></i> 
                                                </span>
                                                <div>
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-shield"></i> {{ $emp->approver_lname }}, {{ $emp->approver_fname }} {{ $emp->approver_suffix }} {{ isset($emp->approver_mname) ? strtoupper(substr($emp->approver_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @elseif($emp->status == 3)
                                            <div class="d-flex flex-wrap align-items-center">
                                                <!-- Employee E-sign Status -->
                                                <div class="mr-1">
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> 
                                                    </span>
                                                </div>

                                                <!-- Employee Status -->
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user"></i> {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- HRMO Status -->
                                                <span class="badge bg-success mr-1">
                                                    <i class="fas fa-check"></i> 
                                                </span>
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-tie"></i> {{ $emp->hr_lname }}, {{ $emp->hr_fname }} {{ $emp->hr_suffix }} {{ isset($emp->hr_mname) ? strtoupper(substr($emp->hr_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Immediate Supervisor Status -->
                                                <span class="badge bg-{{ ($emp->status == 3) ? 'success' : 'danger' }} mr-1">
                                                    <i class="fas fa-{{ ($emp->status == 3) ? 'check' : 'times' }}"></i> 
                                                </span>
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-check"></i> {{ $emp->supervisor_lname }}, {{ $emp->supervisor_fname }} {{ $emp->supervisor_suffix }} {{ isset($emp->supervisor_mname) ? strtoupper(substr($emp->supervisor_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Mayor / Vice Mayor Status -->
                                                <span class="badge bg-danger mr-1">
                                                    <i class="fas fa-times"></i> 
                                                </span>
                                                <div>
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-shield"></i> {{ $emp->approver_lname }}, {{ $emp->approver_fname }} {{ $emp->approver_suffix }} {{ isset($emp->approver_mname) ? strtoupper(substr($emp->approver_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @elseif($emp->status == 4)
                                            <div class="d-flex flex-wrap align-items-center">
                                                <!-- Employee E-sign Status -->
                                                <div class="mr-1">
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> 
                                                    </span>
                                                </div>

                                                <!-- Employee Status -->
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user"></i> {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- HRMO Status -->
                                                <span class="badge bg-success mr-1">
                                                    <i class="fas fa-check"></i> 
                                                </span>
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-tie"></i> {{ $emp->hr_lname }}, {{ $emp->hr_fname }} {{ $emp->hr_suffix }} {{ isset($emp->hr_mname) ? strtoupper(substr($emp->hr_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Immediate Supervisor Status -->
                                                <span class="badge bg-success mr-1">
                                                    <i class="fas fa-check"></i> 
                                                </span>
                                                <div class="mr-1">
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-check"></i> {{ $emp->supervisor_lname }}, {{ $emp->supervisor_fname }} {{ $emp->supervisor_suffix }} {{ isset($emp->supervisor_mname) ? strtoupper(substr($emp->supervisor_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Mayor / Vice Mayor Status -->
                                                <span class="badge bg-{{ ($emp->status == 4) ? 'success' : 'danger' }} mr-1">
                                                    <i class="fas fa-{{ ($emp->status == 4) ? 'check' : 'times' }}"></i> 
                                                </span>
                                                <div>
                                                    <span class="badge bg-secondary span-fix">
                                                        <i class="fas fa-user-shield"></i> {{ $emp->approver_lname }}, {{ $emp->approver_fname }} {{ $emp->approver_suffix }} {{ isset($emp->approver_mname) ? strtoupper(substr($emp->approver_mname, 0, 1)).'.' : '' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <td>{{ \Carbon\Carbon::parse($emp->date_filing)->diffForHumans() }}</td>

                                    <td>
                                        <!-- History Badge -->
                                        @if($emp->history == 1)
                                            <span class="badge bg-warning">
                                                <i class="fas fa-spinner fa-spin"></i> Ongoing...
                                            </span>
                                        @else
                                            @if($emp->remarks_stat == 0)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> Complete
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-check-circle"></i> Disapproved
                                                </span>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <!-- Action Button -->
                                        @if($emp->status != 4)
                                            <a href="#" data-id="{{ $emp->id }}" data-url-template="{{ url('leave/preview-leave/__ID__') }}" 
                                                data-toggle="modal" data-target="#pdfModalPending" 
                                                id="preview{{ $emp->id }}"
                                                class="btn btn-danger btn-sm" 
                                                style="width: 30px; padding: 0px !important;" >
                                                <i class="fas fa-file-pdf" style="font-size: 0.75rem;"></i>
                                            </a>
                                        @else
                                            <a href="#" data-id="{{ $emp->id }}" data-toggle="modal" data-target="#pdfModalHistory"
                                                id="preview{{ $emp->id }}"
                                                class="btn btn-danger btn-sm" 
                                                style="width: 30px; padding: 0px !important;" >
                                                <i class="fas fa-file-pdf" style="font-size: 0.75rem;"></i>
                                            </a>
                                        @endif
                                        {{-- <button type="button"
                                                class="btn btn-danger btn-round btn-sm"
                                                data-id="{{ $emp->id }}"
                                                data-url-template="{{ url('leave/preview-leave/__ID__') }}"
                                                data-toggle="modal"
                                                data-target="#pdfModalPending">
                                            <i class="fas fa-file-pdf"></i>
                                        </button> --}}
                                        <a href="{{ route('leaveStatus', $emp->employid) }}" 
                                        target="_blank" 
                                        class="btn btn-{{ (in_array($emp->emp_esign, [1, 2])) ? 'success' : 'info' }} btn-sm" 
                                        style="width: 30px; padding: 0px !important;" 
                                        value="{{ $emp->id }}">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                @foreach ($employees as $emp)
                                    @php
                                    switch ($type) {
                                        case '2':
                                            $route = route('eligibility', $emp->id);
                                            break;
                                
                                        case '3':
                                            $route = route('work-experience', $emp->id);
                                            break;
                                        
                                        case '4':
                                            $route = route('voluntary-work', $emp->id);
                                            break;
                                
                                        case '5':
                                            $route = route('learning-dev', $emp->id);
                                            break;
                                    }
                                    @endphp
                                    <tr>
                                        <td>{{ $emp->lname }}, {{ $emp->fname }} {{ $emp->suffix }} {{ isset($emp->mname) ? strtoupper(substr($emp->mname, 0, 1)).'.' : '' }}</td>
                                        <td class="text-center">
                                            <a href="{{ $route }}" target="_blank" class='btn btn-info btn-sm mr-1' style='width: 30px;' value="{{ $emp->id }}">
                                                <i class="fas fa-exclamation-circle" style="font-size: 0.75rem;"></i>  
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pdfModalPending" tabindex="-1" role="dialog" aria-labelledby="pdfModalPendingLabel" aria-hidden="true">
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="table_search"]');
        const tableRows = document.querySelectorAll('.table tbody tr');
    
        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();
    
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const found = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(searchTerm));
                row.style.display = found ? '' : 'none';
            });
        });
    });
</script>
<script>
    const pendingRouteBase = "{{ route('readPending', ['type' => 1, 'cat' => ':cat']) }}";
</script>
<script>
    function redirectToPendingLeave(selectElement) {
        const selectedCategory = selectElement.value; // Get the selected value

        // Replace the placeholder in the route with the selected category
        const url = pendingRouteBase.replace(':cat', selectedCategory);

        // Redirect to the constructed URL
        window.location.href = url;
    }
</script>
@endsection