@if(isset($guard) && auth()->guard($guard)->check())
    <style>
        [data-notification-count][hidden], [data-notification-mark-all][hidden] { display: none !important; }
    </style>
    <div hidden id="notificationRealtime"
         data-key="{{ config('broadcasting.connections.pusher.key') }}"
         data-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
         data-enabled="{{ config('broadcasting.default') === 'pusher' ? '1' : '0' }}"
         data-channel="{{ $guard === 'web' ? 'private-notifications.hr' : 'private-notifications.employee.' . auth()->guard($guard)->user()->id }}"
         data-auth="{{ url('/broadcasting/auth') }}"
         data-refresh="{{ route('notifications.refresh') }}"></div>
    @vite('resources/js/notifications.js')
@endif
