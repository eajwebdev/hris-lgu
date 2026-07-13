@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
    $status_labels = [
        0 => 'Application Submitted',
        1 => 'Reviewing',
        2 => 'Qualified / Ready for Interview',
        3 => 'Disqualified',
        4 => 'Qualified yet not selected',
        5 => 'Top 5 / Psychological or Pre-Employment Test',
        6 => 'Not Hired',
        7 => 'Hired',
    ];
@endphp
<style>
    .application-filter .select2-container--default .select2-selection--single {
        height: calc(1.8125rem + 2px);
        border: 1px solid #ced4da;
    }

    .application-filter .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057;
        font-size: .875rem;
        font-weight: 400;
        line-height: calc(1.8125rem + 2px);
        padding-left: .5rem;
        padding-right: 1.75rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .application-filter .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.8125rem + 2px);
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12 mb-2">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-briefcase"></i> Application List
                    </h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('appList') }}" class="application-filter border rounded bg-light p-3 mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label for="filter_position_id">Position</label>
                                    <select name="position_id" id="filter_position_id" class="form-control form-control-sm select2">
                                        <option value="">All Positions</option>
                                        @foreach($jobs as $job)
                                            <option value="{{ $job->id }}" {{ (string) request('position_id') === (string) $job->id ? 'selected' : '' }}>
                                                {{ $job->title }}{{ !empty($job->plantilla_item_no) ? ' - Plantilla No. '.$job->plantilla_item_no : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label for="filter_status">Status</label>
                                    <select name="status" id="filter_status" class="form-control form-control-sm">
                                        <option value="">All Statuses</option>
                                        @foreach($status_labels as $value => $label)
                                            <option value="{{ $value }}" {{ (string) request('status') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label for="filter_date_from">Applied From</label>
                                    <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label for="filter_date_to">Applied To</label>
                                    <input type="date" name="date_to" id="filter_date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <label class="d-none d-md-block">&nbsp;</label>
                                <button type="submit" class="btn btn-info btn-sm btn-block" title="Apply Filter">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                            <div class="col-md-1">
                                <label class="d-none d-md-block">&nbsp;</label>
                                <button type="submit"
                                        class="btn btn-danger btn-sm btn-block"
                                        formaction="{{ route('applicationReport') }}"
                                        formtarget="_blank"
                                        title="Generate Report">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <button class="btn btn-success btn-sm float-right mt-1 mb-1" data-toggle="modal" data-target="#add-applicant">+ ADD APPLICANT</button>
                    <!-- Add Applicant Modal -->
                    <div class="modal fade" id="add-applicant" role="dialog" aria-labelledby="addApplicantLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">

                            <form action="{{ route('applicationStore') }}" method="POST">
                                @csrf

                                <div class="modal-content">

                                    <div class="modal-body" style="background-color: #e9ecef;">

                                        <!-- Position -->
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Position Applied</label>
                                                    <select name="jid" class="form-control select2" required>
                                                        <option value="">Select Position</option>
                                                        @foreach($jobs as $job)
                                                            <option value="{{ $job->id }}">
                                                                {{ $job->title }}{{ !empty($job->plantilla_item_no) ? ' - Plantilla No. '.$job->plantilla_item_no : '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Date Applied</label>
                                                    <input type="datetime-local"
                                                        name="created_at"
                                                        class="form-control"
                                                        value="{{ now()->format('Y-m-d\TH:i') }}"
                                                        required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Name -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>First Name</label>
                                                    <input type="text" name="first_name" class="form-control" autocomplete="off" required>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Middle Name</label>
                                                    <input type="text" name="middle_name" class="form-control" autocomplete="off">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Last Name</label>
                                                    <input type="text" name="last_name" class="form-control" autocomplete="off" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Personal Info -->
                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Age</label>
                                                    <input type="number" name="age" class="form-control" min="18" max="65" required>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Sex</label>
                                                    <select name="sex" class="form-control" required>
                                                        <option value="">Select Sex</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Mobile No.</label>
                                                    <input type="text" name="mobile" class="form-control" autocomplete="off" required>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Email Address</label>
                                                    <input type="email" name="email" class="form-control" autocomplete="off" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Address</label>
                                                    <textarea name="address" class="form-control" rows="2" required></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Education -->
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="mb-0"><strong>Educational Background</strong></label>
                                            <button type="button" class="btn btn-success btn-sm" id="addEducation">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>

                                        <div id="educationWrapper">
                                            <div class="row education-row">
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label>School / Course / Description</label>
                                                        <input type="text" name="education[]" class="form-control" required>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Level</label>
                                                        <input type="text" name="elevel[]" class="form-control" placeholder="College, HS, etc." required>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Year</label>
                                                        <input type="text" name="eyear[]" class="form-control" placeholder="2020" required>
                                                    </div>
                                                </div>

                                                <div class="col-md-1 d-flex align-items-center">
                                                    <button type="button" class="btn btn-danger btn-sm removeEducation mt-3" disabled>
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Eligibility -->
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="mb-0"><strong>Eligibility</strong></label>
                                            <button type="button" class="btn btn-success btn-sm" id="addEligibility">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>

                                        <div id="eligibilityWrapper">
                                            <div class="row eligibility-row">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" name="eligibility[]" class="form-control" placeholder="Civil Service, PRC, etc.">
                                                    </div>
                                                </div>

                                                <div class="col-md-1 d-flex align-items-center">
                                                    <button type="button" class="btn btn-danger btn-sm removeEligibility mt-3" disabled>
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                            <i class="fas fa-times"></i> Close
                                        </button>

                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Save Applicant
                                        </button>
                                    </div>

                                </div>
                            </form>

                        </div>
                    </div>

                    <div class="table-responsive mt-3">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>App No.</th>
                                    <th>Control No.</th>
                                    <th>Applicant Name</th>
                                    <th>Position</th>
                                    <th>Sex</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Files</th>
                                    <th>Date Applied</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach($applications as $app)
                                <tr id="tr-{{ $app->id }}">
                                    <td class="align-middle">{{ $no++ }}</td>
                                    <td class="align-middle">{{ $app->app_number }}</td>
                                    <td class="align-middle">
                                        <span>{{ $app->ctrl_no }}</span>
                                    </td>
                                    <td class="align-middle">{{ $app->first_name }} {{ $app->middle_name }} {{ $app->last_name }}</td>
                                    <td class="align-middle">
                                        {{ $app->position }}
                                        @if(!empty($app->plantilla_item_no))
                                            <br>
                                            <small class="text-muted">Plantilla No. {{ $app->plantilla_item_no }}</small>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ ucfirst($app->sex) }}</td>
                                    <td class="align-middle">{{ $app->mobile }}</td>
                                    <td class="align-middle">{{ $app->email }}</td>

                                    {{-- 🔹 File Access --}}
                                    <td class="align-middle text-center">
                                        @if (empty($app->ctrl_no))
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-warning set-ctrl"
                                                    value="{{ $app->id }}"
                                                    data-toggle="modal"
                                                    data-target="#setCtrlModal"
                                                    title="Set Control Number to unlock file access">
                                                <i class="fas fa-key"></i> Set Control No.
                                            </button>
                                        @else
                                            <div class="d-flex flex-wrap" style="gap: 4px;">
                                                @if(!empty($app->pds))
                                                <a href="{{ asset('storage/' . $app->pds) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Personal Data Sheet">
                                                    <i class="fas fa-file-alt"></i> PDS
                                                </a>
                                                @endif
                                                @if(!empty($app->wes))
                                                <a href="{{ asset('storage/' . $app->wes) }}" class="btn btn-sm btn-outline-info" target="_blank" title="Work Experience Sheet">
                                                    <i class="fas fa-briefcase"></i> WES
                                                </a>
                                                @endif
                                                @if(!empty($app->intent))
                                                <a href="{{ asset('storage/' . $app->intent) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Intent Letter">
                                                    <i class="fas fa-envelope-open-text"></i> Intent
                                                </a>
                                                @endif
                                                @if(!empty($app->resume))
                                                <a href="{{ asset('storage/' . $app->resume) }}" class="btn btn-sm btn-outline-success" target="_blank" title="Resume">
                                                    <i class="fas fa-user"></i> Resume
                                                </a>
                                                @endif
                                                @if(!empty($app->tor))
                                                <a href="{{ asset('storage/' . $app->tor) }}" class="btn btn-sm btn-outline-danger" target="_blank" title="Transcript of Records">
                                                    <i class="fas fa-graduation-cap"></i> TOR
                                                </a>         
                                                @endif
                                                @if(!empty($app->coe))
                                                    <a href="{{ asset('storage/' . $app->coe) }}"
                                                    class="btn btn-sm btn-outline-info"
                                                    target="_blank"
                                                    title="Certificate of Employment">
                                                        <i class="fas fa-briefcase"></i> COE
                                                    </a>
                                                @endif                         
                                                @if(!empty($app->cert_training))
                                                    <a href="{{ asset('storage/' . $app->cert_training) }}"
                                                    class="btn btn-sm btn-outline-warning"
                                                    target="_blank"
                                                    title="Certificate of Training">
                                                        <i class="fas fa-certificate"></i> COT
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle bold">{{ strtoupper($app->created_at->format('M. d, Y h:i A')) }}</td>
                                    {{-- 🔹 Status --}}
                                    <td class="text-center align-middle">
                                        @php
                                            $status_labels = [
                                                0 => 'Application Submitted',
                                                1 => 'Reviewing',
                                                2 => 'Qualified / Ready for Interview',
                                                3 => 'Disqualified',
                                                4 => 'Qualified yet not selected',
                                                5 => 'Top 5 / Psychological or Pre-Employment Test',
                                                6 => 'Not Hired',
                                                7 => 'Hired',
                                            ];

                                            $badge_colors = [
                                                0 => 'secondary',
                                                1 => 'info',
                                                2 => 'success',
                                                3 => 'danger',
                                                4 => 'warning',
                                                5 => 'primary',
                                                6 => 'dark',
                                                7 => 'success',
                                            ];
                                        @endphp
                                        <span class="badge badge-{{ $badge_colors[$app->status] ?? 'secondary' }}">
                                            {{ $status_labels[$app->status] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    {{-- 🔹 Actions --}}
                                    <td class="text-center align-middle">
                                        @if($app->ctrl_no)
                                            <button type="button"
                                                    class="btn btn-sm btn-info set-ctrl"
                                                    value="{{ $app->id }}"
                                                    data-ctrl-no="{{ $app->ctrl_no }}"
                                                    data-toggle="modal"
                                                    data-target="#setCtrlModal"
                                                    title="Edit Control Number">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($app->status == 1)
                                            {{-- Qualified --}}
                                            <button type="button"
                                                    class="btn btn-sm btn-success q-btn"
                                                    data-app-id="{{ $app->id }}"
                                                    data-toggle="modal"
                                                    data-target="#qualifyModal"
                                                    title="Mark as Qualified / Set Interview">
                                                <i class="fas fa-check"></i>
                                            </button>

                                            {{-- Disqualified --}}
                                            <button type="button"
                                                    class="btn btn-sm btn-danger dq-btn"
                                                    data-app-id="{{ $app->id }}"
                                                    data-toggle="modal"
                                                    data-target="#dqModal"
                                                    title="Disqualify Applicant">
                                                <i class="fas fa-times"></i>
                                            </button>

                                        @elseif ($app->status == 2)
                                            {{-- Move to next or skip --}}
                                            <form method="POST" action="{{ route('updateStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="4">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Not selected for next stage">
                                                    <i class="fas fa-user-clock"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('updateStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="5">
                                                <button type="submit" class="btn btn-sm btn-primary" title="Select for next stage (Top 5)">
                                                    <i class="fas fa-arrow-right"></i>
                                                </button>
                                            </form>

                                        @elseif ($app->status == 5)
                                            {{-- Not Hired --}}
                                            <form method="POST" action="{{ route('updateStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="6">
                                                <button type="submit" class="btn btn-sm btn-dark" title="Mark as Not Hired">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            </form>

                                            {{-- Hired --}}
                                            <form method="POST" action="{{ route('updateStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="7">
                                                <button type="submit" class="btn btn-sm btn-success" title="Mark as Hired">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            </form>

                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled title="No actions available">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @endif
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

{{-- 🔸 Set Control No. Modal --}}
<div class="modal fade" id="setCtrlModal" tabindex="-1" role="dialog" aria-labelledby="setCtrlModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg rounded">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title font-weight-bold" id="setCtrlModalLabel">
          <i class="fas fa-key mr-2"></i> Set Control Number
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="ctrlForm" method="POST" action="{{ route('setCtrlNo') }}">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="id" id="ctrlAppId">
          <div class="form-group">
            <label for="ctrl_no">Control Number</label>
            <input type="text" name="ctrl_no" id="ctrl_no" class="form-control" placeholder="Enter Control Number" autocomplete="off" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning text-dark">
            <i class="fas fa-save"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- 🔸 Qualified (Interview Schedule) Modal --}}
<div class="modal fade" id="qualifyModal" tabindex="-1" role="dialog" aria-labelledby="qualifyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow rounded">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title font-weight-bold" id="qualifyModalLabel">
          <i class="fas fa-calendar-check mr-2"></i> Set Interview Schedule
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="{{ route('updateStatus') }}">
        @csrf
        <input type="hidden" name="id" id="qualifyAppId">
        <input type="hidden" name="status" value="2">

        <div class="modal-body">
          <div class="form-group">
            <label for="interview_datetime">Interview Schedule <span class="text-danger">*</span></label>
            <input type="datetime-local" id="interview_datetime" name="interview_datetime" class="form-control" required>
            <small class="form-text text-muted">Example: September 16, 2025, at 2:00 PM</small>
          </div>

          <div class="form-group">
            <label for="venue">Venue <span class="text-danger">*</span></label>
            <textarea id="venue" name="venue" class="form-control" rows="2" required>Conference Room, Admin Building/Bidding Room/Accreditation/ Mini Hotel</textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check"></i> Confirm & Qualify
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- 🔸 Disqualification Reason Modal --}}
<div class="modal fade" id="dqModal" tabindex="-1" role="dialog" aria-labelledby="dqModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow rounded">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title font-weight-bold" id="dqModalLabel">
          <i class="fas fa-times-circle mr-2"></i> Disqualify Applicant
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="{{ route('updateStatus') }}">
        @csrf
        <input type="hidden" name="id" id="dqAppId">
        <input type="hidden" name="status" value="3">

        <div class="modal-body">
          <div class="form-group">
            <label for="dqReason">Reason for Disqualification <span class="text-danger">*</span></label>
            <textarea name="reason" id="dqReason" class="form-control" rows="3" placeholder="Enter reason..." required></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-check"></i> Confirm Disqualification
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-applicant').forEach(btn => {
        btn.addEventListener('click', () => {
            const app = JSON.parse(btn.dataset.application || '{}');

            document.getElementById('edit_app_id').value = app.id || '';
            document.getElementById('edit_jid').value = app.jid || '';
            document.getElementById('edit_first_name').value = app.first_name || '';
            document.getElementById('edit_middle_name').value = app.middle_name || '';
            document.getElementById('edit_last_name').value = app.last_name || '';
            document.getElementById('edit_age').value = app.age || '';
            document.getElementById('edit_sex').value = app.sex || '';
            document.getElementById('edit_mobile').value = app.mobile || '';
            document.getElementById('edit_email').value = app.email || '';
            document.getElementById('edit_address').value = app.address || '';
            document.getElementById('edit_education').value = app.education || '';
            document.getElementById('edit_eligibility').value = app.eligibility || '';
            document.getElementById('edit_created_at').value = app.created_at || '';

            $('#edit_jid').trigger('change');
        });
    });

    // Set Control No.
    document.querySelectorAll('.set-ctrl').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('ctrlAppId').value = btn.value;
        });
    });

    // Qualified modal
    document.querySelectorAll('.q-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('qualifyAppId').value = btn.dataset.appId;
        });
    });

    // Disqualified modal
    document.querySelectorAll('.dq-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('dqAppId').value = btn.dataset.appId;
        });
    });
});
</script>
<script>
$(function () {
    $('#filter_position_id').select2({
        width: '100%',
        placeholder: 'All Positions'
    });

    $('#add-applicant').on('shown.bs.modal', function () {
        $('.select2').select2({
            dropdownParent: $('#add-applicant'),
            width: '100%',
            placeholder: 'Search Position'
        });
    });

    $('#edit-applicant').on('shown.bs.modal', function () {
        $('.select2-edit').select2({
            dropdownParent: $('#edit-applicant'),
            width: '100%',
            placeholder: 'Search Position'
        });
    });

    $('#addEducation').click(function () {
        $('#educationWrapper').append(`
            <div class="row education-row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label>School / Course / Description</label>
                        <input type="text" name="education[]" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Level</label>
                        <input type="text" name="elevel[]" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Year</label>
                        <input type="text" name="eyear[]" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-1 d-flex align-items-center">
                    <button type="button" class="btn btn-danger btn-sm removeEducation mt-3">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `);
    });

    $(document).on('click', '.removeEducation', function () {
        $(this).closest('.education-row').remove();
    });

    $('#addEligibility').click(function () {
        $('#eligibilityWrapper').append(`
            <div class="row eligibility-row">
                <div class="col-md-11">
                    <div class="form-group">
                        <input type="text" name="eligibility[]" class="form-control" placeholder="Civil Service, PRC, etc.">
                    </div>
                </div>

                <div class="col-md-1 d-flex align-items-center">
                    <button type="button" class="btn btn-danger btn-sm removeEligibility mt-3">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `);
    });

    $(document).on('click', '.removeEligibility', function () {
        $(this).closest('.eligibility-row').remove();
    });

});
</script>
@endsection
