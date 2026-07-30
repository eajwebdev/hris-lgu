@extends('layouts.master')

@php
    $isNew   = ! $description->exists;
    $action  = $isNew ? route('positionDescriptionStore') : route('positionDescriptionUpdate', $description->id);
    $c       = $description->contacts ?? [];
    $wc      = $description->working_conditions ?? [];
    $core    = $description->core_competencies ?? [];
    $lead    = $description->leadership_competencies ?? [];
    $levels  = ['Basic', 'Intermediate', 'Advanced', 'Superior'];
@endphp

@section('body')
<div class="container-fluid">
    <form method="POST" action="{{ $action }}" id="pd-form">
        @csrf
        <div class="rec-page">

            <div class="rec-head">
                <div>
                    <h1>{{ $isNew ? 'New Position Description' : $description->full_title }}</h1>
                    <div class="sub">DBM-CSC Form No. 1 &middot; Revised Version No. 1, s. 2017</div>
                </div>
                <div class="rec-actions">
                    <a href="{{ route('positionDescriptionList') }}" class="rec-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    @unless($isNew)
                        <a href="{{ route('positionDescriptionPrint', $description->id) }}" target="_blank" class="rec-btn">
                            <i class="fas fa-print"></i> Print form
                        </a>
                    @endunless
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success"><i class="fas fa-check"></i> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Nothing was saved:
                    <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- 1-3 --}}
            <div class="rec-card">
                <header><span class="n">1&ndash;3</span><h2>Position identity</h2></header>
                <div class="body">
                    <div class="rec-grid">
                        <div class="rec-field f-6">
                            <label>1. Position title <span class="text-danger">*</span></label>
                            <input type="text" name="position_title" value="{{ old('position_title', $description->position_title) }}" required>
                        </div>
                        <div class="rec-field f-6">
                            <label>Parenthetical title</label>
                            <input type="text" name="parenthetical_title" value="{{ old('parenthetical_title', $description->parenthetical_title) }}">
                        </div>
                        <div class="rec-field f-6">
                            <label>2. Item number</label>
                            <input type="text" name="item_number" value="{{ old('item_number', $description->item_number) }}">
                        </div>
                        <div class="rec-field f-6">
                            <label>3. Salary grade</label>
                            <input type="text" name="salary_grade" value="{{ old('salary_grade', $description->salary_grade) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4-8 --}}
            <div class="rec-card">
                <header><span class="n">4&ndash;8</span><h2>Governmental unit &amp; office</h2></header>
                <div class="body">
                    <div class="rec-grid">
                        <div class="rec-field f-4">
                            <label>4. Governmental unit</label>
                            <select name="working_conditions[gov_unit]">
                                <option value="">— Select —</option>
                                @foreach(['province' => 'Province', 'city' => 'City', 'municipality' => 'Municipality'] as $k => $v)
                                    <option value="{{ $k }}" @selected(($wc['gov_unit'] ?? '') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rec-field f-4">
                            <label>Income class</label>
                            <select name="working_conditions[gov_class]">
                                <option value="">— Select —</option>
                                @foreach(['1st Class','2nd Class','3rd Class','4th Class','5th Class','6th Class','Special'] as $cl)
                                    <option value="{{ $cl }}" @selected(($wc['gov_class'] ?? '') === $cl)>{{ $cl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rec-field f-4">
                            <label>Unit and class (as written)</label>
                            <input type="text" name="lgu_unit_and_class" value="{{ old('lgu_unit_and_class', $description->lgu_unit_and_class) }}">
                        </div>
                        <div class="rec-field f-6">
                            <label>5. Department, corporation or agency / LGU</label>
                            <input type="text" name="department_agency" value="{{ old('department_agency', $description->department_agency) }}">
                        </div>
                        <div class="rec-field f-6">
                            <label>6. Bureau or office</label>
                            <input type="text" name="bureau_office" value="{{ old('bureau_office', $description->bureau_office) }}" list="office-list">
                            <datalist id="office-list">
                                @foreach($offices as $o)<option value="{{ $o->office_name }}">@endforeach
                            </datalist>
                        </div>
                        <div class="rec-field f-6">
                            <label>7. Department / branch / division</label>
                            <input type="text" name="division_branch" value="{{ old('division_branch', $description->division_branch) }}">
                        </div>
                        <div class="rec-field f-6">
                            <label>8. Workstation / place of work</label>
                            <input type="text" name="workstation" value="{{ old('workstation', $description->workstation) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 9-14 --}}
            <div class="rec-card">
                <header><span class="n">9&ndash;14</span><h2>Appropriation &amp; supervision</h2></header>
                <div class="body">
                    <div class="rec-grid">
                        <div class="rec-field f-3"><label>9. Present approp act</label>
                            <input type="text" name="present_approp_act" value="{{ old('present_approp_act', $description->present_approp_act) }}"></div>
                        <div class="rec-field f-3"><label>10. Previous approp act</label>
                            <input type="text" name="previous_approp_act" value="{{ old('previous_approp_act', $description->previous_approp_act) }}"></div>
                        <div class="rec-field f-3"><label>11. Salary authorized</label>
                            <input type="text" name="salary_authorized" value="{{ old('salary_authorized', $description->salary_authorized) }}"></div>
                        <div class="rec-field f-3"><label>12. Other compensation</label>
                            <input type="text" name="other_compensation" value="{{ old('other_compensation', $description->other_compensation) }}"></div>
                        <div class="rec-field f-6"><label>13. Position title of immediate supervisor</label>
                            <input type="text" name="immediate_supervisor_title" value="{{ old('immediate_supervisor_title', $description->immediate_supervisor_title) }}"></div>
                        <div class="rec-field f-6"><label>14. Position title of next higher supervisor</label>
                            <input type="text" name="next_higher_supervisor_title" value="{{ old('next_higher_supervisor_title', $description->next_higher_supervisor_title) }}"></div>
                    </div>
                </div>
            </div>

            {{-- 15 --}}
            <div class="rec-card">
                <header>
                    <span class="n">15</span><h2>Positions directly supervised</h2>
                    <span class="hint">if more than seven, list only item numbers and titles</span>
                </header>
                <div class="body">
                    <table class="rec-rows" id="supervised-rows">
                        <thead><tr><th>Position title</th><th style="width:200px;">Item number</th><th style="width:44px;"></th></tr></thead>
                        <tbody>
                            @forelse($description->supervised as $i => $s)
                                <tr>
                                    <td><input type="text" name="supervised[{{ $i }}][position_title]" value="{{ $s->position_title }}"></td>
                                    <td><input type="text" name="supervised[{{ $i }}][item_number]" value="{{ $s->item_number }}"></td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td><input type="text" name="supervised[0][position_title]"></td>
                                    <td><input type="text" name="supervised[0][item_number]"></td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <button type="button" class="rec-btn js-add" data-target="supervised-rows" data-name="supervised">
                        <i class="fas fa-plus"></i> Add position
                    </button>
                </div>
            </div>

            {{-- 16 --}}
            <div class="rec-card">
                <header><span class="n">16</span><h2>Machine, equipment, tools used regularly</h2></header>
                <div class="body">
                    <div class="rec-field">
                        <textarea name="equipment_used">{{ old('equipment_used', $description->equipment_used) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- 17 --}}
            <div class="rec-card">
                <header><span class="n">17</span><h2>Contacts / clients / stakeholders</h2></header>
                <div class="body">
                    <div class="rec-grid">
                        <div class="f-6">
                            <label style="font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);">17a. Internal</label>
                            @foreach($internal as $key => $label)
                                <div class="d-flex align-items-center justify-content-between" style="padding:.3rem 0;border-bottom:1px solid var(--border);">
                                    <span style="font-size:.85rem;">{{ $label }}</span>
                                    <span class="text-nowrap">
                                        @foreach($frequencies as $fk => $fl)
                                            <label style="font-size:.78rem;margin:0 0 0 12px;font-weight:500;">
                                                <input type="radio" name="contacts[internal][{{ $key }}]" value="{{ $fk }}"
                                                       @checked(($c['internal'][$key] ?? null) === $fk)> {{ $fl }}
                                            </label>
                                        @endforeach
                                        <label style="font-size:.78rem;margin:0 0 0 12px;font-weight:500;color:var(--muted);">
                                            <input type="radio" name="contacts[internal][{{ $key }}]" value=""
                                                   @checked(empty($c['internal'][$key]))> None
                                        </label>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="f-6">
                            <label style="font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);">17b. External</label>
                            @foreach($external as $key => $label)
                                <div class="d-flex align-items-center justify-content-between" style="padding:.3rem 0;border-bottom:1px solid var(--border);">
                                    <span style="font-size:.85rem;">{{ $label }}</span>
                                    <span class="text-nowrap">
                                        @foreach($frequencies as $fk => $fl)
                                            <label style="font-size:.78rem;margin:0 0 0 12px;font-weight:500;">
                                                <input type="radio" name="contacts[external][{{ $key }}]" value="{{ $fk }}"
                                                       @checked(($c['external'][$key] ?? null) === $fk)> {{ $fl }}
                                            </label>
                                        @endforeach
                                        <label style="font-size:.78rem;margin:0 0 0 12px;font-weight:500;color:var(--muted);">
                                            <input type="radio" name="contacts[external][{{ $key }}]" value=""
                                                   @checked(empty($c['external'][$key]))> None
                                        </label>
                                    </span>
                                </div>
                            @endforeach
                            <div class="rec-field" style="margin-top:.6rem;">
                                <label>Others, please specify</label>
                                <input type="text" name="contacts[external_others_specify]" value="{{ $c['external_others_specify'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 18 --}}
            <div class="rec-card">
                <header><span class="n">18</span><h2>Working condition</h2></header>
                <div class="body">
                    <div class="rec-grid">
                        <div class="f-6" style="padding-top:.4rem;">
                            <label style="font-weight:500;font-size:.87rem;display:block;margin-bottom:.4rem;">
                                <input type="checkbox" name="working_conditions[office_work]" value="1" @checked(!empty($wc['office_work']))> Office work
                            </label>
                            <label style="font-weight:500;font-size:.87rem;display:block;">
                                <input type="checkbox" name="working_conditions[field_work]" value="1" @checked(!empty($wc['field_work']))> Field work
                            </label>
                        </div>
                        <div class="rec-field f-6">
                            <label>Other/s (please specify)</label>
                            <input type="text" name="working_conditions[others]" value="{{ $wc['others'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 19-20 --}}
            <div class="rec-card">
                <header><span class="n">19&ndash;20</span><h2>General functions</h2></header>
                <div class="body">
                    <div class="rec-grid">
                        <div class="rec-field f-6">
                            <label>19. General function of the unit or section</label>
                            <textarea name="unit_general_function">{{ old('unit_general_function', $description->unit_general_function) }}</textarea>
                        </div>
                        <div class="rec-field f-6">
                            <label>20. General function of the position (job summary)</label>
                            <textarea name="position_general_function">{{ old('position_general_function', $description->position_general_function) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 21 --}}
            <div class="rec-card">
                <header><span class="n">21</span><h2>Qualification standards</h2>
                    <span class="hint">shown on every posting of this item</span></header>
                <div class="body">
                    <div class="rec-grid">
                        <div class="rec-field f-3"><label>21a. Education</label>
                            <textarea name="qs_education">{{ old('qs_education', $description->qs_education) }}</textarea></div>
                        <div class="rec-field f-3"><label>21b. Experience</label>
                            <textarea name="qs_experience">{{ old('qs_experience', $description->qs_experience) }}</textarea></div>
                        <div class="rec-field f-3"><label>21c. Training</label>
                            <textarea name="qs_training">{{ old('qs_training', $description->qs_training) }}</textarea></div>
                        <div class="rec-field f-3"><label>21d. Eligibility</label>
                            <textarea name="qs_eligibility">{{ old('qs_eligibility', $description->qs_eligibility) }}</textarea></div>
                    </div>

                    <hr>

                    <label style="font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);">21e. Core competencies</label>
                    <table class="rec-rows" id="core-rows">
                        <thead><tr><th>Competency</th><th style="width:200px;">Competency level</th><th style="width:44px;"></th></tr></thead>
                        <tbody>
                            @forelse($core as $i => $row)
                                <tr>
                                    <td><input type="text" name="core_competencies[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}"></td>
                                    <td><select name="core_competencies[{{ $i }}][level]">
                                        <option value="">—</option>
                                        @foreach($levels as $l)<option value="{{ $l }}" @selected(($row['level'] ?? '') === $l)>{{ $l }}</option>@endforeach
                                    </select></td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td><input type="text" name="core_competencies[0][name]"></td>
                                    <td><select name="core_competencies[0][level]"><option value="">—</option>
                                        @foreach($levels as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach</select></td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <button type="button" class="rec-btn js-add" data-target="core-rows" data-name="core_competencies">
                        <i class="fas fa-plus"></i> Add core competency
                    </button>

                    <hr>

                    <label style="font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);">21f. Leadership competencies</label>
                    <table class="rec-rows" id="lead-rows">
                        <thead><tr><th>Competency</th><th style="width:200px;">Competency level</th><th style="width:44px;"></th></tr></thead>
                        <tbody>
                            @forelse($lead as $i => $row)
                                <tr>
                                    <td><input type="text" name="leadership_competencies[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}"></td>
                                    <td><select name="leadership_competencies[{{ $i }}][level]">
                                        <option value="">—</option>
                                        @foreach($levels as $l)<option value="{{ $l }}" @selected(($row['level'] ?? '') === $l)>{{ $l }}</option>@endforeach
                                    </select></td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td><input type="text" name="leadership_competencies[0][name]"></td>
                                    <td><select name="leadership_competencies[0][level]"><option value="">—</option>
                                        @foreach($levels as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach</select></td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <button type="button" class="rec-btn js-add" data-target="lead-rows" data-name="leadership_competencies">
                        <i class="fas fa-plus"></i> Add leadership competency
                    </button>
                </div>
            </div>

            {{-- 22 --}}
            <div class="rec-card">
                <header>
                    <span class="n">22</span><h2>Statement of duties and responsibilities</h2>
                    <span class="hint">technical competencies</span>
                    <span id="duty-total" class="rec-total" style="margin-left:auto;">0% of working time</span>
                </header>
                <div class="body">
                    <table class="rec-rows" id="duty-rows">
                        <thead>
                            <tr>
                                <th style="width:120px;">% of time</th>
                                <th>Duty / responsibility</th>
                                <th style="width:180px;">Competency level</th>
                                <th style="width:44px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($description->duties as $i => $d)
                                <tr>
                                    <td><input type="number" step="0.01" min="0" max="100" class="js-pct"
                                               name="duties[{{ $i }}][percentage]" value="{{ $d->percentage }}"></td>
                                    <td><textarea name="duties[{{ $i }}][duty]" style="min-height:64px;">{{ $d->duty }}</textarea></td>
                                    <td><select name="duties[{{ $i }}][competency_level]">
                                        <option value="">—</option>
                                        @foreach($levels as $l)<option value="{{ $l }}" @selected($d->competency_level === $l)>{{ $l }}</option>@endforeach
                                    </select></td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td><input type="number" step="0.01" min="0" max="100" class="js-pct" name="duties[0][percentage]"></td>
                                    <td><textarea name="duties[0][duty]" style="min-height:64px;"></textarea></td>
                                    <td><select name="duties[0][competency_level]"><option value="">—</option>
                                        @foreach($levels as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach</select></td>
                                    <td><button type="button" class="rec-btn ghost-danger js-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <button type="button" class="rec-btn js-add" data-target="duty-rows" data-name="duties">
                        <i class="fas fa-plus"></i> Add duty
                    </button>
                </div>
            </div>

            {{-- Publication. This is what used to be the separate "Job Openings"
                 screen: the same position, advertised. Everything descriptive is
                 taken from the sections above, so nothing is typed twice. --}}
            <div class="rec-card">
                <header>
                    <span class="n"><i class="fas fa-bullhorn"></i></span>
                    <h2>Publication &mdash; advertise this position</h2>
                    <span class="hint">
                        @if($posting)
                            currently {{ strtolower($posting->status) }} &middot;
                            {{ $posting->applications()->count() }} applicant(s)
                        @else
                            optional &mdash; fill this in when the item becomes vacant
                        @endif
                    </span>
                </header>
                <div class="body">
                    <div class="rec-grid">
                        <div class="rec-field f-3">
                            <label>Nature of appointment</label>
                            <select name="type">
                                @foreach($types as $value => $label)
                                    <option value="{{ $value }}" @selected(optional($posting)->type === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rec-field f-3">
                            <label>Monthly salary</label>
                            <input type="number" step="0.01" min="0" name="salary" value="{{ optional($posting)->salary }}">
                        </div>
                        <div class="rec-field f-3">
                            <label>Date posted</label>
                            <input type="date" name="posted_at" value="{{ optional($posting)->posted_at }}">
                        </div>
                        <div class="rec-field f-3">
                            <label>Closing date</label>
                            <input type="date" name="expiration_at" value="{{ optional($posting)->expiration_at }}">
                        </div>
                        <div class="rec-field f-3">
                            <label>Vacancy status</label>
                            <select name="vacancy_status">
                                <option value="Open"   @selected(optional($posting)->status !== 'Closed')>Open — accepting applications</option>
                                <option value="Closed" @selected(optional($posting)->status === 'Closed')>Closed</option>
                            </select>
                        </div>

                        @if($posting)
                            <div class="f-9" style="padding-top:1.35rem;">
                                <label style="font-weight:500;font-size:.85rem;">
                                    <input type="checkbox" name="new_round" value="1">
                                    Publish as a <b>new recruitment round</b> rather than editing the current one
                                </label>
                                <small class="d-block text-muted" style="font-size:.76rem;">
                                    Use this when re-advertising the item later. The existing round keeps its own
                                    applicants, interview panel and Comparative Assessment.
                                </small>
                            </div>
                        @endif
                    </div>

                    @if($posting)
                        <div style="margin-top:.9rem;display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="{{ route('psbAssessment', $posting->id) }}" class="rec-btn">
                                <i class="fas fa-scale-balanced"></i> Comparative Assessment
                            </a>
                            <a href="{{ route('careersPortal') }}" target="_blank" class="rec-btn">
                                <i class="fas fa-up-right-from-square"></i> View on careers portal
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rec-sticky">
                <div style="margin-right:auto;">
                    <select name="status" class="form-control form-control-sm" style="width:150px;">
                        <option value="active" @selected($description->status !== 'archived')>Active</option>
                        <option value="archived" @selected($description->status === 'archived')>Archived</option>
                    </select>
                </div>
                <a href="{{ route('positionDescriptionList') }}" class="rec-btn">Cancel</a>
                <button type="submit" class="rec-btn primary">
                    <i class="fas fa-save"></i> {{ $isNew ? 'Create description' : 'Save changes' }}
                </button>
            </div>

        </div>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('pd-form');

    /* Repeating rows. A new row is cloned from the last one in its table and
       re-indexed, so the markup for a row lives in exactly one place. */
    form.addEventListener('click', function (e) {
        var add = e.target.closest('.js-add');
        if (add) {
            var table = document.getElementById(add.dataset.target);
            var body  = table.querySelector('tbody');
            var last  = body.rows[body.rows.length - 1];
            var row   = last.cloneNode(true);
            var index = body.rows.length;

            row.querySelectorAll('input, textarea, select').forEach(function (el) {
                if (el.name) {
                    el.name = el.name.replace(/\[\d+\]/, '[' + index + ']');
                }
                if (el.tagName === 'SELECT') { el.selectedIndex = 0; } else { el.value = ''; }
            });

            body.appendChild(row);
            recalcDuties();
            return;
        }

        var remove = e.target.closest('.js-remove');
        if (remove) {
            var tbody = remove.closest('tbody');
            // Keep one row so the section never collapses to nothing.
            if (tbody.rows.length > 1) {
                remove.closest('tr').remove();
            } else {
                tbody.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
            }
            recalcDuties();
        }
    });

    /* Section 22 must account for the whole working week; the badge turns red
       past 100 so it is caught here rather than at signing. */
    function recalcDuties() {
        var total = 0;
        form.querySelectorAll('.js-pct').forEach(function (el) {
            total += parseFloat(el.value) || 0;
        });
        var badge = document.getElementById('duty-total');
        var shown = Math.round(total * 100) / 100;
        badge.textContent = shown + '% of working time';
        badge.classList.toggle('over', shown > 100);
    }

    form.addEventListener('input', function (e) {
        if (e.target.classList.contains('js-pct')) { recalcDuties(); }
    });

    recalcDuties();
})();
</script>
@endsection
