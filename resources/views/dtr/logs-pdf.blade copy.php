<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>DTR @if($data != null) 
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
            opacity: 0.9; /* Adjust opacity for the whole body */
        }
    
        .b{
            color: #000000;
        }
    
        /* Apply a semi-transparent background to the table */
        .table-container {
            background-color: rgba(255, 255, 255, 0.8); /* White with 80% opacity */
            padding: 20px;
            border-radius: 10px;
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
    
        .table-custom .text-success {
            color: #28a745;
            font-weight: bold;
        }
    
        .table-custom .text-danger {
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
    </style>
</head>
<body>
    <div class="table-container">
        @foreach ($logsGroupedByDate as $date => $logs)
            <table class="table table-sm table-custom" style="page-break-inside: avoid;">
                <thead>
                    <tr>
                        <th colspan="2">{{ date('F j, Y', strtotime($date)) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        @if ($log['type'] == 'time_in' && isset($campuses[$log['device_in_campus']]))
                            <tr>
                                <td>
                                    <b>
                                        {{ ucwords(strtolower($log['fname'])) }} 
                                        {{ ucwords(strtolower($log['lname'])) }}
                                        {{ !empty($log['suffix']) ? ucwords(strtolower($log['suffix'])) : '' }}
                                    </b>
                                    <span class="text-success">logged in</span> 
                                    at {{ $campuses[$log['device_in_campus']] }}, 
                                    {{ $log['device_in_label'] ?? 'Unknown Label' }} 
                                    at <b class="b">{{ convertTo12HourFormat($log['time'] ?? '00:00:00') }}</b>.
                                </td>
                            </tr>
                        @elseif ($log['type'] == 'time_out' && !empty($log['time']) && isset($campuses[$log['device_out_campus']]))
                            <tr>
                                <td>
                                    <b>
                                        {{ ucwords(strtolower($log['fname'])) }} 
                                        {{ ucwords(strtolower($log['lname'])) }}
                                        {{ !empty($log['suffix']) ? ucwords(strtolower($log['suffix'])) : '' }}
                                    </b>
                                    <span class="text-danger">logged out</span> 
                                    at {{ $campuses[$log['device_out_campus']] }}, 
                                    {{ $log['device_out_label'] ?? 'Unknown Label' }} 
                                    at <b class="b">{{ convertTo12HourFormat($log['time']) }}</b>.
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            <div style="page-break-after: always;"></div>
        @endforeach
    </div>
</body>
</html>