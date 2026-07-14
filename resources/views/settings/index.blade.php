@extends('layouts.master')

@section('body')
<style>
    .custom-label {
        width: 45px;
        padding: 0px;
        padding-left: 5px;
        text-align: center;
    }
    .settings-group {
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        background: #fff;
    }
    .group-header {
        margin: -1.25rem -1.25rem 1.25rem -1.25rem;
        padding: 0.75rem 1.25rem;
        background: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
        border-radius: 6px 6px 0 0;
        font-weight: 600;
        color: #2c3e50;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h2 class="card-title text-success1"><b>SYSTEM SETTINGS</b></h2>
                </div>

                <div class="card-body bg-form">

                    <!-- Group 1: Executive / Leadership Positions -->
                    <div class="settings-group">
                        <div class="group-header">Executive / Leadership Positions</div>
                        <div class="row">
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Mayor</label>
                                    <select id="mayor" name="mayor" class="form-control form-select select2">
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" {{ optional($setting ?? null)->mayor == $emp->id ? 'selected' : '' }}>{{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Approves leave applications.</small>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Vice Mayor</label>
                                    <select id="viceMayor" name="vice_mayor" class="form-control form-select select2">
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" {{ optional($setting ?? null)->vice_mayor == $emp->id ? 'selected' : '' }}>{{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">May approve leave when the Mayor is unavailable.</small>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">HR Head</label>
                                    <select id="hrHead" name="hr" class="form-control form-select select2">
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" {{ optional($setting ?? null)->hr == $emp->id ? 'selected' : '' }}>{{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group 2: Time & Attendance Settings -->
                    <div class="settings-group">
                        <div class="group-header">Time & Attendance Settings</div>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Time Entry Restriction</label>
                                    <select id="timerestriction" class="form-control form-select select2">
                                        <option value="0">None</option>
                                        <option value="1">Partial Restriction</option>
                                        <option value="2">Full Restriction</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">HR Kiosk Access</label>
                                    <select id="hrKioskAccess" class="form-control form-select select2" multiple>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->emp_ID }}"
                                                {{ in_array($emp->emp_ID, $kioskAccess ?? []) ? 'selected' : '' }}>
                                                {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">DTR Full Access</label>
                                    <select id="dtrFullAccess" class="form-control form-select select2" multiple>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}"
                                                {{ in_array($emp->id, $dtrFullAccess ?? []) ? 'selected' : '' }}>
                                                {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance stations: where face-portal punches are expected from.
                         Punches made elsewhere still record; they just carry a distance
                         flag on the Face Attendance monitor. -->
                    <div class="settings-group">
                        <div class="group-header">
                            Attendance Stations
                            <a href="{{ route('attendanceMonitor') }}" class="float-right" style="font-size: 12px;">
                                <i class="fas fa-street-view"></i> Open punch monitor
                            </a>
                        </div>

                        <p class="text-muted" style="font-size: 12.5px;">
                            Employees can clock in from anywhere. Each punch is compared against the
                            stations below, and anything outside every radius is flagged on the
                            Face Attendance monitor &mdash; the record settles the question, not a
                            conversation.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Latitude</th>
                                        <th>Longitude</th>
                                        <th>Radius (m)</th>
                                        <th class="text-center">Active</th>
                                        <th style="width: 120px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stations ?? [] as $station)
                                        <tr>
                                            {{-- form attribute keeps the edit form valid HTML: a <form>
                                                 cannot wrap table cells, so the inputs point at it by id. --}}
                                            <td>
                                                <form method="POST" action="{{ route('stationUpdate', $station->id) }}" id="station-edit-{{ $station->id }}">@csrf</form>
                                                <input form="station-edit-{{ $station->id }}" type="text" name="name" value="{{ $station->name }}" class="form-control form-control-sm" required>
                                            </td>
                                            <td><input form="station-edit-{{ $station->id }}" type="number" step="any" name="lat" value="{{ $station->lat }}" class="form-control form-control-sm" required></td>
                                            <td><input form="station-edit-{{ $station->id }}" type="number" step="any" name="lng" value="{{ $station->lng }}" class="form-control form-control-sm" required></td>
                                            <td><input form="station-edit-{{ $station->id }}" type="number" name="radius_m" value="{{ $station->radius_m }}" min="20" max="100000" class="form-control form-control-sm" required></td>
                                            <td class="align-middle text-center">
                                                <input form="station-edit-{{ $station->id }}" type="hidden" name="active" value="0">
                                                <input form="station-edit-{{ $station->id }}" type="checkbox" name="active" value="1" {{ $station->active ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-nowrap align-middle">
                                                <button form="station-edit-{{ $station->id }}" type="submit" class="btn btn-xs btn-success" title="Save changes">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                                <form method="POST" action="{{ route('stationDelete', $station->id) }}" class="d-inline"
                                                      onsubmit="return confirm('Remove station \'{{ $station->name }}\'? Past punches keep their record.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Remove station">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Add a new station --}}
                                    <tr>
                                        <td>
                                            <form method="POST" action="{{ route('stationStore') }}" id="station-add-form">@csrf</form>
                                            <input form="station-add-form" type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Municipal Hall" required>
                                        </td>
                                        <td><input form="station-add-form" type="number" step="any" name="lat" id="new-station-lat" class="form-control form-control-sm" placeholder="9.7292" required></td>
                                        <td><input form="station-add-form" type="number" step="any" name="lng" id="new-station-lng" class="form-control form-control-sm" placeholder="122.9080" required></td>
                                        <td><input form="station-add-form" type="number" name="radius_m" value="150" min="20" max="100000" class="form-control form-control-sm" required></td>
                                        <td class="align-middle text-center">
                                            <input form="station-add-form" type="checkbox" name="active" value="1" checked>
                                        </td>
                                        <td class="text-nowrap align-middle">
                                            <button form="station-add-form" type="submit" class="btn btn-xs btn-success" title="Add station">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary" id="use-my-location"
                                                    title="Fill coordinates from this device's location">
                                                <i class="fas fa-location-crosshairs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <script>
                        // Fill the new-station coordinates from wherever this browser is —
                        // the natural way to register the building you are standing in.
                        document.getElementById('use-my-location').addEventListener('click', function () {
                            var btn = this;

                            if (!navigator.geolocation) {
                                alert('This browser cannot read a location.');
                                return;
                            }

                            btn.disabled = true;

                            navigator.geolocation.getCurrentPosition(function (pos) {
                                document.getElementById('new-station-lat').value = pos.coords.latitude.toFixed(7);
                                document.getElementById('new-station-lng').value = pos.coords.longitude.toFixed(7);
                                btn.disabled = false;
                            }, function () {
                                alert('Could not read the location. Enter the coordinates manually.');
                                btn.disabled = false;
                            }, { enableHighAccuracy: true, timeout: 10000 });
                        });
                    </script>

                    <!-- Group 3: Email & Notification Settings -->
                    <div class="settings-group">
                        <div class="group-header">Email & Notification Settings</div>
                        <div class="row">
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">HR Head Email</label>
                                    <input type="email" id="hr-head-email" class="form-control form-control-sm" placeholder="Enter email">
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Records Office Email</label>
                                    <input type="email" id="records-office-email" class="form-control form-control-sm" placeholder="Enter email">
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Job Portal Email</label>
                                    <input type="email" id="job-portal-email" class="form-control form-control-sm" placeholder="Enter email">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group 4: System & Kiosk Controls -->
                    <div class="settings-group">
                        <div class="group-header">System & Kiosk Controls</div>
                        <div class="row">
                            <div class="col-6 col-md-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold mb-2">System Maintenance Mode</label>
                                    <input type="checkbox" id="maintenanceSwitch" data-bootstrap-switch
                                           data-off-color="danger" data-on-color="success">
                                </div>
                            </div>

                            <div class="col-6 col-md-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold mb-2">HR Kiosk Backtrack Sync</label>
                                    <input type="checkbox" id="kioskBacktrackSync" data-bootstrap-switch
                                           data-off-color="danger" data-on-color="success">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection