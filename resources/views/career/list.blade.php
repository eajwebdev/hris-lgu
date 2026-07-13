@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row">
        {{-- LEFT COLUMN (Add/Edit Job Form) --}}
        <div class="col-lg-3">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-briefcase"></i> {{ $current_route == "jlist" ? "Add Job" : "Edit Job" }}
                    </h3>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" 
                          action="{{ $current_route == "jlist" ? route('jCreate') : route('jUpdate') }}" 
                          method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $current_route == 'jEdit' ? $jEdit->id : '' }}">

                        {{-- Job Title --}}
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                </div>
                                <input type="text" name="title" 
                                       value="{{ $current_route == 'jEdit' ? $jEdit->title : '' }}" 
                                       placeholder="Enter Job Title" 
                                       class="form-control form-control-sm" required>
                            </div>
                        </div>

                        {{-- Job Type --}}
                        <div class="form-group mt-2">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <select name="type" class="form-control form-control-sm" required>
                                    <option value="">-- Select Job Type --</option>
                                    <option value="1" {{ (isset($jEdit) && $jEdit->type == 1) ? 'selected' : '' }}>Non-Teaching</option>
                                    <option value="2" {{ (isset($jEdit) && $jEdit->type == 2) ? 'selected' : '' }}>Teaching</option>
                                </select>
                            </div>
                        </div>

                        {{-- Plantilla Item No. --}}
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                </div>
                                <input type="text" name="plantilla_item_no" 
                                       value="{{ $current_route == 'jEdit' ? $jEdit->plantilla_item_no : '' }}" 
                                       placeholder="Enter Plantilla Item No." 
                                       class="form-control form-control-sm" required>
                            </div>
                        </div>

                        {{-- Salary --}}
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                </div>
                                <input type="number" step="0.01" name="salary" 
                                       value="{{ $current_route == 'jEdit' ? $jEdit->salary : '' }}" 
                                       placeholder="Enter Salary" 
                                       class="form-control form-control-sm" required>
                            </div>
                        </div>

                        {{-- Assignment --}}
                        <div class="form-group">
                            <textarea name="assignment" class="form-control form-control-sm" placeholder="Required Assignment">{{ $current_route == 'jEdit' ? $jEdit->assignment : '' }}</textarea>
                        </div>

                        {{-- Education --}}
                        <div class="form-group">
                            <textarea name="education" class="form-control form-control-sm" placeholder="Required Education">{{ $current_route == 'jEdit' ? $jEdit->education : '' }}</textarea>
                        </div>

                        {{-- Eligibility --}}
                        <div class="form-group">
                            <textarea name="eligibility" class="form-control form-control-sm" placeholder="Eligibility">{{ $current_route == 'jEdit' ? $jEdit->eligibility : '' }}</textarea>
                        </div>

                        {{-- Training --}}
                        <div class="form-group">
                            <textarea name="training" class="form-control form-control-sm" placeholder="Training (optional)">{{ $current_route == 'jEdit' ? $jEdit->training : '' }}</textarea>
                        </div>

                        {{-- Experience --}}
                        <div class="form-group">
                            <textarea name="experience" class="form-control form-control-sm" placeholder="Experience (optional)">{{ $current_route == 'jEdit' ? $jEdit->experience : '' }}</textarea>
                        </div>

                        {{-- Competency --}}
                        <div class="form-group">
                            <textarea name="competency" class="form-control form-control-sm" placeholder="Competency (optional)">{{ $current_route == 'jEdit' ? $jEdit->competency : '' }}</textarea>
                        </div>

                        {{-- Posted / Expiration --}}
                        <div class="form-group">
                            <label>Posted At</label>
                            <input type="date" name="posted_at" value="{{ $current_route == 'jEdit' ? $jEdit->posted_at : '' }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group">
                            <label>Expiration At</label>
                            <input type="date" name="expiration_at" value="{{ $current_route == 'jEdit' ? $jEdit->expiration_at : '' }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select type="text" name="status" class="form-control form-control-sm" required>
                                <option value="Open">Open</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        {{-- Save Button --}}
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN (Job List) --}}
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Position Title</th>
                                    <th>Plantilla No.</th>
                                    <th>Salary</th>
                                    <th>Assignment</th>
                                    <th>Requirements</th>
                                    <th>Posted</th>
                                    <th>Expiration</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                @php $no = 1; @endphp
                                @foreach($jobs as $job)
                                <tr id="tr-{{ $job->id }}">
                                    <td class="align-middle">{{ $no++ }}</td>
                                    <td class="align-middle">
                                        {{ $job->title }}<br>
                                        @if ($job->type == 1)
                                            <span class="badge bg-success">Non-Teaching</span>
                                        @else
                                            <span class="badge bg-primary">Teaching</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $job->plantilla_item_no }}</td>
                                    <td class="align-middle">₱{{ number_format($job->salary, 2) }}</td>
                                    <td class="align-middle">{{ $job->assignment }}</td>
                                    {{-- Combine education, eligibility, training, experience, competency --}}
                                    <td class="align-middle">
                                        <ul class="list-unstyled small mb-0">
                                            <li><strong>Education:</strong> {{ $job->education }}</li>
                                            <li><strong>Eligibility:</strong> {{ $job->eligibility }}</li>
                                            <li><strong>Training:</strong> {{ $job->training ?? '-' }}</li>
                                            <li><strong>Experience:</strong> {{ $job->experience ?? '-' }}</li>
                                            <li><strong>Competency:</strong> {{ $job->competency ?? '-' }}</li>
                                        </ul>
                                    </td>

                                    <td class="align-middle">{{ \Carbon\Carbon::parse($job->posted_at)->format('M d, Y') }}</td>
                                    <td class="align-middle">{{ \Carbon\Carbon::parse($job->expiration_at)->format('M d, Y') }}</td>

                                    <td class="align-middle">
                                        <span class="badge {{ $job->status == 'Open' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $job->status }}
                                        </span>
                                    </td>

                                    <td class="align-middle text-center">
                                        <a href="{{ route('jEdit', $job->id) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button value="{{ $job->id }}" class="btn btn-danger btn-sm job-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
@endsection
