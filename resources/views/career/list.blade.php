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
                                    <option value="">-- Nature of Appointment --</option>
                                    @foreach($types as $value => $label)
                                        <option value="{{ $value }}" {{ (isset($jEdit) && $jEdit->type == $value) ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Position Description (DBM-CSC Form No. 1).
                             Selecting one copies its qualification standards into the
                             fields below, so a posting and the signed description of the
                             item cannot state different requirements. --}}
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-file-alt"></i></span>
                                </div>
                                <select name="position_description_id" id="pd-picker" class="form-control form-control-sm">
                                    <option value="">-- No Position Description linked --</option>
                                    @foreach($descriptions as $pd)
                                        <option value="{{ $pd->id }}"
                                                data-title="{{ $pd->position_title }}"
                                                data-item="{{ $pd->item_number }}"
                                                data-education="{{ $pd->qs_education }}"
                                                data-eligibility="{{ $pd->qs_eligibility }}"
                                                data-training="{{ $pd->qs_training }}"
                                                data-experience="{{ $pd->qs_experience }}"
                                                {{ (isset($jEdit) && $jEdit->position_description_id == $pd->id) ? 'selected' : '' }}>
                                            {{ $pd->full_title }}{{ $pd->item_number ? ' — '.$pd->item_number : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <small class="form-text text-muted">
                                <a href="{{ route('positionDescriptionList') }}" target="_blank">Manage position descriptions</a>
                            </small>
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
                                        <span class="badge badge-info">{{ $job->type }}</span>
                                        @if($job->positionDescription)
                                            <a href="{{ route('positionDescriptionPrint', $job->position_description_id) }}"
                                               target="_blank" class="badge badge-light border"
                                               title="DBM-CSC Form No. 1 for this item">
                                                <i class="far fa-file-alt"></i> PDF
                                            </a>
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

                                    <td class="align-middle text-center text-nowrap">
                                        <a href="{{ route('psbAssessment', $job->id) }}"
                                           class="btn btn-success btn-sm"
                                           title="Comparative Assessment ({{ $job->applications_count }} applicant{{ $job->applications_count == 1 ? '' : 's' }})">
                                            <i class="fas fa-scale-balanced"></i>
                                            @if($job->comparativeAssessment && $job->comparativeAssessment->finalised_at)
                                                <i class="fas fa-lock fa-xs"></i>
                                            @endif
                                        </a>
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

<script>
(function () {
    var picker = document.getElementById('pd-picker');
    if (!picker) return;

    var form = picker.closest('form');

    /* Selecting a Position Description copies its qualification standards onto
       the posting. Only blank fields are filled on load; choosing a different
       description overwrites them, because the posting must not advertise
       requirements the signed description does not carry. */
    picker.addEventListener('change', function () {
        var opt = picker.options[picker.selectedIndex];
        if (!opt || !opt.value) return;

        var map = {
            title:       opt.dataset.title,
            plantilla_item_no: opt.dataset.item,
            education:   opt.dataset.education,
            eligibility: opt.dataset.eligibility,
            training:    opt.dataset.training,
            experience:  opt.dataset.experience
        };

        Object.keys(map).forEach(function (name) {
            var field = form.querySelector('[name="' + name + '"]');
            if (field && map[name]) { field.value = map[name]; }
        });
    });
})();
</script>
@endsection
