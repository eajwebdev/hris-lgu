@extends('layouts.app')

@php
    // One view for two routes: /user lists, /user/edit/{id} lists with the
    // editor already open on that account.
    $uEdit = $uEdit ?? null;
    $ownId = auth()->guard('web')->id();

    $roles = ['Administrator', 'HR Administrator', 'Payroll Administrator'];

    // What an account may open: place in users.access (a comma-joined row of
    // 0s and 1s) => label. Place 5 is not used.
    $permissions = [
        0 => 'Employees',
        1 => 'Offices',
        2 => 'Payslip',
        3 => 'Events',
        4 => 'DTR',
        7 => 'Leave',
        6 => 'Settings',
        8 => 'Kiosk',
    ];
    $granted = fn ($access) => collect($permissions)
        ->filter(fn ($label, $place) => (explode(',', (string) $access)[$place] ?? '0') == '1');

    // A save that failed validation comes back here with what was typed; the
    // editor reopens on it rather than making the administrator start over.
    $retry = $errors->any() && old('_token') ? [
        'uid' => old('uid'), 'lname' => old('lname'), 'fname' => old('fname'), 'mname' => old('mname'),
        'gender' => old('gender'), 'role' => old('role'), 'username' => old('username'),
        'access' => array_keys((array) old('access', [])),
    ] : null;

    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $input = $field . ' mt-1 block h-10 w-full px-3';
    $label = 'block text-xs font-medium text-ink/60';
    $rowAction = 'grid size-9 place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
@endphp

@if($uEdit)
    @section('breadcrumb', 'Edit')
@endif

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Users</h1>
            <p class="mt-1 max-w-2xl text-cream/70">Accounts for administrators and HR staff. Employees sign in with their own employee record, not from here.</p>
        </div>

        <button type="button" data-user-new
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-cream bg-cream px-4 font-medium text-forest-900 transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            <i class="fas fa-user-plus"></i> Add user
        </button>
    </div>
@endsection

