@extends('layouts.master')

@section('body')
<style>
    .punch-badge { font-size: 11.5px; font-weight: 600; }
    .stat-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 13px;
        border-radius: 10px;
        font-size: 12.5px;
        font-weight: 600;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        margin-right: 8px;
    }
    .stat-chip--red   { background: #FEF2F2; border-color: #FECACA; color: #B91C1C; }
    .stat-chip--amber { background: #FFFBEB; border-color: #FDE68A; color: #92400E; }
</style>

<section class="content">
<div class="container-fluid">
    <div class="card card-outline card-success">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <h2 class="card-title text-success1"><b>FACE ATTENDANCE MONITOR</b></h2>

            <form method="GET" action="{{ route('attendanceMonitor') }}" class="form-inline mb-0">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm mr-2"
                       onchange="this.form.submit()">
                <noscript><button class="btn btn-sm btn-success">Go</button></noscript>
            </form>
        </div>

        <div class="card-body">

            <div class="mb-3">
                <span class="stat-chip"><i class="fas fa-fingerprint"></i> {{ $logs->count() }} punch(es)</span>
                <span class="stat-chip {{ $flagged ? 'stat-chip--red' : '' }}">
                    <i class="fas fa-location-arrow"></i> {{ $flagged }} out of range
                </span>
                <span class="stat-chip {{ $unlocated ? 'stat-chip--amber' : '' }}">
                    <i class="fas fa-location-crosshairs"></i> {{ $unlocated }} without location
                </span>
            </div>

            @if($stations->isEmpty())
                <div class="alert alert-warning py-2">
                    <i class="fas fa-triangle-exclamation"></i>
                    No attendance stations are configured, so punches cannot be judged near or far.
                    Add stations under <a href="{{ route('settings') }}">Settings &rarr; Attendance Stations</a>.
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Employee</th>
                            <th>Action</th>
                            <th>Mode</th>
                            <th>Location</th>
                            <th class="text-right">GPS accuracy</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at->format('g:i:s A') }}</td>
                                <td>
                                    <b>{{ $log->employee ? trim($log->employee->fname . ' ' . $log->employee->lname) : $log->emp_ID }}</b>
                                    <div class="text-muted" style="font-size: 11px;">{{ $log->employee->position ?? '' }}</div>
                                </td>
                                <td>
                                    <span class="badge punch-badge {{ $log->action === 'out' ? 'badge-warning' : 'badge-success' }}">
                                        {{ $log->action === 'out' ? 'CLOCK OUT' : 'CLOCK IN' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge punch-badge badge-light border">
                                        <i class="fas {{ $log->mode === 'qr' ? 'fa-qrcode' : 'fa-user' }}"></i>
                                        {{ strtoupper($log->mode) }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->lat === null)
                                        <span class="badge punch-badge badge-warning">
                                            <i class="fas fa-location-crosshairs"></i> No location shared
                                        </span>
                                    @elseif($log->out_of_range === true)
                                        <span class="badge punch-badge badge-danger">
                                            <i class="fas fa-location-arrow"></i>
                                            {{ $log->distance_m >= 1000 ? number_format($log->distance_m / 1000, 1) . ' km' : $log->distance_m . ' m' }}
                                            from {{ $log->station_name }}
                                        </span>
                                        <a href="https://www.google.com/maps?q={{ $log->lat }},{{ $log->lng }}"
                                           target="_blank" rel="noopener" class="ml-1" title="View on map">
                                            <i class="fas fa-map-location-dot"></i>
                                        </a>
                                    @elseif($log->out_of_range === false)
                                        <span class="badge punch-badge badge-success">
                                            <i class="fas fa-location-dot"></i> {{ $log->station_name }}
                                        </span>
                                    @else
                                        <span class="badge punch-badge badge-secondary">
                                            <i class="fas fa-location-dot"></i> Recorded (no stations to compare)
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right text-muted">
                                    {{ $log->accuracy_m !== null ? '±' . $log->accuracy_m . ' m' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No portal punches on {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
</section>
@endsection
