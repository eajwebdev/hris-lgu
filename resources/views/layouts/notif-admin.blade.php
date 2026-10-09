<!-- Job Application Notifications -->
{{-- Who sees these is decided by sees_job_applications() in
     app/Helpers/helpers.php, shared with the Tailwind shell. --}}
@if(sees_job_applications($guard))
<li class="nav-item dropdown">
    <a class="nav-link" href="#" data-toggle="dropdown" title="Job Applications">
        <i class="fas fa-envelope text-success1"></i>
        <span class="badge badge-danger navbar-badge">{{ $jobapplication->count() }}</span>
    </a>

    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">
           {{ $jobapplication->count() }} New Applications
        </span>

        <div class="dropdown-divider"></div>

        @foreach($jobapplication->take(6) as $jobs)
            <a href="{{ route('viewApplication', $jobs->id) }}" target="_blank" class="dropdown-item" style="white-space: normal;">
                <div class="d-flex align-items-center w-100">

                    <div style="flex:1; min-width:0; padding-right:12px; line-height:1.3; word-break:break-word;">
                        <strong>
                            {{ $jobs->first_name }}
                            {{ !empty($jobs->middle_name) ? strtoupper(substr($jobs->middle_name, 0, 1)).'.' : '' }}
                            {{ $jobs->last_name }}
                        </strong>

                        is applying for
                        <strong>{{ $jobs->title }}</strong>

                        <br>

                        <small class="text-muted d-block text-truncate">
                            {{ $jobs->email }}
                        </small>
                    </div>

                    <div style="flex:0 0 28px; width:28px; text-align:center; align-self:center;">
                        @if($jobs->checked == 1)
                            <i class="fas fa-check-circle text-success fa-lg" title="Reviewed"></i>
                        @else
                            <i class="fas fa-check-circle text-secondary fa-lg" title="Pending"></i>
                        @endif
                    </div>

                </div>
            </a>

            <div class="dropdown-divider"></div>
        @endforeach
        <a href="{{ route('viewAllApplication') }}" class="dropdown-item dropdown-footer">
            <i class="fas fa-list mr-1"></i> View All Applications
        </a>
    </div>
</li>
@endif
<li class="nav-item dropdown" data-notification-bell>
    <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
        <i class="fas fa-bell text-success1"></i>
        <span class="badge badge-warning navbar-badge" data-notification-count @if($notificationsCount == 0) hidden @endif>{{ $notificationsCount }}</span>
    </a>
    <div class="dropdown-menu notifications dropdown-notification dropdown-menu-lg dropdown-menu-right" data-notification-panel style="left: inherit; right: 0;">
        @include('partials.notification_feed', ['legacy' => true])
    </div>
</li>