@section('body')
<section class="rounded-2xl border border-line bg-surface" id="userList" data-list data-list-sort-by="name">
    <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
        <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
            <span class="sr-only">Search users</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" data-list-search placeholder="Search name, username" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
        </label>

        <label>
            <span class="sr-only">Role</span>
            <select data-list-filter="role" class="{{ $field }} h-10 pr-8 pl-3">
                <option value="">All roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
        </label>

        <p class="ml-auto text-ink/55" data-list-count aria-live="polite"></p>
    </div>

    {{-- relative: keeps the visually hidden labels in the cells inside this
         scroller instead of widening the page on a phone. --}}
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    @foreach(['name' => 'Name', 'username' => 'Username', 'role' => 'Role'] as $key => $heading)
                        <th scope="col" class="px-4 py-3 font-medium first:pl-5 {{ $key === 'username' ? 'max-md:hidden' : '' }} {{ $key === 'role' ? 'max-sm:hidden' : '' }}">
                            <button type="button" data-list-sort="{{ $key }}" class="-mx-1.5 inline-flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 transition-colors hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                                {{ $heading }} <i class="fas fa-sort text-[10px] opacity-50"></i>
                            </button>
                        </th>
                    @endforeach
                    <th scope="col" class="px-4 py-3 font-medium max-lg:hidden">Access</th>
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach($users as $user)
                    @php
                        $name = trim($user->lname . ', ' . $user->fname . ' ' . $user->mname);
                        $can = $granted($user->access);
                        $isOwn = $user->uid == $ownId;
                    @endphp
                    <tr id="tr-{{ $user->uid }}" data-row
                        data-search="{{ strtolower($name . ' ' . $user->username . ' ' . $user->role) }}"
                        data-name="{{ strtolower($name) }}" data-username="{{ strtolower($user->username) }}" data-role="{{ $user->role }}"
                        data-user-id="{{ $user->uid }}" data-user-lname="{{ $user->lname }}" data-user-fname="{{ $user->fname }}" data-user-mname="{{ $user->mname }}"
                        data-user-gender="{{ $user->gender }}" data-user-username="{{ $user->username }}" data-user-access="{{ $can->keys()->implode(',') }}"
                        class="transition-colors hover:bg-paper/70">
                        <td class="px-4 py-3 pl-5">
                            <p class="font-semibold">{{ $name }}</p>
                            @if($isOwn)
                                <p class="mt-0.5 text-xs text-ink/55">This is you</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-md:hidden">{{ $user->username }}</td>
                        <td class="px-4 py-3 whitespace-nowrap max-sm:hidden">{{ $user->role ?: 'No role' }}</td>
                        <td class="px-4 py-3 max-lg:hidden">
                            @if($can->isNotEmpty())
                                <div class="flex max-w-xs flex-wrap gap-1">
                                    @foreach($can as $permission)
                                        <span class="inline-block rounded-md bg-forest-100 px-2 py-0.5 text-xs font-medium text-forest-800">{{ $permission }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-ink/45">None ticked</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 pr-5">
                            <div class="flex justify-end gap-1">
                                <button type="button" title="Edit user" data-user-edit class="{{ $rowAction }} cursor-pointer hover:bg-forest-100 hover:text-forest-700">
                                    <i class="fas fa-pen"></i><span class="sr-only">Edit {{ $name }}</span>
                                </button>
                                {{-- Deleting the account in use would sign its owner out for good. --}}
                                @if($isOwn)
                                    <span title="You cannot delete the account you are signed in with" aria-disabled="true" class="{{ $rowAction }} cursor-not-allowed text-ink/25"><i class="fas fa-trash"></i></span>
                                @else
                                    <button type="button" title="Delete user" data-user-delete class="{{ $rowAction }} cursor-pointer hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300">
                                        <i class="fas fa-trash"></i><span class="sr-only">Delete {{ $name }}</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No user matches that.</p>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
        <label class="flex items-center gap-2 text-ink/55">
            Rows
            <select data-list-size class="{{ $field }} h-9 pr-7 pl-3">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="0">All</option>
            </select>
        </label>
        <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
    </div>
</section>

{{-- Add and edit share this form; the script points it at uCreate or uUpdate
     and fills it from the row (or from what was typed, after a failed save). --}}
<dialog id="userDialog" aria-labelledby="userDialogTitle"
        class="m-auto max-h-[calc(100dvh-2rem)] w-[min(36rem,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60"
        data-create-url="{{ route('uCreate') }}" data-update-url="{{ route('uUpdate') }}"
        @if($uEdit) data-open-on="{{ $uEdit->id }}" @endif
        @if($retry) data-retry='@json($retry)' @endif>
    <form method="POST" action="{{ route('uCreate') }}" class="p-6">
        @csrf
        <input type="hidden" name="uid">

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="userDialogTitle">Add user</h2>
            <button type="button" data-dialog-close aria-label="Close" class="-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        @if($retry)
            <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-red-800 dark:bg-red-500/10 dark:text-red-300" role="alert" data-user-errors>
                <p class="font-medium">That could not be saved:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 grid gap-4 sm:grid-cols-6">
            <div class="sm:col-span-2">
                <label for="userLname" class="{{ $label }}">Last name</label>
                <input type="text" id="userLname" name="lname" required autocomplete="off" data-uppercase class="{{ $input }}">
            </div>
            <div class="sm:col-span-2">
                <label for="userFname" class="{{ $label }}">First name</label>
                <input type="text" id="userFname" name="fname" required autocomplete="off" class="{{ $input }}">
            </div>
            <div class="sm:col-span-2">
                <label for="userMname" class="{{ $label }}">Middle name</label>
                <input type="text" id="userMname" name="mname" required autocomplete="off" data-uppercase class="{{ $input }}">
            </div>

            <div class="sm:col-span-2">
                <label for="userGender" class="{{ $label }}">Gender</label>
                <select id="userGender" name="gender" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    <option value="" disabled selected>Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="sm:col-span-4">
                <label for="userRole" class="{{ $label }}">Role</label>
                <select id="userRole" name="role" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    <option value="" disabled selected>Select</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3">
                <label for="userUsername" class="{{ $label }}">Username</label>
                <input type="text" id="userUsername" name="username" required autocomplete="off" placeholder="name@mabinay.gov.ph" class="{{ $input }}">
            </div>

            {{-- Optional when editing: blank keeps the current password, so
                 fixing a typo in a surname does not mean reissuing credentials.
                 It can be shown because it is typed FOR somebody else, who
                 would only find a mistake by failing to sign in. --}}
            <div class="sm:col-span-3">
                <label for="userPassword" class="{{ $label }}">Password</label>
                <div class="relative mt-1">
                    <input type="password" id="userPassword" name="password" autocomplete="new-password" class="{{ $field }} block h-10 w-full pr-16 pl-3">
                    <button type="button" data-password-toggle aria-controls="userPassword"
                            class="absolute inset-y-0 right-0 cursor-pointer rounded-r-xl px-3 text-xs font-medium text-forest-700 hover:text-forest-950 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-sun-500">Show</button>
                </div>
                <p class="mt-1 text-xs text-ink/55" data-password-hint></p>
            </div>
        </div>

        <fieldset class="mt-5">
            <legend class="{{ $label }}">Access permissions</legend>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach($permissions as $place => $permission)
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line px-3 py-2 transition-colors hover:border-forest-600/40 has-checked:border-forest-600/50 has-checked:bg-forest-100">
                        <input type="checkbox" name="access[{{ $place }}]" value="1" class="size-4 shrink-0 accent-forest-600">
                        {{ $permission }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" name="btn-submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var list = document.getElementById('userList');
    var dialog = document.getElementById('userDialog');
    var form = dialog.querySelector('form');
    var password = form.elements['password'];
    var toggle = dialog.querySelector('[data-password-toggle]');

    // values: { uid, lname, fname, mname, gender, role, username, access: [places] },
    // or null for a blank form. A uid means an existing account.
    function openEditor(values) {
        values = values || {};
        var editing = !!values.uid;

        form.reset();
        form.action = editing ? dialog.dataset.updateUrl : dialog.dataset.createUrl;
        dialog.querySelector('#userDialogTitle').textContent = editing ? 'Edit user' : 'Add user';

        ['uid', 'lname', 'fname', 'mname', 'username'].forEach(function (name) {
            form.elements[name].value = values[name] || '';
        });
        form.elements['gender'].value = values.gender || '';
        form.elements['role'].value = values.role || '';

        var places = (values.access || []).map(String);
        form.querySelectorAll('input[name^="access["]').forEach(function (box) {
            box.checked = places.indexOf(box.name.replace(/\D/g, '')) !== -1;
        });

        password.type = 'password';
        toggle.textContent = 'Show';
        password.required = !editing;
        dialog.querySelector('[data-password-hint]').textContent = editing
            ? 'Leave blank to keep the current password.'
            : 'At least 8 characters, with letters and numbers.';

        dialog.showModal();
        form.elements['lname'].focus();
    }

    function fromRow(row) {
        var data = row.dataset;
        return {
            uid: data.userId, lname: data.userLname, fname: data.userFname, mname: data.userMname,
            gender: data.userGender, role: data.role, username: data.userUsername,
            access: data.userAccess ? data.userAccess.split(',') : []
        };
    }

    document.querySelector('[data-user-new]').addEventListener('click', function () {
        var errors = dialog.querySelector('[data-user-errors]');
        if (errors) { errors.remove(); }
        openEditor(null);
    });

    list.addEventListener('click', function (event) {
        var edit = event.target.closest('[data-user-edit]');
        if (!edit) return;

        var errors = dialog.querySelector('[data-user-errors]');
        if (errors) { errors.remove(); }
        openEditor(fromRow(edit.closest('[data-row]')));
    });

    // Arriving after a failed save, or on /user/edit/{id}.
    if (dialog.dataset.retry) {
        openEditor(JSON.parse(dialog.dataset.retry));
    } else if (dialog.dataset.openOn) {
        var requested = document.getElementById('tr-' + dialog.dataset.openOn);
        if (requested) { openEditor(fromRow(requested)); }
    }

    toggle.addEventListener('click', function () {
        var show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Hide' : 'Show';
        password.focus();
    });

    // Last and middle names are kept in capitals, as before.
    form.querySelectorAll('[data-uppercase]').forEach(function (input) {
        input.addEventListener('input', function () {
            var caret = input.selectionStart;
            input.value = input.value.toUpperCase();
            input.setSelectionRange(caret, caret);
        });
    });

    /* -------------------------------------------------------------- delete */
    list.addEventListener('click', function (event) {
        var button = event.target.closest('[data-user-delete]');
        if (!button) return;

        var row = button.closest('[data-row]');

        hrisConfirm({
            title: 'Delete this user?',
            detail: row.querySelector('p').textContent + ' will no longer be able to sign in. You won\'t be able to revert this.',
            button: 'Yes, delete it',
            danger: true
        }).then(function (go) {
            if (!go) return;

            fetch("{{ route('uDelete') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new URLSearchParams({ id: row.dataset.userId })
            })
                .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
                .then(function (data) {
                    if (data.status !== 200) { return Promise.reject(); }
                    row.remove();
                    list.hrisList.render();
                    hrisToast('success', 'User deleted.');
                })
                .catch(function () { hrisToast('error', 'The user could not be deleted. Please try again.'); });
        });
    });
})();
</script>
@endpush
