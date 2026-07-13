@extends('layouts.master')

@section('body')
<style>
    .assignment-page .card { border:0; border-radius:18px; box-shadow:0 9px 30px rgba(15,23,42,.07); }
    .assignment-card { align-items:center; border:1px solid #e5e7eb; border-radius:16px; display:flex; flex-wrap:wrap; gap:14px; justify-content:space-between; padding:16px; }
    .assignment-card + .assignment-card { margin-top:12px; }
    .assignment-avatar { align-items:center; background:#e8f6ee; border-radius:14px; color:#198754; display:flex; font-weight:800; height:48px; justify-content:center; width:48px; }
</style>

@php
    $assignmentKey = $assignmentKey ?? $ratings
        ->map(fn ($rating) => $rating->interview_id . ':' . $rating->application_id . ':' . optional($rating->interview->updated_at)->timestamp)
        ->implode('|');
@endphp
<div class="container-fluid assignment-page" data-current-count="{{ $ratings->count() }}" data-assignment-key="{{ $assignmentKey }}">
    <div class="card">
        <div class="card-body">
            <h4 class="font-weight-bold mb-1"><i class="fas fa-comments mr-1"></i> My Interview Ratings</h4>
            <p class="text-muted mb-4">Only currently cast candidates assigned to you are shown here.</p>

            @forelse($ratings as $rating)
                @php $app = $rating->application; @endphp
                <div class="assignment-card">
                    <div class="d-flex align-items-center" style="gap:12px;">
                        <div class="assignment-avatar">{{ strtoupper(substr($app->first_name ?? 'A', 0, 1).substr($app->last_name ?? '', 0, 1)) }}</div>
                        <div>
                            <strong>{{ trim($app->first_name.' '.$app->middle_name.' '.$app->last_name) }}</strong>
                            <div class="text-muted small">{{ $app->app_number }}</div>
                            <div class="text-muted small">{{ $rating->interview->job->title ?? 'Position' }}</div>
                            @if($rating->interview->job && $rating->interview->job->plantilla_item_no)
                                <div class="text-muted small">{{ $rating->interview->job->plantilla_item_no }}</div>
                            @endif
                            <div class="text-muted small">{{ $rating->interview->eteEvaluation->office->office_name ?? '' }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        @if($rating->submitted_at)
                            <span class="badge badge-success mb-2">Submitted {{ $rating->submitted_at->format('M d, Y h:i A') }}</span>
                        @else
                            <span class="badge badge-warning mb-2">Pending</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="fas fa-user-clock fa-2x mb-3"></i>
                    <div>No candidate is currently cast for your interview panel.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.assignment-page');
    const initialCount = parseInt(page.dataset.currentCount || '0', 10);
    const initialAssignmentKey = page.dataset.assignmentKey || '';
    let checkRunning = false;

    function refreshWhenChanged() {
        if (checkRunning) {
            return;
        }

        checkRunning = true;
        fetch("{{ route('interviewAssignmentsStatus') }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        })
            .then(response => response.json())
            .then(data => {
                const count = parseInt(data.count || 0, 10);
                const assignmentKey = data.assignment_key || '';

                if (count !== initialCount || assignmentKey !== initialAssignmentKey) {
                    window.location.reload();
                }
            })
            .catch(() => {})
            .finally(() => {
                checkRunning = false;
            });
    }

    refreshWhenChanged();
    window.addEventListener('focus', refreshWhenChanged);
    window.addEventListener('pageshow', refreshWhenChanged);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            refreshWhenChanged();
        }
    });
    setInterval(refreshWhenChanged, 1500);
});
</script>
@endsection
