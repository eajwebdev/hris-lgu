@php
    $employeeFeed = $guard === 'employee';
    $rows = $employeeFeed ? unread_employee_notifications($notifications1, $guard) : $notifications;
    $count = $employeeFeed ? $notificationsCount1 : $notificationsCount;
    $legacy = $legacy ?? false;
@endphp
<div class="{{ $legacy ? 'dropdown-item dropdown-header d-flex justify-content-between align-items-center' : 'flex items-center justify-between gap-4 border-b border-line px-4 py-3' }}">
    <p class="{{ $legacy ? 'mb-0' : 'font-medium' }}" data-notification-heading aria-live="polite">{{ $count ?: 'No' }} Notifications</p>
    <form method="POST" action="{{ route('notifications.markAllRead') }}" data-notification-mark-all @if($count == 0) hidden @endif>
        @csrf
        <button type="submit" class="{{ $legacy ? 'btn btn-link btn-sm text-success p-0' : 'cursor-pointer text-xs font-medium text-forest-700 underline-offset-2 hover:underline' }}">Mark all as read</button>
    </form>
</div>
<div id="notifications-container" class="{{ $legacy ? '' : 'max-h-96 overflow-y-auto [scrollbar-width:thin]' }}"
     @if($legacy) style="max-height: 400px; overflow-y: auto;" @endif
     @unless($employeeFeed) data-load-more="{{ route('notificationload') }}" @endunless>
    @include($employeeFeed ? 'partials.notification_items_employee' : 'partials.notification_items', ['notifications' => $rows])
    @if($rows->isEmpty())
        <p class="{{ $legacy ? 'text-center text-muted p-3 mb-0' : 'px-4 py-8 text-center text-ink/55' }}">No notifications yet.</p>
    @endif
</div>
