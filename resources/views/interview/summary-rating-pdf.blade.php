<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Summary Rating of Applicants</title>
    <style>
        @page {
            size: 8.5in 13in portrait;
            margin: 0.45in 0.5in;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #111;
            font-family: Arial, DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
        }

        .page {
            min-height: 860px;
            padding-bottom: 26px;
            position: relative;
        }

        .header-table {
            border-collapse: collapse;
            margin: 0 auto 8px;
            table-layout: auto;
            width: 94%;
        }

        .header-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .seal {
            height: 52px;
            width: 52px;
        }

        .bagong {
            height: 60px !important;
            width: 60px !important;
        }

        .agency {
            font-size: 10px;
            line-height: 1.05;
            text-align: center;
            white-space: nowrap;
        }

        .agency strong {
            font-size: 11px;
        }

        .agency-inner {
            display: inline-block;
            white-space: nowrap;
        }

        .agency-logos {
            display: inline-block;
            font-size: 0;
            vertical-align: middle;
            white-space: nowrap;
        }

        .agency-logos img {
            margin-right: 3px;
            vertical-align: middle;
        }

        .agency-text {
            display: inline-block;
            margin-left: 6px;
            text-align: left;
            vertical-align: middle;
            white-space: nowrap;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0 14px;
            text-align: center;
            text-transform: uppercase;
        }

        .meta {
            margin: 0 auto 14px;
            width: 100%;
            margin-left: 0;
        }

        .meta-row {
            line-height: 1.5;
            margin-bottom: 2px;
            white-space: nowrap;
        }

        .label {
            display: inline-block;
            font-weight: bold;
            width: 155px;
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #111;
            padding: 6px 5px;
            vertical-align: middle;
        }

        th {
            background: #d9d9d9;
            font-weight: bold;
            text-align: center;
        }

        .name-col { width: 33%; }
        .score-col { width: 15%; }
        .final-col { width: 13%; }
        .rank-col { width: 7%; }
        .remarks-col { width: 11%; }

        .center {
            text-align: center;
        }

        .name {
            font-weight: bold;
        }

        .nothing td {
            font-style: italic;
            text-align: center;
        }

        .prepared {
            font-size: 10px;
            margin-top: 20px;
            text-align: center;
        }

        .prepared-by {
            margin-bottom: 24px;
        }

        .prepared-by strong {
            border-bottom: 1px solid #111;
            font-size: 10px;
        }

        .sign-name {
            font-weight: bold;
            line-height: 1;
            text-transform: uppercase;
        }

        .sign-role {
            font-size: 9px;
            line-height: 1.05;
        }

        .board-title {
            font-weight: bold;
            margin: 18px 0 28px;
            text-transform: uppercase;
        }

        .member-table {
            border-collapse: collapse;
            margin: 0 auto;
            table-layout: fixed;
            width: 88%;
        }

        .member-table td {
            border: 0;
            height: 70px;
            padding: 0 10px;
            text-align: center;
            vertical-align: top;
        }

        .chair-block {
            margin: 18px 0 38px;
        }

        .approved {
            margin-left: 21%;
            text-align: left;
        }

        .approved-sign {
            margin-top: 18px;
            text-align: center;
        }

        .footer {
            bottom: 0;
            left: 0;
            position: absolute;
            text-align: center;
            width: 100%;
        }

        .footer img {
            display: block;
            width: 100%;
        }
    </style>
</head>
<body>
@php
    $sealData = 'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('template/img/ete-cpsu-seal.jpeg')));
    $bagongData = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('template/img/bagong-pilipinas.png')));
    $footerData = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('template/img/report-footer.png')));
