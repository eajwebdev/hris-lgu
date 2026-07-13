<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>LEAVE APPLICATION REPORT</title>
    <style>
        @page {
            margin: 100px 50px; /* Top and bottom margins to accommodate header and footer */
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            line-height: 1.5;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            position: fixed;
            top: -90px; /* Adjust to fit within @page top margin */
            left: 0;
            right: 0;
            height: 70px;
            text-align: center;
        }

        footer {
            position: fixed;
            bottom: 160px; /* Adjusted from 0 to 30px */
            left: 0;
            right: 0;
            height: 70px; /* Adjusted height */
            font-size: 12px;
        }

        footer .signature {
            margin: 0;
        }

        footer .signature p {
            margin: 0;
            font-size: 12px;
        }

        .content {
            flex: 1;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto; /* Ensure table breaks inside pages */
        }

        table, th, td {
            border: 1px solid black;
            height: 15px; /* Adjust the height as needed */
        }

        th, td {
            font-size: 12px;
            padding: 5px;
        }

        tbody {
            page-break-inside: avoid; /* Prevent breaking inside rows */
        }

        tbody tr {
            page-break-inside: avoid; /* Prevent individual rows from splitting */
            page-break-after: auto;
        }

        .table-container {
            page-break-after: always;
            margin-top: 25px;
        }

        .table-container:last-child {
            page-break-after: auto;
        }

        .f2 {
            font-size: 14px;
        }

        .text-center{
            text-align: center;
        }
    </style>
</head>
<body>
    @php 
        $leaveTypes = [
            1 => 'VL', // Vacation Leave
            2 => 'MFL', // Mandatory/Forced Leave
            3 => 'SL', // Sick Leave
            4 => 'ML', // Maternity Leave
            5 => 'PL', // Paternity Leave
            6 => 'SPL', // Special Privilege Leave
            7 => 'SPL', // Solo Parent Leave
            8 => 'StL', // Study Leave
            9 => 'VAWC', // 10-Day VAWC Leave
            10 => 'RP', // Rehabilitation Privilege
            11 => 'SLBW', // Special Leave Benefits for Women
            12 => 'SEL', // Special Emergency (Calamity) Leave
            13 => 'AL', // Adoption Leave
            14 => 'VSC', // Vacation Service Credit
            15 => 'WL' // Wellness Leave
        ];
    @endphp
    <header>
        <img src="{{ asset('Uploads/leave-report-header.png') }}" alt="Header Image" width="100%">
    </header>

    <!-- Fixed Footer -->
    <footer>
        <div class="signature">
            <p class="f2">Respectfully,</p><br>
            <p class="f2"><b>{{ strtoupper($setting->hr_fname) }} {{ isset($setting->hr_mname) ? strtoupper(substr($setting->hr_mname, 0, 1)).'.' : '' }}  {{ strtoupper($setting->hr_lname) }}</b><br>
            Head, Human Resource Management Office</p><br>
            <p class="f2">Approved:</p><br>
            <p class="f2"><b>{{ strtoupper($setting->mayor_fname) }} {{ isset($setting->mayor_mname) ? substr($setting->mayor_mname, 0, 1) : '' }} {{ strtoupper($setting->mayor_lname) }}</b><br>
            Mayor</p>
        </div>
        <br>
        <img src="{{ asset('Uploads/transmittal-footer.png') }}" alt="Header Image" width="100%">
    </footer>

    <!-- Main Content -->
    <div class="content">
        @php
            use Carbon\Carbon;
            $chunks = array_chunk($applications->toArray(), 15);
        @endphp
    
        <p class="f2">{{ \Carbon\Carbon::now('Asia/Manila')->format('F j, Y') }}
            <br>
            <b>{{ strtoupper($setting->mayor_fname) }} {{ isset($setting->mayor_mname) ? substr($setting->mayor_mname, 0, 1) : '' }} {{ strtoupper($setting->mayor_lname) }}</b>
            <br>
            Mayor</p>
        <p class="f2">Sir:</p>
        <p class="f2">The following are applications for leave from the HRIS as of {{ $formattedDateRange }} for approval.</p>

        @php
            $chunks = array_chunk($applications->toArray(), 18);
        @endphp

        @foreach ($chunks as $chunk)
            <div class="table-container">
            <table>
            <thead>
            <tr>
                <th>NAME</th>
                <th>TYPE OF LEAVE</th>
                <th>INCLUSIVE DATES</th>
                <th>APPROVED (✓)</th>
                <th>DISAPPROVED (✓)</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($chunk as $row)
                <tr>
                <td>
                {{ $row['lname'] }}, {{ $row['fname'] }} {{ $row['suffix'] }}
                {{ isset($row['mname']) ? strtoupper(substr($row['mname'], 0, 1)).'.' : '' }}
                </td>
                <td class="text-center">{{ $leaveTypes[$row['leave_type']] }}</td>
                <td class="text-center">
                    @php
                        $dates = explode(' to ', $row['date_range']); // split if it's a range

                        if(count($dates) === 2) {
                            // It's a range
                            $start = (new DateTime($dates[0]))->format('M j, Y');
                            $end   = (new DateTime($dates[1]))->format('M j, Y');
                            $formatted = "$start - $end";
                        } else {
                            // Single date
                            $formatted = (new DateTime($dates[0]))->format('M j, Y');
                        }
                    @endphp
                    {{ $formatted }}
                </td>
                <td class="text-center">
                    {{-- {{ ($row['remarks_stat'] <= 0) ? '✓' : '' }} --}}
                </td>
                <td class="text-center">
                    {{-- {{ ($row['remarks_stat'] > 0) ? '✓' : '' }} --}}
                </td>  
                </tr>
            @endforeach
            </tbody>
            </table>
            </div>
        @endforeach
    </div>
</body>
</html>
