@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row">

        {{-- RIGHT COLUMN (Job Applications List - All Applications) --}}
        <div class="col-lg-12">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Job Applications ({{ $jobapplication->count() }} total)
                    </h3>
                    <div class="card-tools">
                        <span class="badge bg-info">All Applications</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Applicant Name</th>
                                    <th>Position Applied</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @forelse($jobapplication as $application)
                                <tr>
                                    <td class="align-middle">{{ $no++ }}</td>
                                    <td class="align-middle">
                                        <strong>
                                            {{ $application->first_name }}
                                            {{ !empty($application->middle_name) ? strtoupper(substr($application->middle_name, 0, 1)).'.' : '' }}
                                            {{ $application->last_name }}
                                        </strong>
                                        @if($application->suffix)
                                            <span class="text-muted">, {{ $application->suffix }}</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{ $application->title }}
                                        <br>
                                        <small class="text-muted">
                                            @if($application->job_type == 1)
                                                <span class="badge bg-success">Non-Teaching</span>
                                            @elseif($application->job_type == 2)
                                                <span class="badge bg-primary">Teaching</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td class="align-middle">{{ $application->email }}</td>
                                    <td class="align-middle">{{ $application->mobile }}</td>
                                    <td class="align-middle">
                                        {{ \Carbon\Carbon::parse($application->created_at)->format('M d, Y') }}
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($application->created_at)->diffForHumans() }}</small>
                                    </td>
                                    <td class="align-middle text-center">
                                        @if($application->checked == 1)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i> Forwarded to the Mayor's Office
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock"></i> Pending Review
                                            </span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        <a href="{{ route('viewApplication', $application->id) }}" 
                                           target="_blank" 
                                           class="btn btn-info btn-sm" 
                                           title="View Application">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No job applications found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection