@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="rec-page">

        <div class="rec-head">
            <div>
                <h1>Personnel Selection Board</h1>
                <div class="sub">
                    The signatory block printed beneath every Comparative Assessment Form.
                    Names are stored as typed, so a past assessment still prints correctly
                    after a member leaves.
                </div>
            </div>
            <div class="rec-actions">
                <a href="{{ route('positionDescriptionList') }}" class="rec-btn">
                    <i class="fas fa-arrow-left"></i> Position Descriptions
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check"></i> {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('psbMembersSave') }}" id="board-form">
            @csrf
            <div class="rec-card">
                <header>
                    <span class="n"><i class="fas fa-user-tie"></i></span>
                    <h2>Board membership</h2>
                    <span class="hint">the chairperson prints first</span>
                </header>
                <div class="body">
                    <table class="rec-rows" id="member-rows">
                        <thead>
                            <tr>
                                <th>Printed name</th>
                                <th style="width:120px;">Credentials</th>
                                <th style="width:160px;">Role</th>
                                <th style="width:220px;">Employee record</th>
                                <th style="width:80px;" class="text-center">Active</th>
                                <th style="width:44px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($members as $i => $m)
                                <tr>
                                    <td>
                                        <input type="hidden" name="members[{{ $i }}][id]" value="{{ $m->id }}">
                                        <input type="text" name="members[{{ $i }}][name]" value="{{ $m->name }}">
                                    </td>
                                    <td><input type="text" name="members[{{ $i }}][credentials]" value="{{ $m->credentials }}" placeholder="RN, JD"></td>
                                    <td>
                                        <select name="members[{{ $i }}][role]">
                                            @foreach(['Chairperson', 'Vice-Chairperson', 'Member'] as $role)
                                                <option value="{{ $role }}" @selected($m->role === $role)>{{ $role }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="members[{{ $i }}][employee_id]">
                                            <option value="">— Not linked —</option>
                                            @foreach($employees as $e)
                                                <option value="{{ $e->id }}" @selected($m->employee_id == $e->id)>
                                                    {{ ucfirst($e->lname) }}, {{ ucfirst($e->fname) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center" style="padding-top:.55rem;">
                                        <input type="hidden" name="members[{{ $i }}][active]" value="0">
                                        <input type="checkbox" name="members[{{ $i }}][active]" value="1" @checked($m->active)>
                                    </td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td><input type="hidden" name="members[0][id]" value=""><input type="text" name="members[0][name]"></td>
                                    <td><input type="text" name="members[0][credentials]"></td>
                                    <td><select name="members[0][role]">
                                        @foreach(['Chairperson', 'Vice-Chairperson', 'Member'] as $role)
                                            <option value="{{ $role }}">{{ $role }}</option>
                                        @endforeach
                                    </select></td>
                                    <td><select name="members[0][employee_id]">
                                        <option value="">— Not linked —</option>
                                        @foreach($employees as $e)
                                            <option value="{{ $e->id }}">{{ ucfirst($e->lname) }}, {{ ucfirst($e->fname) }}</option>
                                        @endforeach
                                    </select></td>
                                    <td class="text-center" style="padding-top:.55rem;">
                                        <input type="hidden" name="members[0][active]" value="0">
                                        <input type="checkbox" name="members[0][active]" value="1" checked>
                                    </td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <button type="button" class="rec-btn" id="add-member"><i class="fas fa-plus"></i> Add member</button>
                </div>
            </div>

            <div class="rec-sticky">
                <button type="submit" class="rec-btn primary"><i class="fas fa-save"></i> Save board</button>
            </div>
        </form>

    </div>
</div>

<script>
(function () {
    var form = document.getElementById('board-form');
    var body = document.querySelector('#member-rows tbody');

    document.getElementById('add-member').addEventListener('click', function () {
        var row = body.rows[body.rows.length - 1].cloneNode(true);
        var index = body.rows.length;

        row.querySelectorAll('input, select').forEach(function (el) {
            if (el.name) { el.name = el.name.replace(/\[\d+\]/, '[' + index + ']'); }
            if (el.type === 'checkbox') { el.checked = true; }
            else if (el.tagName === 'SELECT') { el.selectedIndex = 0; }
            else { el.value = ''; }   // clears the hidden id too, so this saves as a new member
        });

        body.appendChild(row);
    });

    form.addEventListener('click', function (e) {
        var remove = e.target.closest('.js-remove');
        if (!remove) return;

        // Removing a row and saving deletes that member; keep one row present.
        if (body.rows.length > 1) {
            remove.closest('tr').remove();
        } else {
            body.querySelectorAll('input[type="text"], input[type="hidden"]').forEach(function (el) { el.value = ''; });
        }
    });
})();
</script>
@endsection
