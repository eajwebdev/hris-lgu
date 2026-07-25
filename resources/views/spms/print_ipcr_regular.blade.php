<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official IPCR Form (Landscape) - {{ $employee->fname }} {{ $employee->lname }}</title>
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
            margin-bottom: 8px;
        }
        .header-statement {
            font-size: 9.5pt;
            text-align: center;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        .ratee-box {
            text-align: center;
            margin-bottom: 12px;
        }
        .ratee-name {
            font-weight: bold;
            font-size: 10.5pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .subcat-header {
            font-weight: bold;
            font-style: italic;
            text-transform: uppercase;
            background-color: #ffffff;
            padding-left: 8px;
        }
        .section-header-red {
            font-weight: bold;
            color: #cc0000;
            text-transform: uppercase;
            font-size: 10pt;
        }
        .section-header-black {
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            font-size: 10pt;
        }
        .scale-box {
            font-size: 8.5pt;
            border-collapse: collapse;
            margin-left: auto;
            width: 100%;
        }
        .scale-box td {
            border: 1px solid #000000;
            padding: 1px 4px;
        }
        .sig-underline {
            border-bottom: 1px solid #000000;
            display: inline-block;
            min-width: 180px;
            text-align: center;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .excel-container {
                box-shadow: none !important;
                padding: 0 !important;
                border: none !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
            .excel-table th, .excel-table td {
                border: 1px solid #000000 !important;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Bar (Print / Close) --}}
    @if(!request('embed'))
        <div class="excel-container no-print mb-3 bg-light p-3 border rounded d-flex justify-content-between align-items-center">
            <div>
                <span class="font-weight-bold text-dark"><i class="fas fa-file-excel text-success mr-2"></i> Official IPCR Form (Landscape - Long Bond Paper 8.5" x 13")</span>
                <small class="text-muted d-block">Identical layout to the official LGU Mabinay Excel template</small>
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

    {{-- Main Printable Container --}}
    <div class="excel-container">

        {{-- Form Title --}}
        <div class="header-title">
            INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW FORM (IPCRF)
        </div>

        {{-- Commitment Statement --}}
        @php
            $periodStr = ($semester == 1) ? "January, {$year} - June, {$year}" : "July, {$year} - December, {$year}";
            $empFullName = strtoupper($employee->fname . ' ' . ($employee->mname ? $employee->mname[0] . '. ' : '') . $employee->lname);
            $empPos = strtoupper($employee->position ?? 'Personnel');
            $officeName = strtoupper($office->office_name ?? 'LGU-MABINAY');
            $resolvedSupervisor = $ipcr->assessed_by_name ?? ($officeHead ? ($officeHead->fname . ' ' . ($officeHead->mname ? $officeHead->mname[0].'.' : '') . ' ' . $officeHead->lname) : 'SUPERVISOR');
        @endphp
        <div class="header-statement">
            The <u><strong>{{ $officeName }}</strong></u> of the LGU-Mabinay, commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period <u><strong>{{ $periodStr }}</strong></u>.
        </div>

        {{-- Ratee Name Box --}}
        <div class="ratee-box">
            <div class="ratee-name">{{ $empFullName }}</div>
            <div class="small">Ratee</div>
            <div class="mt-1 small">Date: ________________________</div>
        </div>

        {{-- Header Signatures & Rating Scale Block --}}
        <table class="excel-table mb-2">
            <tr>
                <td style="width: 35%; padding: 6px;">
                    <div><strong>Reviewed by:</strong></div>
                    <div class="text-center mt-3">
                        <strong>{{ strtoupper($resolvedSupervisor) }}</strong><br>
                        <span class="small">{{ $officeHead->position ?? 'Department Head' }}</span><br>
                        <span class="small text-muted">(Immediate Supervisor/Dept. Head)</span>
                    </div>
                </td>
                <td style="width: 20%; padding: 6px; vertical-align: bottom;" class="text-center">
                    <div><strong>Date</strong></div>
                    <div class="mt-4">__________________</div>
                </td>
                <td style="width: 25%; padding: 6px;">
                    <div><strong>Approved by:</strong></div>
                    <div class="text-center mt-3">
                        <strong>ERNIE T. UY, RN, JD</strong><br>
                        <span class="small">Municipal Mayor</span><br>
                        <span class="small text-muted">(Head of Agency)</span>
                    </div>
                </td>
                <td style="width: 20%; padding: 0; vertical-align: middle;">
                    <table class="scale-box">
                        <tr><td style="width: 50%; font-weight: bold;">RATING SCALE</td><td>5 - Outstanding</td></tr>
                        <tr><td></td><td>4 - Very Satisfactory</td></tr>
                        <tr><td></td><td>3 - Satisfactory</td></tr>
                        <tr><td></td><td>2 - Unsatisfactory</td></tr>
                        <tr><td></td><td>1 - Poor</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Main Performance Evaluation Matrix Table --}}
        <table class="excel-table mb-2">
            <thead>
                <tr>
                    <th style="width: 28%;" rowspan="2">MFO / PAP</th>
                    <th style="width: 28%;" rowspan="2">SUCCESS INDICATORS<br><small>(TARGETS + MEASURES)</small></th>
                    <th style="width: 24%;" rowspan="2">Actual Accomplishment</th>
                    <th style="width: 12%;" colspan="4">Rating</th>
                    <th style="width: 8%;" rowspan="2">Remarks</th>
                </tr>
                <tr>
                    <th style="width: 3%;">Q</th>
                    <th style="width: 3%;">E</th>
                    <th style="width: 3%;">T</th>
                    <th style="width: 3%;">A</th>
                </tr>
            </thead>
            <tbody>
                {{-- CORE FUNCTIONS SECTION --}}
                <tr>
                    <td colspan="8" class="section-header-red">CORE FUNCTIONS:</td>
                </tr>
                @php
                    $coreItems = $ipcr->items->where('category', 'Core Functions');
                    $coreGrouped = $coreItems->groupBy(fn($i) => $i->subcategory ?: 'GENERAL CORE FUNCTIONS');
                @endphp
                @forelse($coreGrouped as $subcat => $items)
                    @if($subcat !== 'GENERAL CORE FUNCTIONS' || $coreGrouped->count() > 1)
                        <tr>
                            <td colspan="8" class="subcat-header">{{ $subcat }}</td>
                        </tr>
                    @endif
                    @foreach($items as $item)
                        @php $rVal = $item->rating_ave ?? $item->rating_average; @endphp
                        <tr>
                            <td style="padding-left: 12px;">• {!! nl2br(e($item->mfo_pap)) !!}</td>
                            <td>{!! nl2br(e($item->success_indicators)) !!}</td>
                            <td>{!! nl2br(e($item->actual_accomplishment ?? '')) !!}</td>
                            <td class="text-center align-middle">{{ $item->rating_q ?? '' }}</td>
                            <td class="text-center align-middle">{{ $item->rating_e ?? '' }}</td>
                            <td class="text-center align-middle">{{ $item->rating_t ?? '' }}</td>
                            <td class="text-center align-middle font-weight-bold">{{ $rVal ? number_format($rVal, 2) : '' }}</td>
                            <td class="small">{!! nl2br(e($item->remarks ?? '')) !!}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted font-italic py-2">No core functions assigned yet.</td>
                    </tr>
                @endforelse

                {{-- STRATEGIC FUNCTIONS SECTION (IF PRESENT) --}}
                @php
                    $stratItems = $ipcr->items->where('category', 'Strategic Functions');
                    $stratGrouped = $stratItems->groupBy(fn($i) => $i->subcategory ?: 'GENERAL STRATEGIC FUNCTIONS');
                @endphp
                @if($stratItems->count() > 0)
                    <tr>
                        <td colspan="8" class="section-header-red">STRATEGIC FUNCTIONS:</td>
                    </tr>
                    @foreach($stratGrouped as $subcat => $items)
                        @if($subcat !== 'GENERAL STRATEGIC FUNCTIONS' || $stratGrouped->count() > 1)
                            <tr>
                                <td colspan="8" class="subcat-header">{{ $subcat }}</td>
                            </tr>
                        @endif
                        @foreach($items as $item)
                            @php $rVal = $item->rating_ave ?? $item->rating_average; @endphp
                            <tr>
                                <td style="padding-left: 12px;">• {!! nl2br(e($item->mfo_pap)) !!}</td>
                                <td>{!! nl2br(e($item->success_indicators)) !!}</td>
                                <td>{!! nl2br(e($item->actual_accomplishment ?? '')) !!}</td>
                                <td class="text-center align-middle">{{ $item->rating_q ?? '' }}</td>
                                <td class="text-center align-middle">{{ $item->rating_e ?? '' }}</td>
                                <td class="text-center align-middle">{{ $item->rating_t ?? '' }}</td>
                                <td class="text-center align-middle font-weight-bold">{{ $rVal ? number_format($rVal, 2) : '' }}</td>
                                <td class="small">{!! nl2br(e($item->remarks ?? '')) !!}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endif

                {{-- SUPPORT FUNCTIONS SECTION --}}
                <tr>
                    <td colspan="8" class="section-header-red">SUPPORT FUNCTIONS</td>
                </tr>
                @php
                    $supportItems = $ipcr->items->where('category', 'Support Functions');
                    $supportGrouped = $supportItems->groupBy(fn($i) => $i->subcategory ?: 'GENERAL SUPPORT FUNCTIONS');
                @endphp
                @forelse($supportGrouped as $subcat => $items)
                    @if($subcat !== 'GENERAL SUPPORT FUNCTIONS' || $supportGrouped->count() > 1)
                        <tr>
                            <td colspan="8" class="subcat-header">{{ $subcat }}</td>
                        </tr>
                    @endif
                    @foreach($items as $item)
                        @php $rVal = $item->rating_ave ?? $item->rating_average; @endphp
                        <tr>
                            <td style="padding-left: 12px;">• {!! nl2br(e($item->mfo_pap)) !!}</td>
                            <td>{!! nl2br(e($item->success_indicators)) !!}</td>
                            <td>{!! nl2br(e($item->actual_accomplishment ?? '')) !!}</td>
                            <td class="text-center align-middle">{{ $item->rating_q ?? '' }}</td>
                            <td class="text-center align-middle">{{ $item->rating_e ?? '' }}</td>
                            <td class="text-center align-middle">{{ $item->rating_t ?? '' }}</td>
                            <td class="text-center align-middle font-weight-bold">{{ $rVal ? number_format($rVal, 2) : '' }}</td>
                            <td class="small">{!! nl2br(e($item->remarks ?? '')) !!}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted font-italic py-2">No support functions assigned yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- SUMMARY OF RATING TABLE --}}
        @php
            $coreRated = $coreItems->filter(fn($i) => !is_null($i->rating_ave ?? $i->rating_average));
            $coreAve = $coreRated->count() > 0 ? $coreRated->avg(fn($i) => $i->rating_ave ?? $i->rating_average) : 0;
            $coreWeighted = $coreAve * 0.90; // Formula in Excel: (total ave / count) x 90%

            $suppRated = $supportItems->filter(fn($i) => !is_null($i->rating_ave ?? $i->rating_average));
            $suppAve = $suppRated->count() > 0 ? $suppRated->avg(fn($i) => $i->rating_ave ?? $i->rating_average) : 0;
            $suppWeighted = $suppAve * 0.10; // Formula in Excel: (total ave / count) x 10%

            $finalNumerical = $ipcr->final_numerical_rating ?? (($coreRated->count() > 0 || $suppRated->count() > 0) ? round($coreWeighted + $suppWeighted, 3) : null);
            
            $finalAdjectival = $ipcr->final_adjectival_rating;
            if (!$finalAdjectival && $finalNumerical) {
                if ($finalNumerical >= 4.50) $finalAdjectival = 'Outstanding (VS)';
                elseif ($finalNumerical >= 3.50) $finalAdjectival = 'VS';
                elseif ($finalNumerical >= 2.50) $finalAdjectival = 'S';
                elseif ($finalNumerical >= 1.50) $finalAdjectival = 'U';
                else $finalAdjectival = 'P';
            }
        @endphp
        <table class="excel-table mb-2">
            <thead>
                <tr>
                    <th style="width: 25%; font-weight: bold;" class="text-left">SUMMARY OF RATING</th>
                    <th style="width: 35%;"></th>
                    <th style="width: 12%; text-align: center;">TOTAL</th>
                    <th style="width: 14%; text-align: center;">Final Numerical Rating</th>
                    <th style="width: 14%; text-align: center;">Final Adjectival Rating</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-weight-bold">SO/CF</td>
                    <td>Formula: (total of all average rating /no. of entries) x 90%</td>
                    <td class="text-center font-weight-bold align-middle">{{ $coreAve ? number_format($coreWeighted, 3) : '-' }}</td>
                    <td class="text-center font-weight-bold align-middle" rowspan="2" style="font-size: 11pt;">
                        {{ $finalNumerical ? number_format($finalNumerical, 2) : '-' }}
                    </td>
                    <td class="text-center font-weight-bold align-middle" rowspan="2" style="font-size: 11pt;">
                        {{ $finalAdjectival ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold">SF</td>
                    <td>Formula: (total of all average rating /no. of entries) x 10%</td>
                    <td class="text-center font-weight-bold align-middle">{{ $suppAve ? number_format($suppWeighted, 3) : '-' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Comments Box --}}
        <table class="excel-table mb-3">
            <tr>
                <td style="padding: 6px; min-height: 40px;">
                    <strong>Comments and Recommendation for Development Purposes:</strong>
                    <div style="min-height: 25px; margin-top: 4px;">{{ $ipcr->comments_recommendations ?? '' }}</div>
                </td>
            </tr>
        </table>

        {{-- Final Signatures Block --}}
        <table class="excel-table" style="border: none;">
            <tr style="border: none;">
                <td style="width: 30%; border: none; padding-right: 15px;">
                    <div><strong>Prepared by:</strong> <span class="float-right"><strong>Date:</strong> _________</span></div>
                    <div class="mt-4 text-center">
                        <strong style="text-decoration: underline;">{{ $empFullName }}</strong><br>
                        <span class="small font-italic">{{ $empPos }}</span>
                    </div>
                </td>
                <td style="width: 40%; border: none; padding: 0 15px;">
                    <div><strong>Reviewed:</strong> <span class="float-right"><strong>Date:</strong> _________</span></div>
                    <div class="text-center font-weight-bold font-italic mb-2">PMT Members</div>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>LUCRECIA C. NICOLAS</span>
                            <span>____________________</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>MARY ANN Y. ACASO</span>
                            <span>____________________</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>DINDO M. AMORGANDA</span>
                            <span>____________________</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>ELAN D. CADAYDAY</span>
                            <span>____________________</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>BRIAN D. AUSEJO</span>
                            <span>____________________</span>
                        </div>
                    </div>
                </td>
                <td style="width: 30%; border: none; padding-left: 15px;">
                    <div><strong>Final Rating by:</strong></div>
                    <div class="mt-4 text-center">
                        <strong style="text-decoration: underline;">ERNIE T. UY, RN, JD</strong><br>
                        <span class="small font-weight-bold">Municipal Mayor</span><br>
                        <span class="small font-italic">(Head of Agency)</span>
                    </div>
                </td>
            </tr>
        </table>

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
