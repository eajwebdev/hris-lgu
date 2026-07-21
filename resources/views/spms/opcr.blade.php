@extends('layouts.master')

@section('body')
<div class="container-fluid">
    {{-- Header Banner --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card bg-gradient-dark text-white shadow-sm border-left-success">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 font-weight-bold text-success">
                                <i class="fas fa-building-flag mr-2"></i>SPMS - Office Performance Commitment &amp; Review (OPCR)
                            </h4>
                            <p class="mb-0 text-white-50">
                                Strategic Performance Management System &bull; Municipality of Mabinay
                            </p>
                        </div>
                        <span class="badge badge-success px-3 py-2 font-weight-bold">
                            <i class="fas fa-user-shield mr-1"></i> Office Head / Authorized Access
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Office & Period Filter Bar --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('spms.opcr') }}" class="form-inline justify-content-between">
                <div class="d-flex align-items-center">
                    <label class="mr-2 font-weight-bold text-muted">Office:</label>
                    <select name="office_id" class="form-control form-control-sm custom-select mr-3" onchange="this.form.submit()">
                        @foreach($managedOffices as $off)
                            <option value="{{ $off->id }}" {{ ($activeOffice && $activeOffice->id == $off->id) ? 'selected' : '' }}>
                                {{ $off->office_name }} ({{ $off->office_abbr }})
                            </option>
                        @endforeach
                    </select>

                    <label class="mr-2 font-weight-bold text-muted">Period:</label>
                    <select name="year" class="form-control form-control-sm custom-select mr-2" onchange="this.form.submit()">
                        @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <select name="semester" class="form-control form-control-sm custom-select" onchange="this.form.submit()">
                        <option value="1" {{ $semester == 1 ? 'selected' : '' }}>1st Semester (Jan - Jun)</option>
                        <option value="2" {{ $semester == 2 ? 'selected' : '' }}>2nd Semester (Jul - Dec)</option>
                    </select>
                </div>

                <div>
                    <button type="button" class="btn btn-success btn-sm px-3 font-weight-bold" data-toggle="modal" data-target="#addOpcrTargetModal">
                        <i class="fas fa-plus mr-1"></i> Add OPCR Target Output
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- OPCR Form Content --}}
    @if($opcr)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-tasks text-success mr-2"></i> {{ $activeOffice->office_name }} OPCR Targets
                    </h5>
                    <small class="text-muted">
                        Year: {{ $opcr->year }} &bull; Semester {{ $opcr->semester }} &bull; Status: <span class="badge badge-info">{{ $opcr->status }}</span>
                    </small>
                </div>

                {{-- Personnel Count Notice --}}
                <div>
                    <span class="badge badge-light border text-dark px-3 py-2">
                        <i class="fas fa-users text-success mr-1"></i> {{ $officeEmployees->count() }} Department Personnel
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="bg-dark text-white text-center">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 15%">Category</th>
                                <th style="width: 25%">Major Final Output (MFO / PAP)</th>
                                <th style="width: 25%">Success Indicators (Targets + Measures)</th>
                                <th style="width: 10%">Rating (Q/E/T/Ave)</th>
                                <th style="width: 20%">Cascading Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($opcr->targets as $index => $t)
                                <tr>
                                    <td class="text-center font-weight-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge {{ $t->category == 'Core Functions' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $t->category }}
                                        </span>
                                    </td>
                                    <td class="font-weight-bold text-dark">{!! nl2br(e($t->mfo_pap)) !!}</td>
                                    <td class="text-muted">{!! nl2br(e($t->success_indicators)) !!}</td>
                                    <td class="text-center">
                                        @if($t->rating_ave)
                                            <span class="badge badge-success px-2 py-1 font-weight-bold">{{ number_format($t->rating_ave, 2) }}</span>
                                        @else
                                            <span class="text-muted small">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-success font-weight-bold px-2"
                                                data-toggle="modal"
                                                data-target="#cascadeModal{{ $t->id }}">
                                            <i class="fas fa-sitemap mr-1"></i> Cascade to Employee
                                        </button>
                                    </td>
                                </tr>

                                {{-- Cascade Target Modal per Target --}}
                                <div class="modal fade" id="cascadeModal{{ $t->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content shadow-lg">
                                            <div class="modal-header bg-success text-white py-2">
                                                <h5 class="modal-title font-weight-bold text-white">
                                                    <i class="fas fa-sitemap mr-2"></i> Cascade Target to Office Employee
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('spms.opcr.cascade') }}">
                                                @csrf
                                                <input type="hidden" name="opcr_target_id" value="{{ $t->id }}">
                                                
                                                <div class="modal-body text-left">
                                                    <div class="alert alert-info py-2 small mb-3">
                                                        <i class="fas fa-shield-alt mr-1"></i> <strong>Strict Office Scoping Active:</strong> You can only assign targets to employees who belong to <strong>{{ $activeOffice->office_name }}</strong>.
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">Target Output:</label>
                                                        <p class="text-muted small bg-light p-2 rounded border mb-0">{!! nl2br(e($t->mfo_pap)) !!}</p>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">Select Employee ({{ $activeOffice->office_abbr }} Department):</label>
                                                        <select name="employee_id" class="form-control custom-select" required>
                                                            <option value="">-- Choose Employee --</option>
                                                            @foreach($officeEmployees as $emp)
                                                                <option value="{{ $emp->id }}">
                                                                    {{ $emp->lname }}, {{ $emp->fname }} ({{ $emp->position ?? 'Personnel' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="modal-footer bg-light py-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success btn-sm font-weight-bold px-3">
                                                        <i class="fas fa-paper-plane mr-1"></i> Assign / Cascade Target
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
                                        No OPCR target outputs added for this period yet. Click <strong>"Add OPCR Target Output"</strong> to begin.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Add OPCR Target Modal --}}
<div class="modal fade" id="addOpcrTargetModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title font-weight-bold text-success">
                    <i class="fas fa-plus-circle mr-2"></i> Add OPCR Target Output
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('spms.opcr.target.store') }}">
                @csrf
                <input type="hidden" name="opcr_id" value="{{ $opcr?->id }}">

                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Category:</label>
                        <select name="category" class="form-control custom-select" required>
                            <option value="Core Functions">Core Functions</option>
                            <option value="Support Functions">Support Functions</option>
                            <option value="Strategic Functions">Strategic Functions</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Major Final Output (MFO / PAP):</label>
                        <textarea name="mfo_pap" class="form-control" rows="3" placeholder="Enter Major Final Output description..." required></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Success Indicators (Targets + Measures):</label>
                        <textarea name="success_indicators" class="form-control" rows="3" placeholder="e.g. 100% of reports submitted on time with high accuracy" required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Save Target Output
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
