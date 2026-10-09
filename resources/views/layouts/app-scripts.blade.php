{{-- Behaviour of the layouts/app shell. Plain DOM: this layout ships no jQuery. --}}
<script>
(function () {
    var root = document.documentElement;
    var desktop = window.matchMedia('(min-width: 64rem)');

    /* ------------------------------------------------------------ sidebar */
    // One button, two jobs: on a desktop it collapses the sidebar to a rail
    // (and remembers that); on a phone it slides the sidebar in as a drawer.
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('appSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');

    function setRail(collapsed) {
        if (collapsed) { root.dataset.sidebar = 'collapsed'; } else { delete root.dataset.sidebar; }
        try { localStorage.setItem('hris.sidebar', collapsed ? 'collapsed' : 'open'); } catch (e) {}
    }

    function setDrawer(open) {
        if (open) { root.dataset.drawer = 'open'; } else { delete root.dataset.drawer; }
    }

    toggle.addEventListener('click', function () {
        if (desktop.matches) {
            setRail(root.dataset.sidebar !== 'collapsed');
        } else {
            setDrawer(root.dataset.drawer !== 'open');
        }
    });

    backdrop.addEventListener('click', function () { setDrawer(false); });

    sidebar.addEventListener('click', function (event) {
        if (event.target.closest('a')) { setDrawer(false); }
    });

    // A submenu has nowhere to unfold in a strip of icons, so its parent
    // opens the sidebar instead of toggling itself.
    sidebar.querySelectorAll('[data-sidebar-tree] > summary').forEach(function (summary) {
        summary.addEventListener('click', function (event) {
            if (desktop.matches && root.dataset.sidebar === 'collapsed') {
                event.preventDefault();
                setRail(false);
                summary.parentElement.open = true;
            }
        });
    });

    /* --------------------------------------------------------------- theme */
    // Light unless dark has been chosen here; the choice is kept per browser.
    var themeToggle = document.getElementById('themeToggle');

    function setTheme(dark) {
        if (dark) { root.dataset.theme = 'dark'; } else { delete root.dataset.theme; }
        themeToggle.setAttribute('aria-label', dark ? 'Switch to light theme' : 'Switch to dark theme');
        try { localStorage.setItem('hris.theme', dark ? 'dark' : 'light'); } catch (e) {}
    }

    themeToggle.addEventListener('click', function () { setTheme(root.dataset.theme !== 'dark'); });
    themeToggle.setAttribute('aria-label', root.dataset.theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');

    /* -------------------------------------------------------------- menus */
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-menu-button]'));

    function panelOf(button) { return document.getElementById(button.getAttribute('aria-controls')); }

    function closeMenus(except) {
        buttons.forEach(function (button) {
            if (button === except) return;
            panelOf(button).hidden = true;
            button.setAttribute('aria-expanded', 'false');
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var panel = panelOf(button);
            var open = panel.hidden;
            closeMenus(button);
            panel.hidden = !open;
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('[data-menu-button], [data-menu]')) { closeMenus(); }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        closeMenus();
        setDrawer(false);
    });

    /* ------------------------------------------------ notifications paging */
    var feed = document.querySelector('[data-load-more]');

    if (feed) {
        var offset = 10;          // the first ten are already in the page
        var loading = false;
        var finished = false;

        feed.addEventListener('scroll', function () {
            if (loading || finished) return;
            if (feed.scrollTop + feed.clientHeight < feed.scrollHeight - 5) return;

            loading = true;
            fetch(feed.dataset.loadMore + '?offset=' + offset, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.stop === true || !data.html) { finished = true; return; }
                    feed.insertAdjacentHTML('beforeend', data.html);
                    offset = data.nextOffset;
                })
                .catch(function () {})
                .finally(function () { loading = false; });
        });
    }

    /* --------------------------------------------------- interview ratings */
    var ratingLink = document.getElementById('interviewRatingNavLink');
    var ratingBadge = document.getElementById('interviewRatingBadge');

    if (ratingLink && ratingBadge) {
        var checking = false;

        var refreshRatings = function () {
            if (checking) return;
            checking = true;

            fetch(ratingLink.dataset.interviewStatus, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    var count = parseInt(data.count || 0, 10);
                    ratingBadge.textContent = count;
                    ratingLink.hidden = count <= 0;
                })
                .catch(function () {})
                .finally(function () { checking = false; });
        };

        refreshRatings();
        window.addEventListener('focus', refreshRatings);
        window.addEventListener('pageshow', refreshRatings);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) { refreshRatings(); }
        });
    }

    /* ------------------------------------------------------------- dialogs */
    // data-dialog-open="id" opens a dialog, data-dialog-close closes the one
    // it sits in. data-dialog-static refuses Escape; data-dialog-autoshow
    // opens with the page.
    document.querySelectorAll('[data-dialog-open]').forEach(function (opener) {
        opener.addEventListener('click', function () {
            document.getElementById(opener.dataset.dialogOpen).showModal();
        });
    });

    document.querySelectorAll('dialog').forEach(function (dialog) {
        dialog.querySelectorAll('[data-dialog-close]').forEach(function (closer) {
            closer.addEventListener('click', function () { dialog.close(); });
        });

        if (dialog.hasAttribute('data-dialog-static')) {
            dialog.addEventListener('cancel', function (event) { event.preventDefault(); });
        }

        if (dialog.hasAttribute('data-dialog-autoshow')) {
            dialog.showModal();
        }
    });

    /* --------------------------------------------------------- confirmation */
    // One dialog (layouts/app), two ways in:
    //   - a form marked data-confirm is held until the question is answered,
    //     then sent as it was;
    //   - script calls hrisConfirm({ title, detail, button, danger }) and gets
    //     a promise that resolves true or false.
    (function () {
        var dialog = document.getElementById('confirmDialog');
        var yes = dialog.querySelector('[data-confirm-yes]');
        var answer = null;       // called once with true or false

        function ask(options) {
            dialog.querySelector('#confirmDialogTitle').textContent = options.title;
            var detail = dialog.querySelector('[data-confirm-detail]');
            detail.textContent = options.detail || '';
            detail.hidden = !options.detail;
            yes.textContent = options.button || 'Yes, continue';
            if (options.danger) { yes.dataset.danger = ''; } else { delete yes.dataset.danger; }

            return new Promise(function (resolve) {
                answer = resolve;
                dialog.showModal();
            });
        }

        function settle(value) {
            var resolve = answer;
            answer = null;
            if (resolve) { resolve(value); }
        }

        yes.addEventListener('click', function () { settle(true); dialog.close(); });
        dialog.addEventListener('close', function () { settle(false); });

        window.hrisConfirm = ask;

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;

            event.preventDefault();
            var submitter = event.submitter;

            ask({
                title: form.dataset.confirm,
                detail: form.dataset.confirmDetail,
                button: form.dataset.confirmButton,
                danger: form.hasAttribute('data-confirm-danger')
            }).then(function (go) {
                if (!go) return;

                // The button that was pressed may carry its own name and value;
                // put them back, since submit() does not know which it was.
                if (submitter && submitter.name) {
                    var carried = document.createElement('input');
                    carried.type = 'hidden';
                    carried.name = submitter.name;
                    carried.value = submitter.value;
                    form.appendChild(carried);
                }
                HTMLFormElement.prototype.submit.call(form);
            });
        }, true);
    })();

    /* --------------------------------------------------------------- lists */
    // Search, filters, sorting and paging for a table whose rows are all in
    // the page. Everything is found by data attribute inside [data-list]:
    //
    //   [data-row]                 a row; data-search is what the search box
    //                              matches, data-<key> what a filter or sort reads
    //   [data-list-search]         text box
    //   [data-list-filter="key"]   select; '' means no filter
    //   [data-list-sort="key"]     column heading button (with an <i> for the arrow)
    //   [data-list-size]           rows per page; 0 means all
    //   [data-list-count] [data-list-empty] [data-list-pager]
    //
    // data-list-sort-by on the container names the starting sort. A page that
    // adds, removes or changes rows calls container.hrisList.render().
    // With scripting off, the whole table simply shows.
    document.querySelectorAll('[data-list]').forEach(function (list) {
        var body = list.querySelector('tbody');
        var search = list.querySelector('[data-list-search]');
        var filters = Array.prototype.slice.call(list.querySelectorAll('[data-list-filter]'));
        var size = list.querySelector('[data-list-size]');
        var count = list.querySelector('[data-list-count]');
        var empty = list.querySelector('[data-list-empty]');
        var pager = list.querySelector('[data-list-pager]');
        var sorters = Array.prototype.slice.call(list.querySelectorAll('[data-list-sort]'));

        var state = { sort: list.dataset.listSortBy || '', descending: false, page: 1 };

        function pageButton(label, page, options) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'grid h-9 min-w-9 cursor-pointer place-items-center rounded-lg px-2 tabular-nums transition-colors hover:bg-paper focus-visible:outline-2 focus-visible:outline-sun-500 disabled:cursor-default disabled:opacity-35 disabled:hover:bg-transparent aria-[current=page]:bg-forest-100 aria-[current=page]:font-medium aria-[current=page]:text-forest-800';
            button.textContent = label;
            if (options.name) { button.setAttribute('aria-label', options.name); }
            if (options.current) { button.setAttribute('aria-current', 'page'); }
            button.disabled = !!options.disabled;
            button.addEventListener('click', function () { state.page = page; render(); });
            return button;
        }

        function render() {
            var rows = Array.prototype.slice.call(body.querySelectorAll('[data-row]'));
            var query = search ? search.value.trim().toLowerCase() : '';

            var matches = rows.filter(function (row) {
                if (query && (row.dataset.search || '').indexOf(query) === -1) return false;
                return filters.every(function (filter) {
                    return !filter.value || row.dataset[filter.dataset.listFilter] === filter.value;
                });
            });

            // Rows with nothing in the sorted column go last either way round.
            if (state.sort) {
                matches.sort(function (a, b) {
                    var x = a.dataset[state.sort], y = b.dataset[state.sort];
                    if (!x || !y) { return x ? -1 : (y ? 1 : 0); }
                    return x.localeCompare(y, undefined, { numeric: true }) * (state.descending ? -1 : 1);
                });
            }

            var perPage = (size && parseInt(size.value, 10)) || matches.length || 1;
            var pages = Math.max(1, Math.ceil(matches.length / perPage));
            state.page = Math.min(Math.max(1, state.page), pages);
            var first = (state.page - 1) * perPage;
            var shown = matches.slice(first, first + perPage);

            rows.forEach(function (row) { row.hidden = true; });
            shown.forEach(function (row) { row.hidden = false; body.appendChild(row); });

            if (empty) { empty.hidden = matches.length > 0; }
            if (count) {
                count.textContent = matches.length
                    ? (first + 1) + '–' + (first + shown.length) + ' of ' + matches.length
                    : '';
            }

            sorters.forEach(function (sorter) {
                var active = sorter.dataset.listSort === state.sort;
                sorter.querySelector('i').className = 'fas text-[10px] ' + (active ? (state.descending ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort opacity-50');
                sorter.closest('th').setAttribute('aria-sort', active ? (state.descending ? 'descending' : 'ascending') : 'none');
            });

            // Pager: previous, the page numbers around the current one, next.
            if (!pager) return;
            pager.textContent = '';
            if (pages > 1) {
                pager.appendChild(pageButton('‹', state.page - 1, { name: 'Previous page', disabled: state.page === 1 }));
                for (var page = 1; page <= pages; page++) {
                    if (page === 1 || page === pages || Math.abs(page - state.page) <= 1) {
                        pager.appendChild(pageButton(String(page), page, { current: page === state.page }));
                    } else if (Math.abs(page - state.page) === 2) {
                        var gap = document.createElement('span');
                        gap.className = 'px-1 text-ink/35';
                        gap.textContent = '…';
                        pager.appendChild(gap);
                    }
                }
                pager.appendChild(pageButton('›', state.page + 1, { name: 'Next page', disabled: state.page === pages }));
            }
        }

        [search, size].concat(filters).forEach(function (control) {
            if (control) { control.addEventListener('input', function () { state.page = 1; render(); }); }
        });

        sorters.forEach(function (sorter) {
            sorter.addEventListener('click', function () {
                state.descending = state.sort === sorter.dataset.listSort ? !state.descending : false;
                state.sort = sorter.dataset.listSort;
                state.page = 1;
                render();
            });
        });

        list.hrisList = { render: render };
        render();
    });

    /* ---------------------------------------------------------- checklists */
    // partials/checklist: search, a running count, the chosen names spelled
    // out, and "at least one" where the list is marked required.
    document.querySelectorAll('[data-checklist]').forEach(function (checklist) {
        var rows = Array.prototype.slice.call(checklist.querySelectorAll('[data-checklist-row]'));
        var search = checklist.querySelector('[data-checklist-search]');
        var count = checklist.querySelector('[data-checklist-count]');
        var summary = checklist.querySelector('[data-checklist-summary]');
        var error = checklist.querySelector('[data-checklist-error]');

        function chosen() {
            return rows.filter(function (row) { return row.querySelector('input').checked; });
        }

        function tally() {
            var picked = chosen();
            count.textContent = picked.length ? picked.length + ' chosen' : 'None chosen';

            var names = picked.map(function (row) { return row.querySelector('span').textContent.trim(); });
            summary.textContent = names.slice(0, 6).join('; ') + (names.length > 6 ? '; and ' + (names.length - 6) + ' more' : '');
            summary.hidden = !names.length;

            if (picked.length && error) { error.hidden = true; }
        }

        search.addEventListener('input', function () {
            var query = search.value.trim().toLowerCase();
            var any = false;
            rows.forEach(function (row) {
                row.hidden = !!query && row.textContent.toLowerCase().indexOf(query) === -1;
                if (!row.hidden) { any = true; }
            });
            checklist.querySelector('[data-checklist-none]').hidden = any;
        });

        // Enter in the search box must not send the form it sits in.
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); }
        });

        checklist.addEventListener('change', tally);

        // A set of tick boxes cannot say "required" for itself.
        if (checklist.hasAttribute('data-checklist-required') && rows.length) {
            var form = rows[0].querySelector('input').form;
            if (form) {
                form.addEventListener('submit', function (event) {
                    if (chosen().length) return;
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    error.hidden = false;
                    search.focus();
                }, true);
                form.addEventListener('reset', function () { setTimeout(tally, 0); });
            }
        }

        tally();
    });

    /* --------------------------------------------------------- tile charts */
    // Hover readout for the columns in home/stat-tile. One floating label,
    // filled with textContent; the same numbers are in each tile's table.
    (function () {
        var slots = document.querySelectorAll('[data-spark]');
        if (!slots.length) return;

        var tip = document.createElement('div');
        tip.className = 'pointer-events-none fixed z-50 -translate-x-1/2 -translate-y-full rounded-lg bg-ink px-2.5 py-1.5 text-center text-xs leading-tight whitespace-nowrap text-paper shadow-lg';
        tip.hidden = true;

        var value = document.createElement('strong');
        value.className = 'block font-semibold';
        var label = document.createElement('span');
        label.className = 'text-paper/65';
        tip.append(value, label);
        document.body.appendChild(tip);

        slots.forEach(function (slot) {
            slot.addEventListener('pointerenter', function () {
                var box = slot.getBoundingClientRect();
                value.textContent = slot.dataset.spark;
                label.textContent = slot.dataset.sparkLabel;
                tip.style.left = (box.left + box.width / 2) + 'px';
                tip.style.top = (box.top - 6) + 'px';
                tip.hidden = false;
            });
            slot.addEventListener('pointerleave', function () { tip.hidden = true; });
        });
    })();

    /* ------------------------------------------------------ privacy notice */
    // layouts/app-privacy: the contents list jumps to a section and marks
    // the one being read, and the bar under the heading fills with the scroll.
    document.querySelectorAll('[data-privacy]').forEach(function (notice) {
        var scroller = notice.querySelector('[data-privacy-scroll]');
        var bar = notice.querySelector('[data-privacy-progress]');
        var sections = Array.prototype.slice.call(notice.querySelectorAll('[data-privacy-section]'));
        var jumps = Array.prototype.slice.call(notice.querySelectorAll('[data-privacy-jump]'));

        // A section picked from the contents stays marked until the reader
        // scrolls by hand. The last few are short, so jumping to one can land
        // on the end of the notice, where position alone would name the last.
        var picked = null;

        function follow() {
            var room = scroller.scrollHeight - scroller.clientHeight;
            bar.style.width = (room > 0 ? Math.min(100, scroller.scrollTop / room * 100) : 100) + '%';

            // Otherwise the section being read: the last one whose heading
            // has passed the top of the view (the last of all at the end).
            var reading = sections[0];
            sections.forEach(function (section) {
                if (section.offsetTop - scroller.offsetTop <= scroller.scrollTop + 40) { reading = section; }
            });
            if (room > 0 && scroller.scrollTop >= room - 2) { reading = sections[sections.length - 1]; }
            if (picked) { reading = picked; }

            jumps.forEach(function (jump) {
                jump.setAttribute('aria-current', reading && jump.dataset.privacyJump === reading.id ? 'true' : 'false');
            });
        }

        jumps.forEach(function (jump) {
            jump.addEventListener('click', function () {
                var section = document.getElementById(jump.dataset.privacyJump);
                picked = section;
                scroller.scrollTo({ top: section.offsetTop - scroller.offsetTop - 12 });
                follow();
            });
        });

        ['wheel', 'touchmove', 'keydown'].forEach(function (kind) {
            scroller.addEventListener(kind, function () { picked = null; }, { passive: true });
        });

        scroller.addEventListener('scroll', follow, { passive: true });
        follow();
    });

    /* -------------------------------------------------------------- toasts */
    var toasts = document.getElementById('toasts');

    function armToast(toast) {
        var timer = setTimeout(function () { toast.remove(); }, 6000);

        toast.querySelector('[data-toast-close]').addEventListener('click', function () {
            clearTimeout(timer);
            toast.remove();
        });
    }

    toasts.querySelectorAll('[data-toast]').forEach(armToast);

    // For pages that change something without a reload. Built to match the
    // flash messages layouts/app renders; the message goes in as text.
    window.hrisToast = function (kind, message) {
        var error = kind === 'error';

        var toast = document.createElement('div');
        toast.dataset.toast = '';
        toast.setAttribute('role', error ? 'alert' : 'status');
        toast.className = 'pointer-events-auto flex w-full items-start gap-3 rounded-xl px-4 py-3 shadow-lg shadow-forest-950/15 sm:w-96 '
            + (error ? 'bg-red-700 text-white' : 'bg-forest-900 text-cream dark:ring-1 dark:ring-cream/15');

        var icon = document.createElement('i');
        icon.className = 'fas mt-0.5 ' + (error ? 'fa-circle-exclamation' : 'fa-circle-check');

        var text = document.createElement('p');
        text.className = 'min-w-0 flex-1 leading-snug';
        text.textContent = message;

        var close = document.createElement('button');
        close.type = 'button';
        close.dataset.toastClose = '';
        close.setAttribute('aria-label', 'Dismiss');
        close.className = '-mr-1 cursor-pointer opacity-70 hover:opacity-100';
        var cross = document.createElement('i');
        cross.className = 'fas fa-xmark';
        close.appendChild(cross);

        toast.append(icon, text, close);
        toasts.appendChild(toast);
        armToast(toast);
    };

    /* Carried over from the old shell (script/masterScript): the right-click
       menu is disabled on signed-in pages. */
    document.addEventListener('contextmenu', function (event) { event.preventDefault(); });
})();
</script>
