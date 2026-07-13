@php
    $isDtrActive  = request()->is('dtr');
    $isLogsActive = request()->is('dtr/dtr-logs');
@endphp

<div class="page-tabs page-tabs--stacked">
    <a href="{{ route('dtr-read') }}" class="page-tab {{ $isDtrActive ? 'active' : '' }}" id="allButton">
        <i class="fas fa-clock"></i>
        <span>DTR</span>
    </a>

    <a href="{{ route('dtrLogs') }}" class="page-tab {{ $isLogsActive ? 'active' : '' }}" id="ppeButton">
        <i class="fas fa-file-lines"></i>
        <span>LOGS</span>
    </a>
</div>
