import Pusher from 'pusher-js';

const config = document.getElementById('notificationRealtime');
const bell = document.querySelector('[data-notification-bell]');

if (config && bell) {
    const panel = bell.querySelector('#notificationsMenu, [data-notification-panel]');
    const badge = bell.querySelector('[data-notification-count]');
    const headers = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let refreshing = false;
    let pending = false;
    let timer;

    function paging() {
        const feed = panel.querySelector('[data-load-more]');
        if (!feed) return;
        let offset = 10;
        let loading = false;
        let finished = false;
        feed.addEventListener('scroll', async () => {
            if (loading || finished || feed.scrollTop + feed.clientHeight < feed.scrollHeight - 5) return;
            loading = true;
            try {
                const response = await fetch(`${feed.dataset.loadMore}?offset=${offset}`, { headers });
                if (!response.ok) return;
                const data = await response.json();
                if (!feed.isConnected) return;
                if (data.stop || !data.html) { finished = true; return; }
                feed.insertAdjacentHTML('beforeend', data.html);
                offset = data.nextOffset;
            } catch (error) {
                console.warn('Could not load more notifications.', error);
            } finally {
                loading = false;
            }
        });
    }

    async function refresh() {
        if (refreshing) { pending = true; return; }
        refreshing = true;
        try {
            const url = new URL(config.dataset.refresh);
            url.searchParams.set('legacy', panel.id === 'notificationsMenu' ? '0' : '1');
            const response = await fetch(url, { headers, cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            const scrollTop = panel.querySelector('#notifications-container')?.scrollTop || 0;
            panel.innerHTML = data.html;
            badge.textContent = data.count;
            badge.hidden = data.count === 0;
            panel.querySelector('#notifications-container').scrollTop = scrollTop;
            paging();
        } catch (error) {
            console.warn('Could not refresh notifications.', error);
        } finally {
            refreshing = false;
            if (pending) { pending = false; schedule(); }
        }
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(refresh, 150);
    }

    paging();
    panel.addEventListener('submit', async (event) => {
        if (!event.target.matches('[data-notification-mark-all]')) return;
        event.preventDefault();
        const form = event.target;
        const button = form.querySelector('button');
        button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', headers: { ...headers, 'X-CSRF-TOKEN': csrf }, body: new FormData(form),
            });
            if (!response.ok) throw new Error('Could not mark notifications as read.');
            schedule();
        } catch (error) {
            console.warn(error);
            button.disabled = false;
        }
    });

    // Refresh on reconnect and focus to recover any changes made while this tab was offline.
    window.addEventListener('focus', schedule);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) schedule(); });
    setInterval(() => { if (!document.hidden) schedule(); }, 60000);

    if (config.dataset.enabled === '1' && config.dataset.key) {
        const pusher = new Pusher(config.dataset.key, {
            cluster: config.dataset.cluster,
            forceTLS: true,
            channelAuthorization: { endpoint: config.dataset.auth, headers: { 'X-CSRF-TOKEN': csrf } },
        });
        const channel = pusher.subscribe(config.dataset.channel);
        channel.bind('notifications.changed', schedule);
        channel.bind('pusher:subscription_succeeded', schedule);
        channel.bind('pusher:subscription_error', error => console.warn('Notification subscription failed.', error));
        window.hrisNotifications = { pusher, channel, refresh };
    }
}
