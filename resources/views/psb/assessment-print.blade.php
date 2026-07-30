@php
    /**
     * Personnel Selection Board — COMPARATIVE ASSESSMENT FORM.
     *
     * Laid out exactly as "PSB Forms.docx": a two-tier header where
     * PRELIMINARY EVALUATION spans eight columns (eligibility, the six weighted
     * components, and the 100-point total) and FURTHER ASSESSMENT spans the
     * written exam column, followed by overall points, rank and remarks.
     */
    $rows = $assessment->rows;
    $furtherLabel = $rows->firstWhere('further_assessment_label', '!=', null)?->further_assessment_label
        ?: 'WRITTEN EXAM/ SKILLS/ TEST/ ETC.';

    $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Comparative Assessment Form — {{ $assessment->position_to_be_filled }}</title>
<style>
    @page { size: 13in 8.5in; margin: 0.4in; }   /* legal, landscape */

    * { box-sizing: border-box; }

    body {
        font-family: "Arial", "Helvetica", sans-serif;
        font-size: 8pt;
        color: #000;
        margin: 0;
        background: #fff;
    }

    .masthead { text-align: center; line-height: 1.25; }
    .masthead .rp { font-size: 9pt; }
    .masthead .title {
        font-size: 13pt; font-weight: 700; letter-spacing: .05em;
        margin: 7pt 0 6pt;
    }

    .meta { width: 100%; border-collapse: collapse; margin-bottom: 5pt; font-size: 8.5pt; }
    .meta td { padding: 1.5pt 0; white-space: nowrap; }
    .meta .fill {
        display: inline-block; min-width: 120pt;
        border-bottom: 0.75pt solid #000; padding: 0 3pt; font-weight: 700;
    }
    .meta .fill-sm { min-width: 70pt; }

    table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.grid th, table.grid td {
        border: 0.75pt solid #000;
        padding: 2pt 2pt;
        vertical-align: middle;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    table.grid th {
        font-size: 6.5pt;
        font-weight: 700;
        text-align: center;
        line-height: 1.1;
        background: #ececec;
    }
    table.grid td { font-size: 8pt; height: 26pt; }
    .name  { text-align: left; }
    .c     { text-align: center; }
    .total { font-weight: 700; }

    .nothing { text-align: center; font-weight: 700; font-size: 8.5pt; padding: 8pt 0 2pt; letter-spacing: .04em; }

    .board-title { text-align: center; font-weight: 700; font-size: 9.5pt; margin: 10pt 0 14pt; letter-spacing: .03em; }
    table.board { width: 100%; border-collapse: collapse; }
    table.board td {
        width: 33.33%;
        text-align: center;
        padding: 0 6pt 20pt;
        vertical-align: bottom;
    }
    .sig-name { font-weight: 700; font-size: 9pt; text-transform: uppercase; }
    .sig-role { font-size: 8pt; }

    @media screen {
        body { background: #F7F8FA; padding: 18px; }
        .sheet { max-width: 13in; margin: 0 auto; background: #fff; padding: 0.4in;
                 box-shadow: 0 18px 40px -12px rgba(15,23,42,.18); border-radius: 4px; }
        .toolbar { max-width: 13in; margin: 0 auto 14px; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button, .toolbar a {
            font: 600 13px/1 "Inter", system-ui, sans-serif;
            padding: 10px 16px; border-radius: 8px; border: 1px solid #D5D9E0;
            background: #fff; color: #0F172A; cursor: pointer; text-decoration: none;
        }
        .toolbar .primary { background: #1E7A45; border-color: #1E7A45; color: #fff; }
    }
    @media print { .toolbar { display: none !important; } .sheet { padding: 0; } }
</style>
</head>
<body>

<div class="toolbar">
    <a href="{{ route('psbAssessment', $assessment->jid) }}">&larr; Back</a>
    <button class="primary" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="sheet">

    <div class="masthead">
        <div class="rp">Republic of the Philippines</div>
        <div class="rp">Province of Negros Oriental</div>
        <div class="rp"><b>Municipality of Mabinay</b></div>
        <div class="title">COMPARATIVE ASSESSMENT FORM</div>
    </div>

    <table class="meta">
        <tr>
            <td style="width:42%;">POSITION TO BE FILLED: <span class="fill">{{ $assessment->position_to_be_filled }}</span></td>
            <td style="width:29%;">ITEM NO.: <span class="fill fill-sm">{{ $assessment->item_no }}</span></td>
            <td style="width:29%;">LOCATION: <span class="fill fill-sm">{{ $assessment->location }}</span></td>
        </tr>
        <tr>
            <td>DATE POSTED: <span class="fill">{{ optional($assessment->date_posted)->format('F j, Y') }}</span></td>
            <td>DATE PUBLISHED: <span class="fill fill-sm">{{ optional($assessment->date_published)->format('F j, Y') }}</span></td>
            <td>RATE/mo.: <span class="fill fill-sm">{{ $assessment->rate_per_month }}</span></td>
        </tr>
    </table>

    <table class="grid">
        <colgroup>
            <col style="width:12.5%"><col style="width:10%">
            <col style="width:7%"><col style="width:6%"><col style="width:5.5%"><col style="width:5.5%">
            <col style="width:6%"><col style="width:5.5%"><col style="width:6.5%"><col style="width:6%">
            <col style="width:7%"><col style="width:6%"><col style="width:4.5%"><col style="width:12%">
        </colgroup>

        <thead>
            <tr>
                <th rowspan="2">NAME OF CANDIDATE</th>
                <th rowspan="2">PRESENT POSITION TITLE/ SG/ STATUS</th>
                <th colspan="8">PRELIMINARY&nbsp;&nbsp;&nbsp;EVALUATION</th>
                <th colspan="1">FURTHER<br>ASSESSMENT</th>
                <th rowspan="2">OVER<br>ALL<br>POINTS</th>
                <th rowspan="2">RANK</th>
                <th rowspan="2">REMARKS</th>
            </tr>
            <tr>
                <th>CIVIL SERVICE<br>ELIGIBILITY</th>
                <th>PERFORMANCE<br>RATING<br>({{ $weights['performance_rating'] }}%)</th>
                <th>EDUCATION<br>({{ $weights['education_points'] }}%)</th>
                <th>TRAINING<br>({{ $weights['training_points'] }}%)</th>
                <th>EXPERIENCE<br>({{ $weights['experience_points'] }}%)</th>
                <th>POTENTIAL<br>({{ $weights['potential_points'] }}%)</th>
                <th>PSYCHOSOCIAL<br>ATTRIBUTES<br>({{ $weights['psychosocial_points'] }}%)</th>
                <th>TOTAL<br>POINTS<br>(100%)</th>
                <th>{!! nl2br(e($furtherLabel)) !!}</th>
            </tr>
        </thead>

        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="name">{{ strtoupper($row->candidate_name) }}</td>
                    <td class="name">{{ $row->present_position_line }}</td>
                    <td class="c">{{ $row->civil_service_eligibility }}</td>
                    <td class="c">{{ $num($row->performance_rating) }}</td>
                    <td class="c">{{ $num($row->education_points) }}</td>
                    <td class="c">{{ $num($row->training_points) }}</td>
                    <td class="c">{{ $num($row->experience_points) }}</td>
                    <td class="c">{{ $num($row->potential_points) }}</td>
                    <td class="c">{{ $num($row->psychosocial_points) }}</td>
                    <td class="c total">{{ $num($row->preliminary_total) }}</td>
                    <td class="c">{{ $num($row->further_assessment) }}</td>
                    <td class="c total">{{ $num($row->overall_points) }}</td>
                    <td class="c total">{{ $row->rank }}</td>
                    <td class="name">{{ $row->remarks }}</td>
                </tr>
            @empty
                <tr><td colspan="14">&nbsp;</td></tr>
            @endforelse

            {{-- The form always shows a blank line before the closing note. --}}
            <tr><td colspan="14">&nbsp;</td></tr>
        </tbody>
    </table>

    <div class="nothing">** NOTHING FOLLOWS **</div>

    <div class="board-title">Personnel Selection Board</div>

    <table class="board">
        @foreach($board->chunk(3) as $chunk)
            <tr>
                @foreach($chunk as $member)
                    <td>
                        <div class="sig-name">{{ $member->printed_name }}</div>
                        <div class="sig-role">{{ $member->role }}</div>
                    </td>
                @endforeach
                @for($i = $chunk->count(); $i < 3; $i++)<td></td>@endfor
            </tr>
        @endforeach
    </table>

</div>
</body>
</html>
