{{--
    Top bar for layouts/app: sidebar toggle and search on the left;
    applications, notifications, interview ratings and the account menu on
    the right. The breadcrumb is in the banner beneath it (layouts/app-frame).

    It sits on the banner photo, and on solid green once the page scrolls, so
    the toggle is white and everything else is a white chip; the dropdown
    panels are white and set their own ink colour.

    The dropdowns are plain panels toggled by the shell script
    (layouts/app-scripts): a button with data-menu-button and aria-controls
    opens the element it names.
--}}
@php
    $isEmployeeBar = $guard !== 'web';

    // Administrators get the HR feed, ten at a time. An employee gets their
    // own unread rows and the total unread count.
    $barNotifications = $isEmployeeBar ? unread_employee_notifications($notifications1, $guard) : $notifications;
    $barNotificationCount = $isEmployeeBar ? $notificationsCount1 : $notificationsCount;

    $barButton = 'relative grid size-10 cursor-pointer place-items-center rounded-full bg-white/95 text-forest-900 shadow-sm shadow-forest-950/20 transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-sun-500 aria-expanded:bg-forest-100';
    $barBadge  = 'absolute -top-0.5 -right-0.5 grid h-4.5 min-w-4.5 place-items-center rounded-full px-1 text-[10px] font-semibold tabular-nums';
    $barPanel  = 'fixed inset-x-3 top-20 z-40 overflow-hidden rounded-2xl border border-line bg-surface text-ink shadow-xl shadow-forest-950/10 sm:absolute sm:inset-x-auto sm:top-full sm:right-0 sm:mt-2';
