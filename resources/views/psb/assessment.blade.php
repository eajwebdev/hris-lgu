@extends('layouts.master')

@php
    $locked = $assessment && $assessment->isFinalised();
    $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp

@section('body')
<div class="container-fluid">
    <div class="rec-page" style="max-width:1400px;">

        <div class="rec-head">
            <div>
                <h1>Comparative Assessment</h1>
                <div class="sub">
                    Personnel Selection Board &middot; {{ $job->title }}
                    @if($job->plantilla_item_no) &middot; Item {{ $job->plantilla_item_no }} @endif
                </div>
            </div>
            <div class="rec-actions">
                <a href="{{ route('psbMembers') }}" class="rec-btn"><i class="fas fa-user-tie"></i> Board</a>
                <form method="POST" action="{{ route('psbAssessmentBuild', $job->id) }}" class="d-inline">
                    @csrf
                    <button class="rec-btn" @disabled($locked)>
                        <i class="fas fa-rotate"></i> {{ $assessment ? 'Rebuild from evaluations' : 'Build from evaluations' }}
                    </button>
                </form>
                @if($assessment)
                    <a href="{{ route('psbAssessmentPrint', $assessment->id) }}" target="_blank" class="rec-btn primary">
                        <i class="fas fa-print"></i> Print form
                    </a>
                @endif
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

        @if(! $assessment)
            <div class="rec-card">
                <div class="rec-empty">
                    <div class="big"><i class="far fa-clipboard"></i></div>
                    <p style="max-width:520px;margin:0 auto 14px;">
                        No assessment sheet yet. Building one pulls in every candidate for this vacancy and
                        fills the columns that are already measured — education, training and experience from
                        the ETE evaluation, potential and psychosocial attributes from the panel interview.
                        You then key in the performance rating and any written exam.
                    </p>
                    <form method="POST" action="{{ route('psbAssessmentBuild', $job->id) }}">
                        @csrf
                        <button class="rec-btn primary"><i class="fas fa-wand-magic-sparkles"></i> Build assessment</button>
                    </form>
                </div>
            </div>
        @else
            @if($locked)
                <div class="alert alert-info">
                    <i class="fas fa-lock"></i> Finalised {{ $assessment->finalised_at->format('F j, Y g:i A') }}.
                    The sheet is read-only; print it for the board to sign.
                </div>
            @endif

            <form method="POST" action="{{ route('psbAssessmentSave', $assessment->id) }}">
                @csrf

                <div class="rec-card">
                    <header><span class="n"><i class="fas fa-file-lines"></i></span><h2>Form header</h2></header>
                    <div class="body">
                        <div class="rec-grid">
                            <div class="rec-field f-6"><label>Position to be filled</label>
                                <input type="text" name="position_to_be_filled" value="{{ $assessment->position_to_be_filled }}" @disabled($locked)></div>
                            <div class="rec-field f-3"><label>Item no.</label>
                                <input type="text" name="item_no" value="{{ $assessment->item_no }}" @disabled($locked)></div>
                            <div class="rec-field f-3"><label>Location</label>
                                <input type="text" name="location" value="{{ $assessment->location }}" @disabled($locked)></div>
                            <div class="rec-field f-4"><label>Date posted</label>
                                <input type="date" name="date_posted" value="{{ optional($assessment->date_posted)->format('Y-m-d') }}" @disabled($locked)></div>
                            <div class="rec-field f-4"><label>Date published</label>
                                <input type="date" name="date_published" value="{{ optional($assessment->date_published)->format('Y-m-d') }}" @disabled($locked)></div>
                            <div class="rec-field f-4"><label>Rate / month</label>
                                <input type="text" name="rate_per_month" value="{{ $assessment->rate_per_month }}" @disabled($locked)></div>
                            <div class="rec-field f-12"><label>Further assessment column heading</label>
                                <input type="text" name="further_assessment_label" @disabled($locked)
                                       value="{{ $assessment->rows->firstWhere('further_assessment_label','!=',null)?->further_assessment_label ?? 'WRITTEN EXAM/ SKILLS/ TEST/ ETC.' }}"></div>
                        </div>
                    </div>
                </div>

                <div class="rec-card">
                    <header>
                        <span class="n"><i class="fas fa-users"></i></span>
                        <h2>Candidates</h2>
                        <span class="hint">
                            preliminary evaluation totals 100 &middot; overall points add the further assessment &middot;
                            rank is derived, never typed
                        </span>
                    </header>
                    <div class="body" style="padding:0;">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" style="font-size:.82rem;">
                                <thead>
                                    <tr style="background:var(--muted-soft);">
                                        <th style="min-width:170px;">Candidate</th>
                                        <th style="min-width:150px;">Present position / SG / status</th>
                                        <th style="min-width:110px;">CS eligibility</th>
                                        @foreach($labels as $key => $label)
                                            <th class="text-center" style="width:92px;font-size:.66rem;line-height:1.15;">
                                                {{ $label }}<br><span style="color:var(--muted);">({{ $weights[$key] }}%)</span>
                                            </th>
                                        @endforeach
                                        <th class="text-center" style="width:74px;">Total<br>(100%)</th>
                                        <th class="text-center" style="width:88px;">Further</th>
                                        <th class="text-center" style="width:74px;">Overall</th>
                                        <th class="text-center" style="width:56px;">Rank</th>
                                        <th style="min-width:150px;">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($assessment->rows as $row)
                                    <tr>
                                        <td style="font-weight:600;">{{ $row->candidate_name }}</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" @disabled($locked)
                                                   name="rows[{{ $row->id }}][present_position]" value="{{ $row->present_position }}" placeholder="Position">
                                            <div class="d-flex" style="gap:4px;margin-top:3px;">
                                                <input type="text" class="form-control form-control-sm" @disabled($locked) style="width:60px;"
                                                       name="rows[{{ $row->id }}][salary_grade]" value="{{ $row->salary_grade }}" placeholder="SG">
                                                <input type="text" class="form-control form-control-sm" @disabled($locked)
                                                       name="rows[{{ $row->id }}][appointment_status]" value="{{ $row->appointment_status }}" placeholder="Status">
                                            </div>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" @disabled($locked)
                                                   name="rows[{{ $row->id }}][civil_service_eligibility]" value="{{ $row->civil_service_eligibility }}"></td>
                                        @foreach($labels as $key => $label)
                                            <td>
                                                <input type="number" step="0.01" min="0" max="{{ $weights[$key] }}" @disabled($locked)
                                                       class="form-control form-control-sm text-center"
                                                       name="rows[{{ $row->id }}][{{ $key }}]" value="{{ $num($row->{$key}) }}">
                                            </td>
                                        @endforeach
                                        <td class="text-center" style="font-weight:700;">{{ $num($row->preliminary_total) }}</td>
                                        <td><input type="number" step="0.01" min="0" @disabled($locked)
                                                   class="form-control form-control-sm text-center"
                                                   name="rows[{{ $row->id }}][further_assessment]" value="{{ $num($row->further_assessment) }}"></td>
                                        <td class="text-center" style="font-weight:700;color:var(--accent-600);">{{ $num($row->overall_points) }}</td>
                                        <td class="text-center">
                                            <span class="rec-pill active" style="font-size:.8rem;">{{ $row->rank ?? '—' }}</span>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" @disabled($locked)
                                                   name="rows[{{ $row->id }}][remarks]" value="{{ $row->remarks }}"></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="14" class="rec-empty">No candidates on this sheet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="rec-card">
                    <header>
                        <span class="n"><i class="fas fa-signature"></i></span>
                        <h2>Personnel Selection Board</h2>
                        <span class="hint">
                            signs this hiring &middot; started from the standing board, but edits here
                            apply to this form only
                        </span>
                    </header>
                    <div class="body">
                        <table class="rec-rows" id="board-rows">
                            <thead>
                                <tr>
                                    <th>Printed name</th>
                                    <th style="width:130px;">Credentials</th>
                                    <th style="width:170px;">Role</th>
                                    <th style="width:44px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assessment->boardMembers as $i => $m)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="board[{{ $i }}][id]" value="{{ $m->id }}">
                                            <input type="text" name="board[{{ $i }}][name]" value="{{ $m->name }}"
                                                   list="employee-names" @disabled($locked)>
                                        </td>
                                        <td><input type="text" name="board[{{ $i }}][credentials]" value="{{ $m->credentials }}"
                                                   placeholder="RN, JD" @disabled($locked)></td>
                                        <td>
                                            <select name="board[{{ $i }}][role]" @disabled($locked)>
                                                @foreach(['Chairperson', 'Vice-Chairperson', 'Member'] as $role)
                                                    <option value="{{ $role }}" @selected($m->role === $role)>{{ $role }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            @unless($locked)
                                                <button type="button" class="rec-btn ghost-danger js-board-remove">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>
                                            <input type="hidden" name="board[0][id]" value="">
                                            <input type="text" name="board[0][name]" list="employee-names" @disabled($locked)>
                                        </td>
                                        <td><input type="text" name="board[0][credentials]" placeholder="RN, JD" @disabled($locked)></td>
                                        <td><select name="board[0][role]" @disabled($locked)>
                                            @foreach(['Chairperson', 'Vice-Chairperson', 'Member'] as $role)
                                                <option value="{{ $role }}">{{ $role }}</option>
                                            @endforeach
                                        </select></td>
                                        <td>
                                            @unless($locked)
                                                <button type="button" class="rec-btn ghost-danger js-board-remove"><i class="fas fa-times"></i></button>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{-- Names can be picked from the employee list or simply typed;
                             what is stored is the text, not a link to the record. --}}
                        <datalist id="employee-names">
                            @foreach($employees as $e)
                                <option value="{{ ucfirst($e->fname) }} {{ ucfirst($e->lname) }}">
                            @endforeach
                        </datalist>

                        @unless($locked)
                            <button type="button" class="rec-btn" id="add-board-member">
                                <i class="fas fa-plus"></i> Add signatory
                            </button>
                            <a href="{{ route('psbMembers') }}" class="rec-btn" style="margin-left:6px;">
                                <i class="fas fa-gear"></i> Edit standing board
                            </a>
                        @endunless
                    </div>
                </div>

                @unless($locked)
                    <div class="rec-sticky">
                        <button type="submit" class="rec-btn primary"><i class="fas fa-save"></i> Save &amp; re-rank</button>
                    </div>
                @endunless
            </form>

            @unless($locked)
                <form method="POST" action="{{ route('psbAssessmentFinalise', $assessment->id) }}" class="text-right mt-2"
                      onsubmit="return confirm('Finalise this assessment? It becomes read-only.');">
                    @csrf
                    <button class="rec-btn"><i class="fas fa-lock"></i> Finalise</button>
                </form>
            @endunless

        @endif

    </div>
</div>

<script>
(function () {
    var table = document.getElementById('board-rows');
    if (!table) return;

    var body = table.querySelector('tbody');
    var add  = document.getElementById('add-board-member');

    if (add) {
        add.addEventListener('click', function () {
            var row = body.rows[body.rows.length - 1].cloneNode(true);
            var index = body.rows.length;

            row.querySelectorAll('input, select').forEach(function (el) {
                if (el.name) { el.name = el.name.replace(/\[\d+\]/, '[' + index + ']'); }
                // Clearing the hidden id is what makes this save as a new
                // signatory rather than renaming an existing one.
                if (el.tagName === 'SELECT') { el.selectedIndex = 0; } else { el.value = ''; }
            });

            body.appendChild(row);
        });
    }

    body.addEventListener('click', function (e) {
        var remove = e.target.closest('.js-board-remove');
        if (!remove) return;

        if (body.rows.length > 1) {
            remove.closest('tr').remove();
        } else {
            body.querySelectorAll('input').forEach(function (el) { el.value = ''; });
        }
    });
})();
</script>
@endsection
