@php
    /**
     * Personnel Selection Board — INTERVIEW FORM.
     *
     * Laid out as "PSB Forms.docx": one numbered row per applicant, the seven
     * traits with their weights in the column headings, and a TOTAL column.
     * The weights come from PsbScoring, which is the same source the rating
     * screen scores against, so the printed sheet and the system agree.
     */
    $weights = \App\Services\PsbScoring::INTERVIEW_WEIGHTS;
    $labels  = \App\Services\PsbScoring::INTERVIEW_LABELS;

    $num = fn ($v) => ($v === null || $v === '') ? '' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');

    // A printed form is also used blank, so pad out to a usable number of rows.
    $minRows = 10;
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Interview Form — {{ $position }}</title>
<style>
    @page { size: 13in 8.5in; margin: 0.5in; }   /* legal, landscape */

    * { box-sizing: border-box; }

    body {
        font-family: "Arial", "Helvetica", sans-serif;
        font-size: 9pt;
        color: #000;
        margin: 0;
        background: #fff;
    }

    .masthead { text-align: center; line-height: 1.3; }
    .masthead .rp { font-size: 9.5pt; }
    .masthead .title {
        font-size: 14pt; font-weight: 700; letter-spacing: .06em;
        margin: 9pt 0 8pt;
    }

    .position { font-size: 10pt; margin-bottom: 6pt; }
    .position .fill {
        display: inline-block; min-width: 320pt;
        border-bottom: 0.75pt solid #000; padding: 0 4pt; font-weight: 700;
    }

    table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.grid th, table.grid td {
        border: 0.75pt solid #000;
        padding: 3pt;
        vertical-align: middle;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    table.grid th {
        font-size: 7pt;
        font-weight: 700;
        text-align: center;
        line-height: 1.15;
        background: #ececec;
        height: 46pt;
    }
    table.grid td { height: 30pt; font-size: 9.5pt; }
    .idx   { text-align: center; width: 4%; }
    .name  { text-align: left; }
    .c     { text-align: center; }
    .total { font-weight: 700; }

    .footer { margin-top: 26pt; font-size: 9.5pt; }
    .footer .row1 { margin-bottom: 30pt; }
    .footer .fill {
        display: inline-block; min-width: 190pt;
        border-bottom: 0.75pt solid #000;
    }
    .sig { margin-left: 40pt; display: inline-block; text-align: center; }
    .sig .line { width: 230pt; border-bottom: 0.75pt solid #000; }
    .sig .cap  { font-size: 8.5pt; padding-top: 2pt; }

    @media screen {
        body { background: #F7F8FA; padding: 18px; }
        .sheet { max-width: 13in; margin: 0 auto; background: #fff; padding: 0.5in;
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
    @isset($backUrl)<a href="{{ $backUrl }}">&larr; Back</a>@endisset
    <button class="primary" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="sheet">

    <div class="masthead">
        <div class="rp">Republic of the Philippines</div>
        <div class="rp">Province of Negros Oriental</div>
        <div class="rp"><b>Municipality of Mabinay</b></div>
        <div class="title">INTERVIEW FORM</div>
    </div>

    <div class="position">Position: <span class="fill">{{ $position }}</span></div>

    <table class="grid">
        <colgroup>
            <col style="width:4%"><col style="width:22%">
            @foreach($weights as $key => $w)<col style="width:{{ round(64 / count($weights), 2) }}%">@endforeach
            <col style="width:10%">
        </colgroup>

        <thead>
            <tr>
                <th colspan="2">NAME OF APPLICANTS</th>
                @foreach($labels as $key => $label)
                    <th>{!! str_replace(' ', '<br>', $label) !!}<br>({{ $weights[$key] }}%)</th>
                @endforeach
                <th>TOTAL</th>
            </tr>
        </thead>

        <tbody>
            @foreach($candidates as $i => $candidate)
                <tr>
                    <td class="idx">{{ $i + 1 }}</td>
                    <td class="name">{{ strtoupper($candidate['name']) }}</td>
                    @foreach(array_keys($weights) as $key)
                        <td class="c">{{ $num($candidate['scores'][$key] ?? null) }}</td>
                    @endforeach
                    <td class="c total">{{ $num($candidate['total'] ?? null) }}</td>
                </tr>
            @endforeach

            {{-- Blank lines so the sheet is usable in the room as well as on file. --}}
            @for($i = count($candidates); $i < $minRows; $i++)
                <tr>
                    <td class="idx">{{ $i + 1 }}</td>
                    <td>&nbsp;</td>
                    @foreach(array_keys($weights) as $key)<td>&nbsp;</td>@endforeach
                    <td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="footer">
        <div class="row1">
            Interviewed by:
            <span style="margin-left:150pt;">Date: <span class="fill"></span></span>
        </div>
        <div class="sig">
            <div class="line"></div>
            <div class="cap">Signature over Printed Name</div>
        </div>
    </div>

</div>
</body>
</html>
