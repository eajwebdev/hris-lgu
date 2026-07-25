<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COS / JO Performance Rating Form - {{ $employee->fname }} {{ $employee->lname }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            color: #000000;
            background-color: #f8fafc;
            padding: 20px;
        }
        .paper-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }
        .form-header-title {
            font-size: 16px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            line-height: 1.4;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .meta-table td {
            padding: 4px 0;
            vertical-align: bottom;
        }
        .meta-label {
            font-weight: bold;
            width: 160px;
        }
        .meta-value-line {
            border-bottom: 1px solid #000000;
            font-weight: bold;
            text-transform: uppercase;
            padding-left: 5px;
        }
        .rating-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12.5px;
        }
        .rating-table th, .rating-table td {
            border: 1px solid #000000;
            padding: 6px 10px;
        }
        .rating-table th {
            background-color: #e2e8f0 !important;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        .section-header-row {
            font-weight: bold;
            color: #b91c1c;
            text-transform: uppercase;
            background-color: #fef2f2 !important;
        }
        .summary-box {
            font-size: 13px;
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .scale-list {
            font-size: 12px;
            list-style: none;
            padding-left: 20px;
            margin-bottom: 0;
        }
        .scale-list li {
            margin-bottom: 2px;
        }
        .signature-section {
            margin-top: 40px;
            font-size: 12px;
        }
        .signature-line {
            border-bottom: 1px solid #000000;
            width: 85%;
            margin: 40px auto 5px auto;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .paper-container {
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
            .rating-table th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .section-header-row {
                background-color: #fff5f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Bar (Print / Close) --}}
    @if(!request('embed'))
        <div class="paper-container no-print mb-3 bg-light p-3 border rounded d-flex justify-content-between align-items-center">
            <div>
                <span class="font-weight-bold text-dark"><i class="fas fa-file-alt text-teal mr-2"></i> Performance Rating Form Preview</span>
                <small class="text-muted d-block">Official COS / JO Rating Document</small>
            </div>
            <div>
                <button type="button" onclick="window.print()" class="btn btn-teal btn-sm font-weight-bold shadow-sm px-3 mr-2">
                    <i class="fas fa-print mr-1"></i> Print / Save PDF
                </button>
                <button type="button" onclick="window.close()" class="btn btn-outline-secondary btn-sm font-weight-bold px-3">
                    <i class="fas fa-times mr-1"></i> Close
                </button>
            </div>
        </div>
    @endif

    {{-- Printable Document Paper Container --}}
    <div class="paper-container">
        {{-- Header Title --}}
        <div class="form-header-title">
            {{ strtoupper($office->office_name ?? 'GENERAL SERVICES OFFICE') }} CONTRACT OF SERVICE PERSONNEL<br>
            PERFORMANCE RATING FORM
        </div>

        {{-- Employee & Period Metadata --}}
        @php
            $periodStr = ($semester == 1) ? "January 1 - June 30, {$year}" : "July 1 - December 31, {$year}";
            $resolvedSupervisor = $ipcr->assessed_by_name ?? ($officeHead ? ($officeHead->fname . ' ' . $officeHead->lname) : 'LUCRECIA C. NICOLAS');
        @endphp
        <table class="meta-table">
            <tr>
                <td class="meta-label">Employee Name:</td>
                <td class="meta-value-line">{{ $employee->fname }} {{ $employee->mname ? $employee->mname[0].'.' : '' }} {{ $employee->lname }}</td>
            </tr>
            <tr>
                <td class="meta-label">Division/Office:</td>
                <td class="meta-value-line">{{ $office->office_name ?? 'LGU MABINAY' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Period Covered:</td>
                <td class="meta-value-line">{{ $periodStr }}</td>
            </tr>
            <tr>
                <td class="meta-label">Immediate Supervisor:</td>
                <td class="meta-value-line">{{ $resolvedSupervisor }}</td>
            </tr>
            <tr>
                <td class="meta-label">Date of Evaluation:</td>
                <td class="meta-value-line">{{ date('F d, Y') }}</td>
            </tr>
        </table>

        {{-- Rating Table --}}
        <table class="rating-table">
            <thead>
                <tr>
                    <th style="width: 78%">TASK DESCRIPTIONS</th>
                    <th style="width: 22%">RATING</th>
                </tr>
            </thead>
            <tbody>
                {{-- CORE FUNCTIONS / TASK DESCRIPTIONS --}}
                @php
                    $coreItems = $ipcr->items->where('category', 'Core Functions');
                @endphp

                @if($coreItems->count() > 0)
                    @foreach($coreItems as $item)
                        @php $rVal = $item->rating_ave ?? $item->rating_average; @endphp
                        <tr>
                            <td>{!! nl2br(e($item->mfo_pap ?: $item->success_indicators)) !!}</td>
                            <td class="text-center font-weight-bold">
                                {{ $rVal ? number_format($rVal, 2) : '' }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    {{-- Default General Services Tasks if empty --}}
                    <tr>
                        <td>Maintains the cleanliness and orderliness of the hallways and assigned office premises</td>
                        <td class="text-center"></td>
                    </tr>
                    <tr>
                        <td>Gathers garbage from the landscaped areas of the Government Center / Office premises</td>
                        <td class="text-center"></td>
                    </tr>
                    <tr>
                        <td>Segregates and dispose garbage properly</td>
                        <td class="text-center"></td>
                    </tr>
                    <tr>
                        <td>Performs other tasks as directed by the immediate supervisor</td>
                        <td class="text-center"></td>
                    </tr>
                @endif

                {{-- SUPPORT FUNCTIONS --}}
                <tr class="section-header-row">
                    <td colspan="2">SUPPORT FUNCTIONS</td>
                </tr>
                @php
                    $supportItems = $ipcr->items->where('category', 'Support Functions');
                @endphp
                @if($supportItems->count() > 0)
                    @foreach($supportItems as $item)
                        @php $rVal = $item->rating_ave ?? $item->rating_average; @endphp
                        <tr>
                            <td>{!! nl2br(e($item->mfo_pap ?: $item->success_indicators)) !!}</td>
                            <td class="text-center font-weight-bold">
                                {{ $rVal ? number_format($rVal, 2) : '' }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr><td>Does other tasks as assigned by the Department head</td><td class="text-center"></td></tr>
                    <tr><td>Attends Flag Raising Ceremony</td><td class="text-center"></td></tr>
                    <tr><td>Participates in capacity enhancement activities</td><td class="text-center"></td></tr>
                    <tr><td>Participates in activities sanctioned by the LCE</td><td class="text-center"></td></tr>
                @endif

                {{-- WORK ETHICS --}}
                <tr class="section-header-row">
                    <td colspan="2">WORK ETHICS</td>
                </tr>
                @php
                    $workEthics = [
                        'Punctuality and attendance',
                        'Responsibility',
                        'Integrity',
                        'Teamwork',
                        'Professionalism',
                        'Time Management',
                        'Continuous improvement',
                        'Respect',
                        'Accountability',
                        'Adaptability',
                        'Customer service skills',
                    ];
                @endphp
                @foreach($workEthics as $ethic)
                    <tr>
                        <td>{{ $ethic }}</td>
                        <td class="text-center font-weight-bold"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- SUMMARY OF RATINGS & PERFORMANCE RATING SCALE --}}
        @php
            $ratedItems = $ipcr->items->filter(fn($i) => !is_null($i->rating_ave ?? $i->rating_average));
            $overallAve = $ratedItems->count() > 0 ? round($ratedItems->avg(fn($i) => $i->rating_ave ?? $i->rating_average), 2) : null;
        @endphp
        <div class="summary-box">
            <p class="font-weight-bold mb-1">SUMMARY OF RATINGS</p>
            <p class="mb-1 ml-3">Total Score: <u>{{ $ratedItems->count() > 0 ? number_format($ratedItems->sum(fn($i) => $i->rating_ave ?? $i->rating_average), 2) : '__________' }}</u></p>
            <p class="mb-3 ml-3">Average Score: <u>{{ $overallAve ? number_format($overallAve, 2) : '__________' }}</u></p>

            <p class="font-weight-bold mb-1">Performance Rating Scale:</p>
            <ul class="scale-list">
                <li>&bull; 4.50 &ndash; 5.00 &ndash; Outstanding</li>
                <li>&bull; 3.50 &ndash; 4.49 &ndash; Very Satisfactory</li>
                <li>&bull; 2.50 &ndash; 3.49 &ndash; Satisfactory</li>
                <li>&bull; 1.50 &ndash; 2.49 &ndash; Needs Improvement</li>
            </ul>
        </div>

        {{-- SIGNATURE SECTION --}}
        <div class="signature-section">
            <div class="row">
                <div class="col-6 text-center">
                    <p class="font-weight-bold mb-4">Evaluated by / Immediate Supervisor:</p>
                    <div class="signature-line">{{ $resolvedSupervisor }}</div>
                    <small class="text-muted font-weight-bold d-block">Signature over Printed Name</small>
                </div>
                <div class="col-6 text-center">
                    <p class="font-weight-bold mb-4">Conforme / Ratee:</p>
                    <div class="signature-line">{{ $employee->fname }} {{ $employee->lname }}</div>
                    <small class="text-muted font-weight-bold d-block">Signature over Printed Name</small>
                </div>
            </div>
        </div>
    </div>

    @if(!request('embed'))
        <script>
            // Auto trigger window print on load if not embedded in modal iframe
            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        </script>
    @endif
</body>
</html>
