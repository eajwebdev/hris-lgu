<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ETE EVALUATION - {{ $application->app_number }}</title>
    <style>
        @page { margin: 28px 54px 28px; size: legal portrait; }
        * { box-sizing: border-box; }
        body { color:#111; font-weight: 450; font-family:Arial, DejaVu Sans, sans-serif; font-size:12px; line-height:1.12; margin:0; }
        .page { min-height:944px; page-break-after:always; position:relative; }
        .page:last-child { page-break-after:auto; }
        .header-table { border-collapse:collapse; margin:0 auto 8px; width:78%; }
        .header-table td { border:0; padding:0; vertical-align:middle; }
        .seal { height:52px; width:52px; }
        .bagong { height:70px !important; width:70px !important; }
        .agency { font-size:10px; line-height:1.05; text-align:center; white-space:nowrap; }
        .agency strong { font-size:11px; }
        .agency-inner { display:inline-block; white-space:nowrap; }
        .agency-logos { display:inline-block; font-size:0; vertical-align:middle; white-space:nowrap; }
        .agency-logos img { margin-right:3px; vertical-align:middle; }
        .agency-text { display:inline-block; margin-left:6px; text-align:left; vertical-align:middle; white-space:nowrap; }
        .form-title { font-size:11px; font-weight:bold; margin:10px 0 0; text-align:center; }
        .subtitle { font-size:10px; margin:1px 0 17px; text-align:center; }
        .line-table { border-collapse:collapse; width:100%; }
        .line-table td { border:0; font-size:9px; padding:1px 0; vertical-align:bottom; }
        .fill-line { border-bottom:1px solid #111 !important; padding:0 4px 1px !important; }
        .double-rule { border-top:3px double #111; margin:12px 0 18px; }
        .section-title { font-size:9px; margin:0 0 1px; }
        .section-note { font-size:8px; margin-bottom:10px; }
        .requirements { border-collapse:collapse; margin:0 auto 11px; width:90%; }
        .requirements td { border:0; font-size:9px; padding:2px 3px; vertical-align:middle; }
        .requirement-name { font-weight:bold; width:19%; }
        .credits-title { font-size:9px; margin:0 0 8px; }
        .numbered-section { margin:0 0 8px 18px; }
        .numbered-heading { font-size:9px; margin-bottom:3px; }
        .credit-list { border-collapse:collapse; margin-left:31px; width:70%; }
        .credit-list td { border:0; font-size:8.5px; padding:1px 2px; }
        .credit-list .letter { width:18px; }
        .credit-list .leader { white-space:nowrap; width:auto; }
        .credit-list .leader-dots { color:#555; letter-spacing:.6px; }
        .credit-list .credit { font-weight:normal; text-align:right; width:28px; }
        .experience-title { font-size:9px; margin:4px 0 2px 18px; }
        .experience-table { border-collapse:collapse; table-layout:fixed; width:100%; }
        .experience-table th, .experience-table td { border:1px solid #555; font-size:{{ count($years) > 18 ? '6.5px' : '7.5px' }}; line-height:1; padding:{{ count($years) > 18 ? '1px' : '2px' }} 3px; text-align:center; }
        .experience-table th { font-weight:bold; }
        .experience-table .number-row th { font-size:8px; height:14px; }
        .experience-table .total-label { font-size:8px; font-weight:bold; text-align:center; }
        .summary { border-collapse:collapse; margin-top:8px; width:100%; }
        .summary td { border:0; font-size:8.5px; padding:1px 0; vertical-align:bottom; }
        .summary .summary-label { width:69%; }
        .summary .value-label { width:15%; }
        .summary .value { border-bottom:1px solid #111; font-weight:bold; text-align:center; width:16%; }
        .total-rating-label { font-size:10px !important; font-weight:bold; padding-top:6px !important; text-align:right; }
        .total-rating { border-bottom:3px double #111 !important; font-size:11px !important; font-weight:bold; text-align:center; }
        .remarks { font-size:8px; margin-top:4px; }
        .rated-block { margin-top:12px; }
        .rated-table { border-collapse:collapse; width:100%; }
        .rated-table td { border:0; font-size:8.5px; padding:0; vertical-align:bottom; }
        .rated-label { width:13%; }
        .signature-cell { text-align:center; width:50%; }
        .signature { display:block; height:24px; margin:0 auto -2px; max-width:135px; object-fit:contain; }
        .signature-space { height:24px; }
        .signature-line { border-top:1px solid #111; padding-top:2px; }
        .evaluator-name { font-weight:bold; }
        .footer { bottom:0; font-size:7px; left:0; position:absolute; text-align:center; width:100%; }
        .footer span { display:inline-block; margin:0 10px; }
    </style>
</head>
<body>
@php
    $educationItems = [
        'additional_four_year_course' => ['Additional 4-year course completed', 2, 'a.', 111],
        'masteral_1_18' => ['1 - 18 masteral units', 1, 'b.', 130],
        'masteral_19_30' => ['19 - 30 masteral units', 2, 'c.', 128],
        'masters_degree' => ["Master's degree completed", 4, 'd.', 122],
        'doctoral_1_18' => ['1 - 18 doctoral units', 5, 'e.', 131],
        'doctoral_19_36' => ['19 - 36 doctoral units', 6, 'f.', 130],
        'doctoral_degree' => ['Doctoral degree completed', 10, 'g.', 122],
    ];
    $trainingItems = [
        ['a.', 'Relevant study or scholarship grant', 3, 111],
        ['b.', 'Any comparable leadership seminar', 2, 111],
        ['c.', 'For every 50 hours consisting of 1 or more relevant in-service training', 1, 67],
    ];
    $applicantName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name);
    $sealData = 'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('template/img/ete-cpsu-seal.jpeg')));
    $bagongData = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('template/img/bagong-pilipinas.png')));
    $pdfCheckbox = function ($checked) {
        return '<span style="display:inline-block;white-space:nowrap;margin-left:5px;">'
            .'<input type="checkbox" '.($checked ? 'checked' : '').' style="display:none; margin-left: -50px !important;">'
            .'<span style="display:inline-block;width:10px;height:10px;line-height:9px;border:1px solid #111;text-align:center;font-size:8px;font-weight:bold;vertical-align:middle;margin-right:4px;">'
            .($checked ? '&#10003;' : '&nbsp;').'</span></span>';
    };
@endphp

@foreach($reportEvaluators as $reportEvaluator)
    @php
        $educationRatings = $rating->education_ratings ?? [];
        $trainingRatings = $rating->training_ratings ?? [];
        $experienceRatings = $rating->experience_year_ratings ?? [];
        $evaluator = $reportEvaluator->employee;
        $evaluatorName = trim(
            ($evaluator && $evaluator->prefix ? $evaluator->prefix.' ' : '').
            ($evaluator->fname ?? '').' '.($evaluator->mname ?? '').' '.
            ($evaluator->lname ?? '').' '.($evaluator->suffix ?? '')
        );
    @endphp
    <div class="page">
        <table class="header-table">
            <tr>
                <td class="agency">
                    <div class="agency-inner">
                        <span class="agency-logos"><img class="seal" src="{{ $sealData }}"><img class="bagong" src="{{ $bagongData }}" width="70" height="70" style="width:60px !important;height:60px !important;"></span>
                        <span class="agency-text">Republic of the Philippines<br><strong>CENTRAL PHILIPPINES STATE UNIVERSITY</strong><br>Kabankalan City, Negros Occidental 6111</span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="form-title">EVALUATION OF EDUCATION, TRAINING, AND EXPERIENCE</div>
        <div class="subtitle">(To be accomplished by the HRMPSB Committee)</div>

        <table class="line-table">
            <tr>
                <td width="60%">
                    Name:
                    <span style="display:inline-block; width:90%; border-bottom:1px solid #000; vertical-align:bottom; line-height:12px; padding-bottom:0;">
                        {{ $applicantName }}
                    </span>
                </td>
                <td width="35%">
                    Date:
                    <span style="display:inline-block; width:89.4%; border-bottom:1px solid #000; vertical-align:bottom; line-height:12px; padding-bottom:0;">
                        {{ optional($rating->evaluation_date ?? $ete->evaluation_date)->format('m/d/Y') }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="100%" colspan="2">
                    Considered for the Position of:
                    <span style="display:inline-block; width:80.3%; border-bottom:1px solid #000; vertical-align:bottom; line-height:12px; padding-bottom:0;">
                        {{ $ete->job->title ?? $application->position }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="100%" colspan="2">
                    Present Position:
                    <span style="display:inline-block; width:88.9%; border-bottom:1px solid #000; vertical-align:bottom; line-height:12px; padding-bottom:0;">
                        {{ $rating->present_position ?: 'N/A' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="100%" colspan="2">
                    Office/Department:
                    <span style="display:inline-block; width:75.2%; border-bottom:1px solid #000; vertical-align:bottom; line-height:12px; padding-bottom:0;">
                        {{ $rating->college_department ?: 'N/A' }}
                    </span>
                </td>
            </tr>
        </table>
        <div class="double-rule"></div>

        <div class="section-title">Minimum requirements 70%</div>
        <div class="section-note">(Refer to attached Q.S. for the position)</div>
        <table class="requirements">
            <tr>
                <td width="4%">1.</td>
                <td width="46%"><span style="width:80px; display:inline-block;">EDUCATION:</span>{!! $pdfCheckbox($rating->education_met === true) !!}Met &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {!! $pdfCheckbox($rating->education_met === false) !!} Not met</td>
                <td width="4%">3.</td>
                <td width="46%"><span style="width:80px; display:inline-block;">ELIGIBILITY:</span>{!! $pdfCheckbox($rating->eligibility_met === true) !!}Met &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {!! $pdfCheckbox($rating->eligibility_met === false) !!} Not met</td>
            </tr>
            <tr>
                <td>2.</td>
                <td><span style="width:80px; display:inline-block;">EXPERIENCE:</span>{!! $pdfCheckbox($rating->experience_met === true) !!}Met &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {!! $pdfCheckbox($rating->experience_met === false) !!} Not met</td>
                <td>4.</td>
                <td><span style="width:80px; display:inline-block;">TRAINING:</span>{!! $pdfCheckbox($rating->training_met === true) !!}Met &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {!! $pdfCheckbox($rating->training_met === false) !!} Not met</td>
            </tr>
        </table>

        <div class="credits-title">Additional credits for EDUCATION AND TRAINING in excess of the minimum requirements (30%)</div>
        <div class="numbered-section">
            <div class="numbered-heading">1.&nbsp;&nbsp;&nbsp; Education (total not to exceed 10)</div>
            <table class="credit-list">
                @foreach($educationItems as $key => [$label, $credit, $letter, $dotCount])
                    <tr><td class="letter">{{ $letter }}</td><td class="leader">{{ $label }} <span class="leader-dots">{{ str_repeat('.', $dotCount) }}</span></td><td class="credit">{{ $credit }}</td></tr>
                @endforeach
            </table>
        </div>

        <div class="numbered-section">
            <div class="numbered-heading">2.&nbsp;&nbsp;&nbsp; Training (total not to exceed 5)</div>
            <table class="credit-list">
                @foreach($trainingItems as [$letter, $label, $credit, $dotCount])
                    <tr><td class="letter">{{ $letter }}</td><td class="leader">{{ $label }} <span class="leader-dots">{{ str_repeat('.', $dotCount) }}</span></td><td class="credit">{{ $credit }}</td></tr>
                @endforeach
            </table>
        </div>

        <div class="experience-title">3.&nbsp;&nbsp;&nbsp; Experience (total not to exceed 15)</div>
        <table class="experience-table">
            <tr class="number-row"><th>1</th><th>2</th><th>3</th><th>4</th></tr>
            <tr><th>Year</th><th>Length of experience</th><th>Level of Experience</th><th>Credit</th></tr>
            @foreach($years as $year)
                @php $months = $experienceRatings[$year]['length'] ?? ''; @endphp
                <tr>
                    <td>{{ $year }}</td>
                    <td>{{ $months !== '' ? rtrim(rtrim(number_format((float)$months, 2, '.', ''), '0'), '.').' / 12' : '/ 12' }}</td>
                    <td>{{ $experienceRatings[$year]['level'] ?? '' }}</td>
                    <td>{{ isset($experienceRatings[$year]['credit']) ? number_format((float)$experienceRatings[$year]['credit'], 2) : '' }}</td>
                </tr>
            @endforeach
            <tr><td colspan="3" class="total-label">TOTAL</td><td><strong>{{ number_format($rating->experience_score, 2) }}</strong></td></tr>
        </table>

        <table class="summary">
            <tr><td class="summary-label">Qualification over and above the minimum requirements</td><td class="value-label">Experience:</td><td class="value">{{ number_format($rating->experience_score, 2) }}</td></tr>
            <tr><td></td><td class="value-label">Education:</td><td class="value">{{ number_format($rating->education_score, 2) }}</td></tr>
            <tr><td></td><td class="value-label">Training:</td><td class="value">{{ number_format($rating->training_score, 2) }}</td></tr>
            <tr><td class="summary-label">Minimum admission requirements points earned</td><td></td><td class="value">{{ number_format($rating->minimum_requirement_score, 2) }}</td></tr>
            <tr><td colspan="2" class="total-rating-label">TOTAL RATING&nbsp;&nbsp;&nbsp;</td><td class="total-rating">{{ number_format($rating->total_score, 2) }}</td></tr>
        </table>

        @if($rating->remarks)<div class="remarks"><strong>Remarks:</strong> {{ $rating->remarks }}</div>@endif

        <div class="rated-block">
            <table class="rated-table">
                <tr>
                    <td class="rated-label">Rated by:</td>
                    <td class="signature-cell">
                        @if($reportEvaluator->signature_data)<img class="signature" src="{{ $reportEvaluator->signature_data }}">@else<div class="signature-space"></div>@endif
                        <div class="signature-line"><span class="evaluator-name">{{ $evaluatorName }}</span><br>HRMPSB's Signature over Printed Name</div>
                    </td>
                    <td></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <span>Doc Control Code: CPSU-F-HRMO-06-REV01</span>
            <span>Effective Date: 09/12/2018</span>
            <span>Page No: 1/1</span>
        </div>
    </div>
@endforeach
</body>
</html>
