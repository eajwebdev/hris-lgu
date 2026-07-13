@php
    $formatValue = function ($value) {
        if (is_array($value)) {
            return collect($value)->filter()->implode(', ');
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return collect($decoded)->filter()->implode(', ');
            }
        }

        return trim((string) ($value ?? ''));
    };

    $nameOf = function ($app) {
        $middle = trim((string) ($app->middle_name ?? ''));
        $middleInitial = $middle !== '' ? strtoupper(substr($middle, 0, 1)) . '.' : '';

        return strtoupper(trim(collect([
            $app->last_name ?? '',
            trim(($app->first_name ?? '') . ' ' . $middleInitial),
        ])->filter()->implode(', ')));
    };

    $sexOf = function ($app) {
        return strtoupper(substr(trim((string) ($app->sex ?? '')), 0, 1));
    };

    $dqOf = function ($app) {
        return (int) ($app->status ?? 0) === 3 ? 'DQ' : '';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Applicants Report</title>
    <style>
        @page {
            size: 18in 8.5in;
            margin: 0.25in;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
        }

        .title {
            margin-bottom: 4px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .filters {
            margin-bottom: 6px;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #111;
            padding: 3px 4px;
            vertical-align: middle;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        th {
            text-align: center;
            font-weight: bold;
            white-space: nowrap;
            overflow-wrap: normal;
            word-wrap: normal;
        }

        .center {
            text-align: center;
        }

        .top {
            vertical-align: top;
        }

        .no-col { width: 2.5%; }
        .name-col { width: 11%; }
        .position-col { width: 6.5%; }
        .program-col { width: 6.5%; }
        .sex-col { width: 3%; }
        .civil-col { width: 4.5%; }
        .age-col { width: 3%; }
        .address-col { width: 11%; }
        .contact-col { width: 10.5%; }
        .education-col { width: 10.5%; }
        .dq-col { width: 2.5%; }
        .experience-col { width: 14%; }
        .training-col { width: 10%; }
        .eligibility-col { width: 7%; }

        .nothing-follows td {
            height: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="title">NON-TEACHING APPLICANTS</div>
    <div class="filters">
        <strong>Position:</strong> {{ $selectedPosition ? $selectedPosition->title : 'All Positions' }}
        &nbsp;&nbsp;
        <strong>Status:</strong> {{ $selectedStatus }}
        @if(!empty($selectedDateFrom) || !empty($selectedDateTo))
            &nbsp;&nbsp;
            <strong>Applied Date:</strong>
            {{ !empty($selectedDateFrom) ? \Carbon\Carbon::parse($selectedDateFrom)->format('M d, Y') : 'Start' }}
            -
            {{ !empty($selectedDateTo) ? \Carbon\Carbon::parse($selectedDateTo)->format('M d, Y') : 'End' }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="no-col">NO.</th>
                <th class="name-col">NAME</th>
                <th class="position-col">POSITION</th>
                <th class="program-col">PROGRAM</th>
                <th class="sex-col">SEX</th>
                <th class="civil-col">CIVIL<br>STATUS</th>
                <th class="age-col">AGE</th>
                <th class="address-col">ADDRESS</th>
                <th class="contact-col">CONTACT<br>DETAILS</th>
                <th class="education-col">Education</th>
                <th class="dq-col">DQ</th>
                <th class="experience-col">EXPERIENCE</th>
                <th class="dq-col">DQ</th>
                <th class="training-col">TRAINING</th>
                <th class="dq-col">DQ</th>
                <th class="eligibility-col">ELIGIBILITY</th>
                <th class="dq-col">DQ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $index => $app)
                @php
                    $contact = collect([
                        $formatValue($app->mobile ?? ''),
                        $formatValue($app->email ?? ''),
                    ])->filter();

                    $education = $formatValue($app->education ?? $app->required_education ?? '');
                    $experience = $formatValue($app->experience ?? $app->required_experience ?? '');
                    $training = $formatValue($app->training ?? $app->required_training ?? '');
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td><strong>{{ $nameOf($app) }}</strong></td>
                    <td class="center">{{ $formatValue($app->position ?? '') }}</td>
                    <td class="center">{{ $formatValue($app->program ?? '') }}</td>
                    <td class="center">{{ $sexOf($app) }}</td>
                    <td class="center"></td>
                    <td class="center">{{ $formatValue($app->age ?? '') }}</td>
                    <td>{{ $formatValue($app->address ?? '') }}</td>
                    <td>
                        @foreach($contact as $contactDetail)
                            <div>{{ $contactDetail }}</div>
                        @endforeach
                    </td>
                    <td>{{ $education }}</td>
                    <td class="center">{{ $dqOf($app) }}</td>
                    <td class="top">{{ $experience }}</td>
                    <td class="center">{{ $dqOf($app) }}</td>
                    <td class="top">{{ $training }}</td>
                    <td class="center">{{ $dqOf($app) }}</td>
                    <td></td>
                    <td class="center">{{ $dqOf($app) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="center">No applicants found.</td>
                </tr>
            @endforelse

            <tr class="nothing-follows">
                <td></td>
                <td colspan="16">Nothing follows ***</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