@endphp
<div class="page">
    <table class="header-table">
        <tr>
            <td class="agency">
                <div class="agency-inner">
                    <span class="agency-logos">
                        <img class="seal" src="{{ $sealData }}">
                        <img class="bagong" src="{{ $bagongData }}" width="60" height="60">
                    </span>
                    <span class="agency-text">
                        Republic of the Philippines<br>
                        <strong>CENTRAL PHILIPPINES STATE UNIVERSITY</strong><br>
                        Kabankalan City, Negros Occidental 6111
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <div class="title">Summary Rating of Applicants</div>

    <div class="meta">
        <div class="meta-row">
            <span class="label">Position:</span>
            <strong>{{ strtoupper($interview->job->title ?? 'N/A') }}</strong>
        </div>
        <div class="meta-row">
            <span class="label">Salary Grade:</span>
            {{ $interview->job && $interview->job->salary_grade ? $interview->job->salary_grade : '' }}
        </div>
        <div class="meta-row">
            <span class="label">Office Assignment:</span>
            <strong>{{ strtoupper($interview->eteEvaluation->office->office_name ?? $interview->job->assignment ?? 'N/A') }}</strong>
        </div>
        <div class="meta-row">
            <span class="label">Date of Screening:</span>
            {{ $interview->interview_date ? $interview->interview_date->format('F j, Y') : now()->format('F j, Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="name-col">Name</th>
                <th class="score-col">Qualifications</th>
                <th class="score-col">Potential</th>
                <th class="score-col">Interview</th>
                <th rowspan="2" class="final-col">FINAL RESULTS</th>
                <th rowspan="2" class="rank-col">Rank</th>
                <th rowspan="2" class="remarks-col">Remarks</th>
            </tr>
            <tr>
                <th>(50%)</th>
                <th>(25%)</th>
                <th>(25%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="name">{{ strtoupper($row['name']) }}</td>
                    <td class="center">{{ $row['qualification_score'] }}</td>
                    <td class="center">{{ $row['weighted_potential_score'] }}</td>
                    <td class="center">{{ $row['weighted_interview_score'] }}</td>
                    <td class="center">{{ $row['final_score'] }}</td>
                    <td class="center">{{ $row['rank'] }}</td>
                    <td>{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center">No interview ratings are available.</td>
                </tr>
            @endforelse

        </tbody>
    </table>

    <div class="prepared">
        <div class="prepared-by">Prepared by: <strong>SECRETARIAT</strong></div>

        <div>
            <div class="sign-name" style="margin-left: 34% !important;">WENDI AHMOR O. ELENTORIO</div>
            <div class="sign-role" style="margin-left: 34% !important;">Administrative Officer II (HRMO I) /<br>HRMPSB Secretariat</div>
        </div>

        <div class="board-title">Human Resource Merit, Promotion and Selection Board (HRMPSB)</div>

        <table class="member-table">
            @foreach($panelists->chunk(3) as $chunk)
                <tr>
                    @foreach($chunk as $panelist)
                        <td>
                            <div class="sign-name">{{ strtoupper($panelist['name']) }}</div>
                            <div class="sign-role">{{ $panelist['position'] }}<br>HRMPSB Member</div>
                        </td>
                    @endforeach
                    @for($i = $chunk->count(); $i < 3; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>

        <div class="chair-block">
            @if($chairman)
                <div class="sign-name">{{ strtoupper($chairman['name']) }}</div>
                <div class="sign-role">{{ $chairman['position'] }}<br>HRMPSB Chairman</div>
            @else
                <div class="sign-name">ENGR. MARC ALEXEI CAESAR B. BADAJOS, PhD</div>
                <div class="sign-role">Vice President for Administration and Finance /<br>HRMPSB Chairman</div>
            @endif
        </div>

        <div class="approved">APPROVED:</div>
        <div class="approved-sign">
            <div class="sign-name">ALADINO C. MORACA, Ph. D.</div>
            <div class="sign-role">Mayor</div>
        </div>
    </div>

    <div class="footer">
        <img src="{{ $footerData }}" alt="Report footer">
    </div>
</div>
</body>
</html>