@endphp
<header class="flex h-19 shrink-0 items-center gap-2">
    <button type="button" id="sidebarToggle" aria-controls="appSidebar" aria-label="Toggle navigation"
            class="-ml-1 grid size-10 cursor-pointer place-items-center rounded-xl text-lg text-white transition-colors hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-sun-500">
        <i class="fas fa-bars"></i>
    </button>

    <label class="relative ml-1 hidden min-w-0 max-w-md flex-1 md:block">
        <span class="sr-only">Search the HRIS</span>
        <i class="fas fa-magnifying-glass pointer-events-none absolute top-1/2 left-5 -translate-y-1/2 text-forest-900"></i>
        <input type="search" id="globalSearch" placeholder="Search employee, office, or report..." autocomplete="off"
               class="h-11 w-full rounded-full border border-white/60 bg-white/95 pr-5 pl-12 text-[15px] text-ink shadow-sm shadow-forest-950/20 outline-none transition placeholder:text-ink/50 focus:border-white focus:bg-white focus:ring-4 focus:ring-white/25">
    </label>

    <div class="ml-auto flex shrink-0 items-center gap-2 pl-3">
        {{-- Theme. The two icons are wrapped because Font Awesome's own
             display rule beats a display utility on the <i> itself. --}}
        <button type="button" id="themeToggle" aria-label="Switch to dark theme" title="Switch theme" class="{{ $barButton }} hidden">
            <span class="dark:hidden"><i class="fas fa-moon"></i></span>
            <span class="hidden dark:inline"><i class="fas fa-sun"></i></span>
        </button>

        {{-- Job applications --}}
        @if(sees_job_applications($guard))
            <div class="sm:relative">
                <button type="button" data-menu-button aria-controls="applicationsMenu" aria-expanded="false" title="Job Applications" class="{{ $barButton }}">
                    <i class="fas fa-envelope"></i>
                    @if($jobapplication->count() > 0)
                        <span class="{{ $barBadge }} bg-red-600 text-white">{{ $jobapplication->count() }}</span>
                    @endif
                </button>

                <div id="applicationsMenu" data-menu hidden class="{{ $barPanel }} sm:w-96">
                    <p class="border-b border-line px-4 py-3 font-medium">{{ $jobapplication->count() }} New Applications</p>

                    <div class="max-h-96 divide-y divide-line overflow-y-auto">
                        @foreach($jobapplication->take(6) as $jobs)
                            <a href="{{ route('viewApplication', $jobs->id) }}" target="_blank" class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-paper">
                                <span class="min-w-0 flex-1 leading-snug">
                                    <strong class="font-semibold">
                                        {{ $jobs->first_name }}
                                        {{ !empty($jobs->middle_name) ? strtoupper(substr($jobs->middle_name, 0, 1)).'.' : '' }}
                                        {{ $jobs->last_name }}
                                    </strong>
                                    is applying for
                                    <strong class="font-semibold">{{ $jobs->title }}</strong>
                                    <span class="block truncate text-xs text-ink/50">{{ $jobs->email }}</span>
                                </span>
                                @if($jobs->checked == 1)
                                    <i class="fas fa-check-circle text-lg text-forest-600" title="Reviewed"></i>
                                @else
                                    <i class="fas fa-check-circle text-lg text-ink/25" title="Pending"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <a href="{{ route('viewAllApplication') }}" class="block border-t border-line px-4 py-3 text-center font-medium text-forest-700 transition-colors hover:bg-paper">
                        <i class="fas fa-list mr-1"></i> View All Applications
                    </a>
                </div>
            </div>
        @endif

        {{-- Notifications --}}
        <div class="sm:relative" data-notification-bell>
            <button type="button" data-menu-button aria-controls="notificationsMenu" aria-expanded="false" title="Notifications" class="{{ $barButton }}">
                <i class="fas fa-bell"></i>
                <span data-notification-count class="{{ $barBadge }} bg-sun-500 text-ink" @if($barNotificationCount == 0) hidden @endif>{{ $barNotificationCount }}</span>
            </button>

            <div id="notificationsMenu" data-menu hidden class="{{ $barPanel }} sm:w-[28rem]">
                @include('partials.notification_feed', ['legacy' => false])
            </div>
        </div>

        {{-- Interview ratings waiting on this person. Kept in step by the
             shell script, which polls interviewAssignmentsStatus. --}}
        <a href="{{ route('interviewAssignments') }}" id="interviewRatingNavLink" title="Interview Ratings"
           data-interview-status="{{ route('interviewAssignmentsStatus') }}"
           class="{{ $barButton }}" @if(($activeInterviewRatingCount ?? 0) <= 0) hidden @endif>
            <i class="fas fa-comments"></i>
            <span class="{{ $barBadge }} bg-red-600 text-white" id="interviewRatingBadge">{{ $activeInterviewRatingCount ?? 0 }}</span>
        </a>

        {{-- Account --}}
        <div class="sm:relative">
            <button type="button" data-menu-button aria-controls="accountMenu" aria-expanded="false" aria-label="Account"
                    class="ml-1 flex cursor-pointer items-center gap-3 rounded-full bg-white/95 p-1 pr-3 text-left shadow-sm shadow-forest-950/20 transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-sun-500 aria-expanded:bg-forest-100">
                <img src="{{ $accountPhoto }}" alt="" class="size-9 rounded-full object-cover ring-2 ring-forest-100">
                <span class="hidden min-w-0 leading-tight xl:block">
                    <strong class="block max-w-44 truncate text-sm font-semibold text-ink">{{ $accountName }}</strong>
                    <span class="block max-w-44 truncate text-xs text-ink/55">{{ $accountRole }}</span>
                </span>
                <i class="fas fa-angle-down text-xs text-forest-900"></i>
            </button>

            <div id="accountMenu" data-menu hidden class="{{ $barPanel }} sm:w-64">
                <div class="border-b border-line px-4 py-3 leading-tight">
                    <p class="truncate font-medium">{{ $accountName }}</p>
                    <p class="truncate text-xs text-ink/55">{{ $accountRole }}</p>
                </div>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="p-1.5">
                    @csrf
                    <button type="submit" class="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-left transition-colors hover:bg-paper">
                        <i class="fas fa-power-off text-xs text-ink/50"></i> Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
