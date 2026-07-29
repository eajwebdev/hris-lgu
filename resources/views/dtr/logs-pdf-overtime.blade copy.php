<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>DTR 
        @if($data != null) 
            {{ 
                ($data['dateFrom'] != $data['dateTo']) 
                ? date('F j, Y', strtotime($data['dateFrom'])) . ' to ' . date('F j, Y', strtotime($data['dateTo'])) 
                : date('F j, Y', strtotime($data['dateFrom'])) 
            }} 
        @endif
    </title>
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-image: url('{{ asset('Uploads/hris.png') }}') !important;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            opacity: 0.95;
            margin: 0;
            padding: 0;
        }

        .b {
            color: #000000;
            font-weight: bold;
        }

        .table-container {
            background-color: rgba(255, 255, 255, 0.8); /* White with 80% opacity */
            padding: 20px;
            border-radius: 10px;
            margin-top: -40px;
        }
    
        .table-custom th {
            background-color: #d6f0f463; /* Light gray background */
            font-size: 8px;
            font-weight: bold;
            padding: 3px;
            border-top: none;
            text-align: left;
        }
    
        .table-custom td {
            padding: 3px;
            font-size: 8px;
            border-color: #dee2e6; /* Match Bootstrap's default border color */
        }
    
        .text-success {
            color: #28a745;
            font-weight: bold;
        }
    
        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }
    
        .table-custom {
            border-collapse: collapse;
            width: 100%;
        }
    
        .table-sm th, .table-sm td {
            padding: 0.2rem;
        }

        @media print {
            body {
                background: none;
                margin: 0;
                padding: 0;
            }
            
            .table-container {
                background: none;
                padding: 0;
            }

            .page-break {
                page-break-after: always;
            }

            .table-custom th, .table-custom td {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="table-container">
        @php
            if (!function_exists('convertTo12HourFormat')) {
                function convertTo12HourFormat($time) {
                    return date("g:i:s A", strtotime($time));
                }
            }

            $campuses = [
                '1' => 'LGU Mabinay Main',
                '2' => 'LGU Mabinay Candoni',
                '3' => 'LGU Mabinay Cauayan',
                '4' => 'LGU Mabinay Hinigaran',
                '5' => 'LGU Mabinay Hinoba-an',
                '6' => 'LGU Mabinay Ilog',
                '7' => 'LGU Mabinay San Carlos',
                '8' => 'LGU Mabinay Sipalay',
                '9' => 'LGU Mabinay Victorias',
                '10' => 'LGU Mabinay Murcia',
                '11' => 'LGU Mabinay Valladolid',
                '12' => 'LGU Mabinay Moises Padilla',
            ];

            $logsGroupedByDate = [];
            foreach ($processedLogs as $employeeId => $logs) {
                foreach ($logs as $log) {
                    $logsGroupedByDate[$log['date']][] = $log;
                }
            }
            
            ksort($logsGroupedByDate);
        @endphp
        @foreach ($logsGroupedByDate as $date => $logs)
            <table class="table table-sm table-custom" style="page-break-inside: avoid;">
                <thead>
                    <tr>
                        <th colspan="2">{{ date('F j, Y', strtotime($date)) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        @php
                            $campusName = $campuses[$log['device_out_campus']] ?? 'Unknown Campus';
                        @endphp
                        @if ($campusName !== 'Unknown Campus')
                            <tr>
                                <td>
                                    <b>
                                        {{ ucwords(strtolower($log['fname'])) }} 
                                        {{ ucwords(strtolower($log['lname'])) }}
                                        {{ !empty($log['suffix']) ? ucwords(strtolower($log['suffix'])) : '' }}
                                    </b>
                                    <span class="text-danger">logged</span> 
                                    at {{ $campusName }} 
                                    at <b class="b">{{ convertTo12HourFormat($log['time']) }}</b>.
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            <div class="page-break"></div>
        @endforeach

    </div>
</body>
</html>
