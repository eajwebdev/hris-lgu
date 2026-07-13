<div class="card card-info card-outline p-1">
    <h5 class="card-title" style="font-size: 17pt"></h5>
    <ul class="nav nav-pills nav-sidebar nav-compact flex-column">
        <li class="nav-item mb-1">
            <a href="{{ route('dtr-read') }}" class="nav-link2 {{ request()->is('dtr') ? 'active1' : '' }}" id="allButton">
                <i class="fas fa-clock {{ request()->is('dtr') ? 'text-success1' : 'text-muted' }}"></i>
                <span class="ml-2 {{ request()->is('dtr') ? 'text-success1' : 'text-muted' }}">DTR</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="{{ route('dtrLogs') }}" class="nav-link2 {{ request()->is('dtr/dtr-logs') ? 'active1' : '' }}" id="ppeButton">
                <i class="fas fa-file-alt {{ request()->is('dtr/dtr-logs') ? 'text-success1' : 'text-muted' }}"></i>
                <span class="ml-2 {{ request()->is('dtr/dtr-logs') ? 'text-success1' : 'text-muted' }}">LOGS</span>
            </a>
        </li>
    </ul>                     
</div>
