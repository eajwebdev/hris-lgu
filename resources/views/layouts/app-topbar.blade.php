{{--
    Top bar for layouts/app: sidebar toggle and breadcrumb on the left;
    applications, notifications, interview ratings and the account menu on
    the right.

    The breadcrumb is read off the menu (breadcrumb_trail), so a page has one
    as soon as it is in the sidebar. A view names what is open beneath that
    with @section('breadcrumb', 'Maria Santos').

    It is the top row of the header banner in layouts/app, so everything
    here is cream on dark; the dropdown panels are white and set their own
    ink colour.

    The dropdowns are plain panels toggled by the shell script
    (layouts/app-scripts): a button with data-menu-button and aria-controls
    opens the element it names.
--}}
@php
    $isEmployeeBar = $guard !== 'web';
    $crumbs = breadcrumb_trail($menu);

    // Administrators get the HR feed, ten at a time. An employee gets their
    // own unread rows and the total unread count.
    $barNotifications = $isEmployeeBar ? unread_employee_notifications($notifications1, $guard) : $notifications;
    $barNotificationCount = $isEmployeeBar ? $notificationsCount1 : $notificationsCount;

    $barButton = 'relative grid size-10 cursor-pointer place-items-center rounded-xl text-cream/80 transition-colors hover:bg-cream/12 hover:text-cream focus-visible:outline-2 focus-visible:outline-sun-500 aria-expanded:bg-cream/15 aria-expanded:text-cream';
    $barBadge  = 'absolute -top-0.5 -right-0.5 grid h-4.5 min-w-4.5 place-items-center rounded-full px-1 text-[10px] font-semibold tabular-nums';
    $barPanel  = 'fixed inset-x-3 top-20 z-40 overflow-hidden rounded-2xl border border-line bg-surface text-ink shadow-xl shadow-forest-950/10 sm:absolute sm:inset-x-auto sm:top-full sm:right-0 sm:mt-2';
@endphp
<header class="flex h-16 shrink-0 items-center gap-1 px-4 sm:px-6">
    <button type="button" id="sidebarToggle" aria-controls="appSidebar" aria-label="Toggle navigation" class="{{ $barButton }} -ml-2">
        <i class="fas fa-bars"></i>
    </button>

    {{-- On a phone only the page itself is named; the steps above it are a
         tap away in the menu. --}}
    <nav aria-label="Breadcrumb" class="min-w-0">
        <ol class="flex min-w-0 items-center gap-1.5 text-cream/65">
            <li class="shrink-0">
                <a href="{{ route('dashboard') }}" title="Dashboard" aria-label="Dashboard"
                   class="grid size-8 place-items-center rounded-lg transition-colors hover:bg-cream/12 hover:text-cream focus-visible:outline-2 focus-visible:outline-sun-500">
                    <i class="fas fa-house text-xs"></i>
                </a>
            </li>

            @foreach($crumbs as $crumb)
                @php
                    $isPage = $loop->last && !View::hasSection('breadcrumb');
                @endphp
                <li class="flex min-w-0 items-center gap-1.5 {{ $isPage ? '' : 'max-sm:hidden' }}">
                    <i class="fas fa-angle-right text-[10px] text-cream/35"></i>
                    @if($isPage)
                        <span aria-current="page" class="truncate font-medium text-cream">{{ $crumb['label'] }}</span>
                    @elseif($crumb['url'])
                        <a href="{{ $crumb['url'] }}" class="truncate rounded underline-offset-2 hover:text-cream hover:underline focus-visible:outline-2 focus-visible:outline-sun-500">{{ $crumb['label'] }}</a>
                    @else
                        <span class="truncate">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach

            @hasSection('breadcrumb')
                <li class="flex min-w-0 items-center gap-1.5">
                    <i class="fas fa-angle-right text-[10px] text-cream/35"></i>
                    <span aria-current="page" class="truncate font-medium text-cream">@yield('breadcrumb')</span>
                </li>
            @endif
        </ol>
    </nav>

    <div class="ml-auto flex shrink-0 items-center gap-1 pl-3">
        <p class="mr-2 hidden text-cream/65 lg:block">{{ now('Asia/Manila')->format('l, F j, Y') }}</p>

        {{-- Theme. The two icons are wrapped because Font Awesome's own
             display rule beats a display utility on the <i> itself. --}}
        <button type="button" id="themeToggle" aria-label="Switch to dark theme" title="Switch theme" class="{{ $barButton }}">
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
                    class="ml-1 flex cursor-pointer items-center gap-2 rounded-full p-0.5 pr-2 transition-colors hover:bg-cream/12 focus-visible:outline-2 focus-visible:outline-sun-500 aria-expanded:bg-cream/15">
                <img src="{{ $accountPhoto }}" alt="" class="size-9 rounded-full object-cover ring-2 ring-cream/25">
                <i class="fas fa-angle-down text-xs text-cream/60"></i>
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
