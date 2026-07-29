@php
    /**
     * DBM-CSC Form No. 1 (Revised Version No. 1, s. 2017)
     * Position Description Form.
     *
     * The grid mirrors the source workbook exactly: six columns at the widths
     * set in the xlsx (22.71 / 16.71 / 11.29 / 24.86 / 13.57 / 13.29 chars),
     * and the same merge pattern, so a printed copy lines up with the official
     * form field for field.
     */
    $contacts   = $description->contacts ?? [];
    $conditions = $description->working_conditions ?? [];
    $core       = $description->core_competencies ?? [];
    $leadership = $description->leadership_competencies ?? [];

    // A tick that survives both screen and print (no icon font in the PDF path).
    $tick = fn ($on) => $on ? '&#10003;' : '&nbsp;';

    $isChecked = function ($group, $key, $value = true) use ($contacts) {
        return ($contacts[$group][$key] ?? null) === $value || ($contacts[$group][$key] ?? null) === 'on';
    };

    $govUnits   = ['Province' => 'province', 'City' => 'city', 'Municipality' => 'municipality'];
    $govClasses = ['1st Class', '2nd Class', '3rd Class', '4th Class', '5th Class', '6th Class', 'Special'];
    $unitPick   = $conditions['gov_unit'] ?? null;
    $classPick  = $conditions['gov_class'] ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Position Description Form — {{ $description->position_title }}</title>
