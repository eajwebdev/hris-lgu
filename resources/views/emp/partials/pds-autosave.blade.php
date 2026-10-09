{{--
    Saving for a Personal Data Sheet page on layouts/app whose answers are
    saved one at a time, with no Save button.

    The page supplies <form id="pdsForm" data-employee="{id}"> and the
    indicator in the banner (emp/partials/pds-hero with $autosaves). Three
    kinds of answer are saved from it, each as it changes:

    A column. A field marked data-save (emp/partials/field marks its own)
    goes to the form's data-save-url as the employee's id, the column (the
    field's name) and the new value.

    A slot. Some columns hold several answers in one separated string (the
    yes/no questions, the references, the ID). A field marked
    data-save-slot="{position}" goes to the form's data-slot-url as the
    employee's id, the column (its name, less any _number ending), the
    position and the value.

    A list of rows. Others hold one string per column, matched by position
    (children, college degrees, skills). The whole list is sent whenever any
    of it changes:

        <div data-rows data-rows-url="…" data-rows-what="The children" data-rows-item="child">
            <ol data-rows-list>
                <li data-row> <input data-key="name_child"> … <button data-row-remove> </li>
            </ol>
            <template> one empty row </template>
            <button data-rows-add>
        </div>

    Each input's data-key is the name its values are posted under, as an
    array. Rows left empty are not sent, though one always is, since some of
    the endpoints refuse an empty list. An input with data-label is read to
    screen readers as "Child 2, date of birth"; [data-row-number] is given
    the row's number.

    A field with data-strip="," never keeps that character: it is what
    separates one answer from the next in the stored string.

    For anything saved another way the page calls pdsSave(url, params, what):
    it shows in the same indicator, raises the same message when it fails
    ("{what} could not be saved"), and resolves true or false. A column that
    has been saved raises `pds:saved` on its field.
--}}
@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var form = document.getElementById('pdsForm');
    var state = document.getElementById('pdsSaveState');

    // Nothing here is ever submitted; Enter in a field must not reload the page.
    form.addEventListener('submit', function (event) { event.preventDefault(); });

    var inFlight = 0;
    var failed = false;

    var states = {
        saving: ['fas fa-circle-notch fa-spin', 'Saving'],
        saved:  ['fas fa-circle-check', 'All changes saved'],
        error:  ['fas fa-triangle-exclamation', 'Last change not saved']
    };

    function show(name) {
        state.dataset.state = name;
        state.querySelector('i').className = states[name][0];
        state.querySelector('span').textContent = states[name][1];
    }

    window.pdsSave = function (url, params, what) {
        if (!inFlight) { failed = false; }
        inFlight++;
        show('saving');

        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: params instanceof URLSearchParams ? params : new URLSearchParams(params)
        })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function (data) { return data.success ? true : Promise.reject(); })
            .catch(function () {
                failed = true;
                hrisToast('error', what + ' could not be saved. Check the answer and change it again.');
                return false;
            })
            .finally(function () {
                inFlight--;
                if (!inFlight) { show(failed ? 'error' : 'saved'); }
            });
    };

    // What a field is called on the page, for the message when it fails.
    function nameOf(input) {
        if (input.dataset.saveName) { return input.dataset.saveName; }
        var label = input.id && form.querySelector('label[for="' + input.id + '"]');
        if (label) { return label.textContent.trim(); }
        if (input.getAttribute('aria-label')) { return input.getAttribute('aria-label'); }
        var group = input.closest('fieldset');
        return group ? group.querySelector('legend').textContent.trim() : input.name;
    }

    form.addEventListener('input', function (event) {
        var input = event.target;
        var strip = input.dataset && input.dataset.strip;
        if (strip && input.value.indexOf(strip) !== -1) { input.value = input.value.split(strip).join(''); }
    });

    /* ----------------------------------------------------- columns, slots */
    form.addEventListener('change', function (event) {
        var input = event.target;

        if (input.matches('[data-save]')) {
            pdsSave(form.dataset.saveUrl, { id: form.dataset.employee, column: input.name, value: input.value }, nameOf(input))
                .then(function (saved) {
                    if (saved) { input.dispatchEvent(new CustomEvent('pds:saved', { bubbles: true })); }
                });
        } else if (input.matches('[data-save-slot]')) {
            pdsSave(form.dataset.slotUrl, { empid: form.dataset.employee, column: input.name, index: input.dataset.saveSlot, value: input.value }, nameOf(input));
        }
    });

    /* ---------------------------------------------------------------- rows */
    Array.prototype.forEach.call(form.querySelectorAll('[data-rows]'), function (group) {
        var list = group.querySelector('[data-rows-list]');
        var template = group.querySelector('template');
        var item = group.dataset.rowsItem;

        function rows() { return Array.prototype.slice.call(list.querySelectorAll('[data-row]')); }
        function inputs(row) { return Array.prototype.slice.call(row.querySelectorAll('[data-key]')); }
        function filled(row) { return inputs(row).filter(function (input) { return input.value.trim(); }); }

        function number() {
            rows().forEach(function (row, index) {
                inputs(row).forEach(function (input) {
                    if (input.dataset.label) { input.setAttribute('aria-label', item.charAt(0).toUpperCase() + item.slice(1) + ' ' + (index + 1) + ', ' + input.dataset.label); }
                });
                var shown = row.querySelector('[data-row-number]');
                if (shown) { shown.textContent = index + 1; }
            });
        }

        function save() {
            var params = new URLSearchParams({ empid: form.dataset.employee });
            var kept = rows().filter(function (row) { return filled(row).length; });

            (kept.length ? kept : rows().slice(0, 1)).forEach(function (row) {
                inputs(row).forEach(function (input) { params.append(input.dataset.key + '[]', input.value.trim()); });
            });

            pdsSave(group.dataset.rowsUrl, params, group.dataset.rowsWhat);
        }

        group.querySelector('[data-rows-add]').addEventListener('click', function () {
            list.appendChild(template.content.cloneNode(true));
            number();
            inputs(rows().pop())[0].focus();
        });

        list.addEventListener('change', function (event) {
            if (event.target.matches('[data-key]')) { save(); }
        });

        list.addEventListener('click', function (event) {
            var button = event.target.closest('[data-row-remove]');
            if (!button) return;

            var row = button.closest('[data-row]');
            var had = filled(row);

            // The last row is emptied rather than taken away, so there is
            // always somewhere to type.
            function remove() {
                if (rows().length > 1) {
                    row.remove();
                } else {
                    inputs(row).forEach(function (input) { input.value = ''; });
                }
                number();
                if (had.length) { save(); }
            }

            if (!had.length) { remove(); return; }

            hrisConfirm({ title: 'Remove this ' + item + '?', detail: had[0].value.trim(), button: 'Yes, remove', danger: true })
                .then(function (go) { if (go) { remove(); } });
        });

        number();
    });
})();
</script>
@endpush
