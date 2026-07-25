<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official OPCR Form (Landscape) - {{ $opcr->office->office_name }} ({{ $opcr->year }})</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @page {
            size: 13in 8.5in landscape;
            margin: 0.25in;
        }
        body {
            font-family: "Arial", "Calibri", sans-serif;
            color: #000000;
            background-color: #f1f5f9;
            padding: 10px;
            font-size: 10px;
        }
        .excel-container {
            max-width: 1260px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
        }
        .excel-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            color: #000000;
        }
        .excel-table th, .excel-table td {
            border: 1px solid #000000;
            padding: 4px 6px;
            vertical-align: top;
        }
        .excel-table th {
            text-align: center;
            font-weight: bold;
            background-color: #ffffff;
        }
        .header-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .rating-legend {
            border: 1px solid #000000;
            margin: 10px 0 15px 0;
            padding: 6px 10px;
            font-size: 8.5pt;
            background: #ffffff;
        }
        .commitment-text {
            font-size: 9.5pt;
            text-align: justify;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .no-print-bar {
            max-width: 1260px;
            margin: 0 auto 15px auto;
        }
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                padding: 0 !important;
            }
            .excel-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    @if(!request('embed'))
        {{-- Top Action Toolbar (Hidden during printing) --}}
        <div class="no-print-bar d-flex justify-content-between align-items-center bg-white p-2 border rounded shadow-sm">
            <div>
                <a href="{{ route('spms.opcr.matrix', $opcr->id) }}" class="btn btn-sm btn-outline-secondary font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Back to OPCR Matrix
                </a>
                <span class="ml-3 font-weight-bold text-dark">
                    Official OPCR Form &bull; {{ $opcr->office->office_name }} ({{ $opcr->year }} - {{ $opcr->semester == 1 ? '1st Half' : '2nd Half' }})
                </span>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-sm font-weight-bold px-4 shadow-sm" style="background-color: #16a085; color: #fff;">
                    <i class="fas fa-print mr-1"></i> Print / Save as PDF
                </button>
            </div>
        </div>
    @endif

    <div class="excel-container">
        {{-- Document Header --}}
        <div class="text-center mb-2">
            <div style="font-size: 9pt;">Republic of the Philippines</div>
            <div style="font-size: 9pt; font-weight: bold;">PROVINCE OF NEGROS ORIENTAL</div>
            <div style="font-size: 9pt; font-weight: bold;">MUNICIPALITY OF MABINAY</div>
            <div class="header-title mt-2">OFFICE PERFORMANCE COMMITMENT AND REVIEW (OPCR)</div>
        </div>

        {{-- Commitment Statement --}}
        <div class="commitment-text">
            I, <strong>{{ strtoupper($officeHead ? ($officeHead->fname . ' ' . $officeHead->lname) : ($opcr->prepared_by_name ?? 'LUCRECIA C. NICOLAS, MAEd')) }}</strong>, 
            <strong>{{ strtoupper($officeHead->position ?? ($opcr->prepared_by_position ?? 'MGDH-I (GSO)/HRMO-Designate')) }}</strong> of the 
            <strong>{{ strtoupper($opcr->office->office_name) }}</strong>, 
            commit to deliver and be rated on the accomplishments of the following targets in accordance with the indicated measures for the period 
            <strong>{{ $opcr->semester == 1 ? 'JANUARY 1 to JUNE 30' : 'JULY 1 to DECEMBER 31' }}, {{ $opcr->year }}</strong>.
        </div>

        <div class="d-flex justify-content-between align-items-end mb-2">
            <div></div>
            <div class="text-right" style="font-size: 9pt;">
                <div>_________________________________________</div>
                <div class="font-weight-bold text-uppercase" style="font-size: 9.5pt;">{{ $officeHead ? ($officeHead->fname . ' ' . $officeHead->lname) : ($opcr->prepared_by_name ?? 'LUCRECIA C. NICOLAS, MAEd') }}</div>
                <div class="small font-italic">Head of Office / Department Head</div>
                <div class="small mt-1">Date: ________________________</div>
            </div>
        </div>

        {{-- Rating Scale Legend Box --}}
        <div class="rating-legend">
            <div class="row text-center">
                <div class="col-2 font-weight-bold border-right">5 - Outstanding (130% &amp; above)</div>
                <div class="col-3 font-weight-bold border-right">4 - Very Satisfactory (115% - 129%)</div>
                <div class="col-3 font-weight-bold border-right">3 - Satisfactory (100% - 114%)</div>
                <div class="col-2 font-weight-bold border-right">2 - Unsatisfactory (51% - 99%)</div>
                <div class="col-2 font-weight-bold">1 - Poor (50% &amp; below)</div>
            </div>
        </div>

        {{-- Main OPCR Matrix Table --}}
        <table class="excel-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 18%;">MAJOR FINAL OUTPUT (MFO) / PROGRAM, ACTIVITY, PROJECT (PAP)</th>
                    <th rowspan="2" style="width: 22%;">SUCCESS INDICATORS<br><small>(TARGETS + MEASURES)</small></th>
                    <th colspan="2" style="width: 18%;">EVIDENCE</th>
                    <th rowspan="2" style="width: 8%;">ALLOTTED BUDGET</th>
                    <th rowspan="2" style="width: 12%;">DIVISIONS / INDIVIDUALS ACCOUNTABLE</th>
                    <th colspan="4" style="width: 12%;">RATING GUIDE</th>
                    <th rowspan="2" style="width: 10%;">REMARKS</th>
                </tr>
                <tr>
                    <th style="width: 9%; font-size: 8pt;">INDIVIDUAL SUPPORT</th>
                    <th style="width: 9%; font-size: 8pt;">REPORT OF SUPERVISOR</th>
                    <th style="width: 3%;">Q</th>
                    <th style="width: 3%;">E</th>
                    <th style="width: 3%;">T</th>
                    <th style="width: 3%;">A</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['Core Functions' => 'CORE FUNCTIONS (60%)', 'Strategic Functions' => 'STRATEGIC FUNCTIONS (20%)', 'Support Functions' => 'SUPPORT FUNCTIONS (20%)'] as $catKey => $catLabel)
                    <tr style="background-color: #f8fafc;">
                        <td colspan="11" class="font-weight-bold text-uppercase py-1">
                            {{ $catLabel }}
                        </td>
                    </tr>

                    @php
                        $categoryItems = $opcr->items->where('category', $catKey);
                    @endphp

                    @forelse($categoryItems as $item)
                        @php
                            $ipcrRatings = $item->ipcrItems->whereNotNull('rating_ave');
                            $qVal = $item->rating_q ?? ($ipcrRatings->count() > 0 ? round($ipcrRatings->avg('rating_q'), 2) : null);
                            $eVal = $item->rating_e ?? ($ipcrRatings->count() > 0 ? round($ipcrRatings->avg('rating_e'), 2) : null);
                            $tVal = $item->rating_t ?? ($ipcrRatings->count() > 0 ? round($ipcrRatings->avg('rating_t'), 2) : null);
                            $aVal = $item->rating_ave ?? ($ipcrRatings->count() > 0 ? round($ipcrRatings->avg('rating_ave'), 2) : null);

                            $assignedEmps = $item->assignedEmployees;
                            $empNames = $assignedEmps->map(fn($e) => $e->fname . ' ' . $e->lname)->implode(', ');
                        @endphp
                        <tr>
                            <td class="font-weight-bold">{!! nl2br(e($item->mfo_pap)) !!}</td>
                            <td>{!! nl2br(e($item->success_indicators)) !!}</td>
                            <td class="text-center font-italic small">
                                @if($item->ipcrItems->whereNotNull('evidence_file')->count() > 0)
                                    Submitted ({{ $item->ipcrItems->whereNotNull('evidence_file')->count() }})
                                @else
                                    No Evidence
                                @endif
                            </td>
                            <td class="text-center font-italic small">Summary Report</td>
                            <td class="text-center font-weight-bold">{{ $item->allotted_budget ?? '-' }}</td>
                            <td class="small">{{ $empNames ?: ($item->division_accountable ?? 'Unassigned') }}</td>
                            <td class="text-center font-weight-bold">{{ $qVal ? number_format($qVal, 2) : '-' }}</td>
                            <td class="text-center font-weight-bold">{{ $eVal ? number_format($eVal, 2) : '-' }}</td>
                            <td class="text-center font-weight-bold">{{ $tVal ? number_format($tVal, 2) : '-' }}</td>
                            <td class="text-center font-weight-bold text-success">{{ $aVal ? number_format($aVal, 2) : '-' }}</td>
                            <td class="small">{!! nl2br(e($item->remarks ?? '')) !!}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center font-italic text-muted py-2">
                                No items listed under {{ $catLabel }}.
                            </td>
                        </tr>
                    @endforelse
                @endforeach
            </tbody>
        </table>

        {{-- Signatories Table --}}
        @php
            $defaultPmt = "LUCRECIA C. NICOLAS\nMARY ANN Y. ACASO\nDINDO M. AMORGANDA\nELAN D. CADAYDAY\nBRIAN D. AUSEJO";
            $pmtRaw = $opcr->pmt_members ?: $defaultPmt;
            $pmtList = array_filter(array_map('trim', explode("\n", $pmtRaw)));
        @endphp
        <div class="mt-4 pt-2">
            <table class="w-100 border-0" style="font-size: 9pt;">
                <tr>
                    {{-- Column 1: Prepared by --}}
                    <td class="w-33 align-top pr-3 border-0">
                        <div class="font-weight-bold mb-3">Prepared by:</div>
                        <br><br>
                        <div class="font-weight-bold text-uppercase" style="font-size: 10pt;"><u>{{ $opcr->prepared_by_name ?? ($officeHead ? ($officeHead->fname . ' ' . $officeHead->lname) : 'LUCRECIA C. NICOLAS, MAEd') }}</u></div>
                        <div class="small font-weight-bold">{{ $opcr->prepared_by_position ?? 'MGDH-I (GSO)/HRMO-Designate' }}</div>
                        <div class="small mt-2">Date: ________________________</div>
                    </td>

                    {{-- Column 2: Reviewed by PMT --}}
                    <td class="w-33 align-top px-3 border-0 border-left">
                        <div class="font-weight-bold mb-2">Reviewed by (PMT Members):</div>
                        @foreach($pmtList as $member)
                            <div class="font-weight-bold text-uppercase mb-1" style="font-size: 9pt;"><u>{{ $member }}</u></div>
                        @endforeach
                        <div class="small mt-2">Date: ________________________</div>
                    </td>

                    {{-- Column 3: Final Rating by Mayor --}}
                    <td class="w-33 align-top pl-3 border-0 border-left">
                        <div class="font-weight-bold mb-3">Final Rating by:</div>
                        <br><br>
                        <div class="font-weight-bold text-uppercase" style="font-size: 10pt;"><u>{{ $opcr->approved_by_name ?? 'ERNIE T. UY, RN, JD' }}</u></div>
                        <div class="small font-weight-bold">{{ $opcr->approved_by_position ?? 'Municipal Mayor / Head of Agency' }}</div>
                        <div class="small mt-2">Date: ________________________</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
