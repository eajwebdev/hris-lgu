{{--
    Dropdowns for layouts/app.

    Every <select> on a page is given a button and a list drawn in the app's
    own style, in place of the browser's popup. The <select> itself stays in
    the page, hidden: it still holds the value, is what the form posts, and
    still raises `input` and `change`, so nothing that reads or listens to it
    has to know. Changes made to it from script (value, selectedIndex,
    disabled, a form reset) show on the button too.

    A list of more than eight options gets a search box. A select marked
    data-native is left alone, as is a multiple one.
--}}
<script>
(function () {
    var valueProp = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    var indexProp = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
    var current = null;      // the open dropdown: { select, button, panel, ... }

    function node(tag, className, text) {
        var el = document.createElement(tag);
        if (className) { el.className = className; }
        if (text !== undefined) { el.textContent = text; }
        return el;
    }

    function labelOf(select) {
        var label = (select.id && document.querySelector('label[for="' + select.id + '"]')) || select.closest('label');
        if (!label) { return select.getAttribute('aria-label') || ''; }
        var named = label.querySelector('.sr-only') || label;
        // The label may wrap the select: take its own words, not the options'.
        return Array.prototype.map.call(named.childNodes, function (child) {
            return child.nodeType === 3 ? child.textContent : (child.tagName === 'SELECT' || child.tagName === 'BUTTON' ? '' : child.textContent);
        }).join(' ').replace(/\s+/g, ' ').trim();
    }

    /* ------------------------------------------------------------ the button */
    function enhance(select) {
        if (select.multiple || select.hasAttribute('data-native') || select.hrisSelect) return;

        // The button takes over the select's own classes, so it sits, sizes
        // and takes focus exactly as the field did.
        var sized = /(^|\s|:)(w-|flex-1|grow)/.test(select.className);
        var button = node('button', select.className + ' relative cursor-pointer text-left disabled:cursor-not-allowed disabled:opacity-60');
        button.type = 'button';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');

        var name = labelOf(select);
        if (name) { button.appendChild(node('span', 'sr-only', name + ': ')); }

        // A field with no width of its own is as wide as its longest option,
        // as a select is; the invisible copy of that option holds it open.
        var stack = node('span', 'grid');
        var shown = node('span', 'truncate [grid-area:1/1]');
        stack.appendChild(shown);
        if (!sized) {
            var longest = Array.prototype.reduce.call(select.options, function (a, option) {
                return option.text.trim().length > a.length ? option.text.trim() : a;
            }, '');
            stack.appendChild(node('span', 'invisible h-0 overflow-hidden whitespace-nowrap [grid-area:1/1]', longest));
        }
        button.appendChild(stack);

        var chevron = node('span', 'pointer-events-none absolute inset-y-0 right-3 grid place-items-center text-ink/45');
        chevron.appendChild(node('i', 'fas fa-angle-down text-xs'));
        button.appendChild(chevron);

        select.className = 'sr-only';
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');
        select.insertAdjacentElement('afterend', button);

        var state = select.hrisSelect = { select: select, button: button, shown: shown };

        state.refresh = function () {
            var option = select.options[indexProp.get.call(select)];
            shown.textContent = option ? option.text.replace(/\s+/g, ' ').trim() : '';
            shown.classList.toggle('text-ink/45', !option || option.disabled);
            button.disabled = select.disabled;
        };

        // Script that sets the value directly (a form being filled in) raises
        // no event, so the two properties report to the button themselves.
        Object.defineProperty(select, 'value', {
            configurable: true,
            get: function () { return valueProp.get.call(select); },
            set: function (value) { valueProp.set.call(select, value); state.refresh(); }
        });
        Object.defineProperty(select, 'selectedIndex', {
            configurable: true,
            get: function () { return indexProp.get.call(select); },
            set: function (index) { indexProp.set.call(select, index); state.refresh(); }
        });

        select.addEventListener('change', state.refresh);
        select.addEventListener('input', state.refresh);
        select.addEventListener('focus', function () { button.focus(); });
        if (select.form) {
            select.form.addEventListener('reset', function () { setTimeout(state.refresh, 0); });
        }
        new MutationObserver(state.refresh).observe(select, { attributes: true, attributeFilter: ['disabled'], childList: true });

        var label = select.id && document.querySelector('label[for="' + select.id + '"]');
        if (label) {
            label.addEventListener('click', function (event) { event.preventDefault(); button.focus(); });
        }

        button.addEventListener('click', function () {
            if (current && current.select === select) { close(); } else { open(state); }
        });
        button.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); open(state); }
        });

        state.refresh();
    }

    /* -------------------------------------------------------------- the list */
    function open(state) {
        close();

        var select = state.select, button = state.button;
        // Inside a modal dialog the list has to live in it too, or it would
        // sit behind the dialog and take no clicks.
        var host = select.closest('dialog') || document.body;

        var panel = node('div', 'fixed z-50 flex flex-col overflow-hidden rounded-xl border border-line bg-surface text-sm text-ink shadow-xl shadow-forest-950/15');
        var options = Array.prototype.slice.call(select.options);
        var search = null;

        if (options.length > 8) {
            var box = node('div', 'border-b border-line p-2');
            search = node('input', 'block h-9 w-full rounded-lg border border-line bg-paper px-3 text-ink outline-none placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface');
            search.type = 'search';
            search.placeholder = 'Search';
            search.setAttribute('aria-label', 'Search options');
            search.autocomplete = 'off';
            box.appendChild(search);
            panel.appendChild(box);
        }

        var list = node('div', 'min-h-0 flex-1 overflow-y-auto overscroll-contain p-1.5 outline-none [scrollbar-width:thin]');
        list.setAttribute('role', 'listbox');
        list.tabIndex = -1;

        var selected = indexProp.get.call(select);
        var rows = options.map(function (option, index) {
            var row = node('div', 'flex cursor-pointer items-center justify-between gap-4 rounded-lg px-3 py-2 aria-disabled:cursor-default aria-disabled:text-ink/40 aria-selected:font-medium data-[active]:bg-forest-100 data-[active]:text-forest-800');
            row.setAttribute('role', 'option');
            row.setAttribute('aria-selected', index === selected ? 'true' : 'false');
            if (option.disabled) { row.setAttribute('aria-disabled', 'true'); }
            row.dataset.index = index;
            row.appendChild(node('span', 'min-w-0', option.text.replace(/\s+/g, ' ').trim()));
            if (index === selected) {
                var tick = node('span', 'shrink-0 text-forest-700');
                tick.appendChild(node('i', 'fas fa-check text-xs'));
                row.appendChild(tick);
            }
            list.appendChild(row);
            return row;
        });

        var none = node('p', 'px-3 py-6 text-center text-ink/50', 'No match');
        none.hidden = true;
        list.appendChild(none);
        panel.appendChild(list);
        host.appendChild(panel);

        var active = -1;
        function setActive(index, scroll) {
            if (rows[active]) { delete rows[active].dataset.active; }
            active = index;
            if (rows[active]) {
                rows[active].dataset.active = '';
                if (scroll) { rows[active].scrollIntoView({ block: 'nearest' }); }
            }
        }

        // The next row up or down that is showing and can be chosen.
        function step(from, by) {
            for (var i = from + by; i >= 0 && i < rows.length; i += by) {
                if (!rows[i].hidden && !options[i].disabled) { return i; }
            }
            return from;
        }

        function choose(index) {
            if (index < 0 || !options[index] || options[index].disabled) return;
            indexProp.set.call(select, index);
            state.refresh();
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
            button.focus();
        }

        // Below the field if there is room, above it if there is more there.
        function place() {
            var field = button.getBoundingClientRect();
            var below = window.innerHeight - field.bottom - 12;
            var above = field.top - 12;
            var up = below < 220 && above > below;

            panel.style.minWidth = field.width + 'px';
            panel.style.maxWidth = Math.min(448, window.innerWidth - 16) + 'px';
            panel.style.maxHeight = Math.max(140, Math.min(352, up ? above : below)) + 'px';
            panel.style.top = up ? '' : (field.bottom + 6) + 'px';
            panel.style.bottom = up ? (window.innerHeight - field.top + 6) + 'px' : '';
            panel.style.left = Math.max(8, Math.min(field.left, window.innerWidth - panel.offsetWidth - 8)) + 'px';
        }

        list.addEventListener('click', function (event) {
            var row = event.target.closest('[role="option"]');
            if (row) { choose(parseInt(row.dataset.index, 10)); }
        });
        list.addEventListener('pointermove', function (event) {
            var row = event.target.closest('[role="option"]');
            if (row && !row.hasAttribute('aria-disabled')) { setActive(parseInt(row.dataset.index, 10), false); }
        });

        if (search) {
            search.addEventListener('input', function () {
                var query = search.value.trim().toLowerCase();
                var first = -1;
                rows.forEach(function (row, index) {
                    row.hidden = !!query && row.textContent.toLowerCase().indexOf(query) === -1;
                    if (!row.hidden && first === -1 && !options[index].disabled) { first = index; }
                });
                none.hidden = rows.some(function (row) { return !row.hidden; });
                setActive(first, true);
            });
        }

        // Without a search box, typing jumps to the option that starts so.
        var typed = '', typing = null;

        panel.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') { event.preventDefault(); setActive(step(active, 1), true); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); setActive(step(active, -1), true); }
            else if (event.key === 'Home') { event.preventDefault(); setActive(step(-1, 1), true); }
            else if (event.key === 'End') { event.preventDefault(); setActive(step(rows.length, -1), true); }
            else if (event.key === 'Enter') { event.preventDefault(); choose(active); }
            else if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); close(); button.focus(); }
            else if (event.key === 'Tab') { close(); button.focus(); }
            else if (!search && event.key.length === 1) {
                typed += event.key.toLowerCase();
                clearTimeout(typing);
                typing = setTimeout(function () { typed = ''; }, 600);
                for (var i = 0; i < rows.length; i++) {
                    if (!options[i].disabled && rows[i].textContent.toLowerCase().indexOf(typed) === 0) { setActive(i, true); break; }
                }
            }
        });

        // Escape must close the list, not the dialog it is in.
        var dialog = select.closest('dialog');
        function holdDialog(event) { event.preventDefault(); }
        if (dialog) { dialog.addEventListener('cancel', holdDialog); }

        current = { select: select, button: button, panel: panel, dialog: dialog, holdDialog: holdDialog, place: place };
        button.setAttribute('aria-expanded', 'true');

        place();
        setActive(selected >= 0 && !options[selected].disabled ? selected : step(-1, 1), true);
        (search || list).focus();
    }

    function close() {
        if (!current) return;
        current.panel.remove();
        current.button.setAttribute('aria-expanded', 'false');
        if (current.dialog) { current.dialog.removeEventListener('cancel', current.holdDialog); }
        current = null;
    }

    document.addEventListener('pointerdown', function (event) {
        if (current && !current.panel.contains(event.target) && !current.button.contains(event.target)) { close(); }
    }, true);

    // The list is pinned to where the field was; if the page moves under it,
    // or the window changes, it is shut rather than left adrift.
    document.addEventListener('scroll', function (event) {
        if (current && !(event.target instanceof Node && current.panel.contains(event.target))) { close(); }
    }, true);
    window.addEventListener('resize', close);

    document.querySelectorAll('select').forEach(enhance);

    // For a page that adds a select after loading.
    window.hrisSelect = enhance;
})();
</script>