<style>
    /* Legal paper is what the office prints these on. */
    @page { size: 8.5in 13in; margin: 0.5in 0.45in; }

    * { box-sizing: border-box; }

    body {
        font-family: "Arial", "Helvetica", sans-serif;
        font-size: 8.5pt;
        color: #000;
        margin: 0;
        background: #fff;
    }

    .sheet { width: 100%; }

    table.form {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.form td {
        border: 0.75pt solid #000;
        padding: 2pt 3pt;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Column widths taken straight from the workbook. */
    .c1 { width: 22.17%; }
    .c2 { width: 16.31%; }
    .c3 { width: 11.02%; }
    .c4 { width: 24.27%; }
    .c5 { width: 13.25%; }
    .c6 { width: 12.97%; }

    .lbl   { font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: .01em; }
    .lbl-n { font-size: 7pt; font-weight: 700; }
    .note  { font-size: 6.5pt; font-style: italic; font-weight: 400; text-transform: none; }
    .val   { font-size: 9pt; min-height: 15pt; }
    .val-lg{ font-size: 9pt; min-height: 44pt; }
    .center{ text-align: center; }
    .band  { background: #e8e8e8; }
    .nb    { border: 0 !important; }

    /* Masthead */
    .masthead { text-align: center; margin-bottom: 5pt; }
    .masthead .rp    { font-size: 8.5pt; }
    .masthead .title { font-size: 13pt; font-weight: 700; letter-spacing: .06em; margin-top: 1pt; }
    .masthead .code  { font-size: 7.5pt; margin-top: 1pt; }

    .box {
        display: inline-block;
        width: 9pt; height: 9pt;
        border: 0.75pt solid #000;
        text-align: center;
        line-height: 8pt;
        font-size: 8pt;
        margin-right: 2pt;
        vertical-align: middle;
    }
    .opt { display: inline-block; margin-right: 8pt; white-space: nowrap; font-size: 7.5pt; }

    .sig-line { margin-top: 22pt; border-bottom: 0.75pt solid #000; }
    .sig-cap  { font-size: 7pt; text-align: center; padding-top: 2pt; }

    @media screen {
        body { background: #F7F8FA; padding: 18px; }
        .sheet { max-width: 8.5in; margin: 0 auto; background: #fff; padding: 0.5in 0.45in;
                 box-shadow: 0 18px 40px -12px rgba(15,23,42,.18); border-radius: 4px; }
        .toolbar { max-width: 8.5in; margin: 0 auto 14px; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button, .toolbar a {
            font: 600 13px/1 "Inter", system-ui, sans-serif;
            padding: 10px 16px; border-radius: 8px; border: 1px solid #D5D9E0;
            background: #fff; color: #0F172A; cursor: pointer; text-decoration: none;
        }
        .toolbar .primary { background: #1E7A45; border-color: #1E7A45; color: #fff; }
    }
    @media print { .toolbar { display: none !important; } }
</style>
</head>
<body>

<div class="toolbar">
    <a href="{{ route('positionDescriptionEdit', $description->id) }}">&larr; Back to form</a>
    <button class="primary" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="sheet">

    <div class="masthead">
        <div class="rp">Republic of the Philippines</div>
        <div class="title">POSITION DESCRIPTION FORM</div>
        <div class="code">DBM-CSC Form No. 1 (Revised Version No. 1, s. 2017)</div>
    </div>

    <table class="form">
        <colgroup>
            <col class="c1"><col class="c2"><col class="c3"><col class="c4"><col class="c5"><col class="c6">
        </colgroup>

        {{-- 1 --}}
        <tr>
            <td colspan="6" class="lbl-n">
                1.&nbsp; POSITION TITLE <span class="note">(as approved by authorized agency)</span> with parenthetical title
            </td>
        </tr>
        <tr>
            <td colspan="6" class="val center" style="font-weight:700;">{{ $description->full_title }}</td>
        </tr>

        {{-- 2, 3 --}}
        <tr>
            <td colspan="3" class="lbl-n">2.&nbsp; ITEM NUMBER</td>
            <td colspan="3" class="lbl-n">3.&nbsp; SALARY GRADE</td>
        </tr>
        <tr>
            <td colspan="3" class="val">{{ $description->item_number }}</td>
            <td colspan="3" class="val">{{ $description->salary_grade }}</td>
        </tr>

        {{-- 4 --}}
        <tr>
            <td colspan="6" class="lbl-n">4.&nbsp; FOR LOCAL GOVERNMENT POSITION, ENUMERATE GOVERNMENTAL UNIT AND CLASS</td>
        </tr>
        <tr>
            <td colspan="6">
                <div style="margin-bottom:3pt;">
                    @foreach($govUnits as $label => $key)
                        <span class="opt"><span class="box">{!! $tick($unitPick === $key) !!}</span>{{ $label }}</span>
                    @endforeach
                </div>
                <div>
                    @foreach($govClasses as $class)
                        <span class="opt"><span class="box">{!! $tick($classPick === $class) !!}</span>{{ $class }}</span>
                    @endforeach
                </div>
                @if($description->lgu_unit_and_class)
                    <div class="val" style="margin-top:2pt;">{{ $description->lgu_unit_and_class }}</div>
                @endif
            </td>
        </tr>

        {{-- 5, 6 --}}
        <tr>
            <td colspan="3" class="lbl-n">5.&nbsp; DEPARTMENT, CORPORATION OR AGENCY/<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;LOCAL GOVERNMENT</td>
            <td colspan="3" class="lbl-n">6.&nbsp; BUREAU OR OFFICE</td>
        </tr>
        <tr>
            <td colspan="3" class="val">{{ $description->department_agency }}</td>
            <td colspan="3" class="val">{{ $description->bureau_office }}</td>
        </tr>

        {{-- 7, 8 --}}
        <tr>
            <td colspan="3" class="lbl-n">7.&nbsp; DEPARTMENT / BRANCH / DIVISION</td>
            <td colspan="3" class="lbl-n">8.&nbsp; WORKSTATION / PLACE OF WORK</td>
        </tr>
        <tr>
            <td colspan="3" class="val">{{ $description->division_branch }}</td>
            <td colspan="3" class="val">{{ $description->workstation }}</td>
        </tr>

        {{-- 9, 10, 11, 12 --}}
        <tr>
            <td class="lbl-n">9.&nbsp; PRESENT APPROP ACT</td>
            <td colspan="2" class="lbl-n">10.&nbsp; PREVIOUS APPROP ACT</td>
            <td class="lbl-n">11.&nbsp; SALARY AUTHORIZED</td>
            <td colspan="2" class="lbl-n">12.&nbsp; OTHER COMPENSATION</td>
        </tr>
        <tr>
            <td class="val">{{ $description->present_approp_act }}</td>
            <td colspan="2" class="val">{{ $description->previous_approp_act }}</td>
            <td class="val">{{ $description->salary_authorized }}</td>
            <td colspan="2" class="val">{{ $description->other_compensation }}</td>
        </tr>

        {{-- 13, 14 --}}
        <tr>
            <td colspan="3" class="lbl-n">13.&nbsp; POSITION TITLE OF IMMEDIATE SUPERVISOR</td>
            <td colspan="3" class="lbl-n">14.&nbsp; POSITION TITLE OF NEXT HIGHER SUPERVISOR</td>
        </tr>
        <tr>
            <td colspan="3" class="val">{{ $description->immediate_supervisor_title }}</td>
            <td colspan="3" class="val">{{ $description->next_higher_supervisor_title }}</td>
        </tr>

        {{-- 15 --}}
        <tr>
            <td colspan="6" class="lbl-n">
                15.&nbsp; POSITION TITLE, AND ITEM OF THOSE DIRECTLY SUPERVISED
                <span class="note">(if more than seven (7) list only by their item numbers and titles)</span>
            </td>
        </tr>
        <tr class="band">
            <td colspan="3" class="lbl center">POSITION TITLE</td>
            <td colspan="3" class="lbl center">ITEM NUMBER</td>
        </tr>
        @forelse($description->supervised as $row)
            <tr>
                <td colspan="3" class="val">{{ $row->position_title }}</td>
                <td colspan="3" class="val">{{ $row->item_number }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="val">&nbsp;</td><td colspan="3" class="val">&nbsp;</td></tr>
        @endforelse

        {{-- 16 --}}
        <tr>
            <td colspan="6" class="lbl-n">16.&nbsp; MACHINE, EQUIPMENT, TOOLS, ETC., USED REGULARLY IN PERFORMANCE OF WORK</td>
        </tr>
        <tr><td colspan="6" class="val-lg">{!! nl2br(e($description->equipment_used)) !!}</td></tr>

        {{-- 17 --}}
        <tr>
            <td colspan="6" class="lbl-n">17.&nbsp; CONTACTS / CLIENTS / STAKEHOLDERS</td>
        </tr>
        <tr class="band">
            <td class="lbl">17a.&nbsp; Internal</td>
            <td class="lbl center">Occasional</td>
            <td class="lbl center">Frequent</td>
            <td class="lbl">17b.&nbsp; External</td>
            <td class="lbl center">Occasional</td>
            <td class="lbl center">Frequent</td>
        </tr>
        @php
            $internalKeys = array_keys($internal);
            $externalKeys = array_keys($external);
            $rowCount = max(count($internalKeys), count($externalKeys));
        @endphp
        @for($i = 0; $i < $rowCount; $i++)
            @php
                $iKey = $internalKeys[$i] ?? null;
                $eKey = $externalKeys[$i] ?? null;
            @endphp
            <tr>
                <td>{{ $iKey ? $internal[$iKey] : '' }}</td>
                <td class="center">{!! $iKey ? $tick(($contacts['internal'][$iKey] ?? null) === 'occasional') : '' !!}</td>
                <td class="center">{!! $iKey ? $tick(($contacts['internal'][$iKey] ?? null) === 'frequent') : '' !!}</td>
                <td>
                    {{ $eKey ? $external[$eKey] : '' }}
                    @if($eKey === 'others' && !empty($contacts['external_others_specify']))
                        <span style="font-style:italic;">{{ $contacts['external_others_specify'] }}</span>
                    @endif
                </td>
                <td class="center">{!! $eKey ? $tick(($contacts['external'][$eKey] ?? null) === 'occasional') : '' !!}</td>
                <td class="center">{!! $eKey ? $tick(($contacts['external'][$eKey] ?? null) === 'frequent') : '' !!}</td>
            </tr>
        @endfor

        {{-- 18 --}}
        <tr>
            <td colspan="6" class="lbl-n">18.&nbsp; WORKING CONDITION</td>
        </tr>
        <tr>
            <td colspan="3">
                <span class="opt"><span class="box">{!! $tick(!empty($conditions['office_work'])) !!}</span>Office Work</span>
            </td>
            <td colspan="3" rowspan="2">
                <span class="lbl-n">Other/s (Please Specify)</span>
                <div class="val">{{ $conditions['others'] ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <span class="opt"><span class="box">{!! $tick(!empty($conditions['field_work'])) !!}</span>Field Work</span>
            </td>
        </tr>

        {{-- 19 --}}
        <tr>
            <td colspan="6" class="lbl-n">19.&nbsp; BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE UNIT OR SECTION</td>
        </tr>
        <tr><td colspan="6" class="val-lg">{!! nl2br(e($description->unit_general_function)) !!}</td></tr>

        {{-- 20 --}}
        <tr>
            <td colspan="6" class="lbl-n">20.&nbsp; BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE POSITION <span class="note">(Job Summary)</span></td>
        </tr>
        <tr><td colspan="6" class="val-lg" style="min-height:70pt;">{!! nl2br(e($description->position_general_function)) !!}</td></tr>

        {{-- 21 --}}
        <tr>
            <td colspan="6" class="lbl-n band">21.&nbsp; QUALIFICATION STANDARDS</td>
        </tr>
        <tr class="band">
            <td class="lbl">21a.&nbsp; Education</td>
            <td colspan="2" class="lbl">21b.&nbsp; Experience</td>
            <td class="lbl">21c.&nbsp; Training</td>
            <td colspan="2" class="lbl">21d.&nbsp; Eligibility</td>
        </tr>
        <tr>
            <td class="val-lg">{!! nl2br(e($description->qs_education)) !!}</td>
            <td colspan="2" class="val-lg">{!! nl2br(e($description->qs_experience)) !!}</td>
            <td class="val-lg">{!! nl2br(e($description->qs_training)) !!}</td>
            <td colspan="2" class="val-lg">{!! nl2br(e($description->qs_eligibility)) !!}</td>
        </tr>

        {{-- 21e --}}
        <tr class="band">
            <td colspan="4" class="lbl">21e.&nbsp; Core Competencies</td>
            <td colspan="2" class="lbl center">Competency Level</td>
        </tr>
        @forelse($core as $row)
            <tr>
                <td colspan="4" class="val">{{ $row['name'] ?? '' }}</td>
                <td colspan="2" class="val center">{{ $row['level'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="val">&nbsp;</td><td colspan="2" class="val">&nbsp;</td></tr>
        @endforelse

        {{-- 21f --}}
        <tr class="band">
            <td colspan="4" class="lbl">21f.&nbsp; Leadership Competencies</td>
            <td colspan="2" class="lbl center">Competency Level</td>
        </tr>
        @forelse($leadership as $row)
            <tr>
                <td colspan="4" class="val">{{ $row['name'] ?? '' }}</td>
                <td colspan="2" class="val center">{{ $row['level'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="val">&nbsp;</td><td colspan="2" class="val">&nbsp;</td></tr>
        @endforelse

        {{-- 22 --}}
        <tr class="band">
            <td colspan="4" class="lbl">22.&nbsp; STATEMENT OF DUTIES AND RESPONSIBILITIES <span class="note">(Technical Competencies)</span></td>
            <td colspan="2" class="lbl center">Competency Level</td>
        </tr>
        <tr class="band">
            <td class="lbl center">Percentage of<br>Working Time</td>
            <td colspan="3" class="lbl"><span class="note">(State the duties and responsibilities here:)</span></td>
            <td colspan="2"></td>
        </tr>
        @forelse($description->duties as $duty)
            <tr>
                <td class="val center">{{ rtrim(rtrim(number_format($duty->percentage, 2), '0'), '.') }}%</td>
                <td colspan="3" class="val">{!! nl2br(e($duty->duty)) !!}</td>
                <td colspan="2" class="val center">{{ $duty->competency_level }}</td>
            </tr>
        @empty
            <tr><td class="val">&nbsp;</td><td colspan="3" class="val">&nbsp;</td><td colspan="2" class="val">&nbsp;</td></tr>
        @endforelse
        <tr>
            <td class="val center" style="font-weight:700;">{{ rtrim(rtrim(number_format($description->dutiesPercentageTotal(), 2), '0'), '.') }}%</td>
            <td colspan="3" class="lbl">TOTAL</td>
            <td colspan="2"></td>
        </tr>

        {{-- 23 --}}
        <tr>
            <td colspan="6" class="lbl-n">23.&nbsp; ACKNOWLEDGMENT AND ACCEPTANCE:</td>
        </tr>
        <tr>
            <td colspan="6" style="font-size:8pt;">
                I have received a copy of this position description. It has been discussed with me and I have
                freely chosen to comply with the performance and behavior/conduct expectations contained herein.
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding-bottom:2pt;">
                <div class="sig-line"></div>
                <div class="sig-cap">Employee's Name, Date and Signature</div>
            </td>
            <td colspan="3" style="padding-bottom:2pt;">
                <div class="sig-line"></div>
                <div class="sig-cap">Supervisor's Name, Designation, Date and Signature</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
