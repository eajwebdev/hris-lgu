<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTR {{ strtoupper($startDate) }} - {{ strtoupper($endDate) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            text-align: center; /* Center aligns text content */
            margin-top: -43px;
        }
        .column1 {
            float: left;
            width: 52%;
            margin-left: -20px;
            margin-top: 20px;
        }
        .column2 {
            margin-left: 15px;
            float: left;
            width: 52%;
            margin-top: 20px;
        }
        .table-head{
            width: 100%;
            border-collapse: collapse;
        }
        .b-none{
            border: none !important;
            font-size: 10px;
        }
        .table-time {
            width: 100%;
            border: 1px solid rgb(255, 255, 255);
            border-collapse: collapse; /* Added to collapse table borders */
        }
        th, td {
            border: 1px solid black; /* Ensures all cells have borders */
            padding: 0px;
            text-align: left; /* Aligns text to the left */
        }
        .center{
            text-align: center;
        }
        .font{
            font-size: 10px;
        } 
        .font1{
            font-size: 10px;
            width: 50px;
            height: 20px;
        }
        .left{
            text-align: left;
        }
        .head-font{
            font-size: 10px;
            
        }
        .bb {
            display: inline-block;
            width: 200px; /* Adjust the width as needed */
            border-bottom: 1px solid black; /* If you want a bottom border */
        }
        .header{
            border-bottom: 1px solid black; 
            position: relative;
            font-weight: 700;
            font-size: 9px;
            padding: 0;
            margin: 0px 0px -2px 0px; 
        }
    </style>
</head>
@php 
    $time_arrays = [];

    for ($day = 1; $day <= 31; $day++) {
        $day_record = $dtrRecords->first(function ($record) use ($year, $day) {
            return substr($record->date, 8, 2) == sprintf('%02d', $day) && substr($record->date, 0, 4) == $year;
        });

        $time_array_overtime = [];

        if ($day_record) {
            $day_overtime = $day_record->time_over;

            $time_array_overtime = $day_overtime ? explode(',', $day_overtime) : [];

            foreach ($time_array_overtime as $key => $time) {
                $time_array_overtime[$key] = date('h:i:s A', strtotime($time));
            }

        }

        $time_arrays[$day] = [
            'overtime' => $time_array_overtime,
        ];
    }
@endphp

<body>
    <div class="column1"> 
        <img src="{{ asset('Uploads/dtr-header.png') }}" width="110%" class="mt-5" alt="Header Image">
        <div>
            <span class="font">Name of Employee :</span> <span class="header" style="relative; display: inline-block; width: 73%; text-align: left;">&nbsp; {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->prefix)) }} {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(ucwords($employee->mname)) }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Office : </span> <span class="header" style="relative; display: inline-block; width: 67.5%; text-align: left;">&nbsp;{{ strtoupper(ucwords($employee->office_name)) }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">For the month of : </span> <span class="header" style="relative; display: inline-block; width: 37%; text-align: left;">&nbsp;{{ $startDate }} - {{ $endDate }}</span>, <span class="header" style="relative; display: inline-block; width: 36%; text-align: left;">&nbsp;{{ $year }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Official Hour of Arrival in Regular Days : </span> <span class="header" style="relative; display: inline-block; width: 49.3%; text-align: left;">&nbsp; </span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Saturdays : </span> <span class="header" style="relative; display: inline-block; width: 82.6%; text-align: left;">&nbsp;</span>
        </div> 
        <table class="table-time">
            <thead>
                <tr>
                    <th rowspan="1" width="20"></th>
                    <th colspan="2" class="font1 center">AM</th>
                    <th colspan="2" class="font1 center">PM</th>
                    <th colspan="2" class="font1 center">OVERTIME</th>
                </tr>
            </thead>
            <tbody>    
            @if ($period == 1)
                @for ($day = 1; $day <= 15; $day++)
                    @php
                        $overtime = isset($time_arrays[$day]['overtime']) ? $time_arrays[$day]['overtime'] : [];
                        sort($overtime); // Sort from lowest to highest
                        $firstTime = isset($overtime[0]) ? substr($overtime[0], 0, strrpos($overtime[0], ':')) : '';
                        $lastTime = isset($overtime[count($overtime) - 1]) ? substr($overtime[count($overtime) - 1], 0, strrpos($overtime[count($overtime) - 1], ':')) : '';
                    @endphp
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center">{{ $firstTime }}</th>
                        <th class="font1 center">{{ $lastTime }}</th>
                    </tr>
                @endfor
                @for ($day = 16; $day <= 31; $day++)
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                    </tr>
                @endfor
            @elseif ($period == 2)
                @for ($day = 1; $day <= 15; $day++)
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                    </tr>
                @endfor
                @for ($day = 16; $day <= 31; $day++)
                    @php
                        $overtime = isset($time_arrays[$day]['overtime']) ? $time_arrays[$day]['overtime'] : [];
                        sort($overtime);
                        $firstTime = isset($overtime[0]) ? substr($overtime[0], 0, strrpos($overtime[0], ':')) : '';
                        $lastTime = isset($overtime[count($overtime) - 1]) ? substr($overtime[count($overtime) - 1], 0, strrpos($overtime[count($overtime) - 1], ':')) : '';
                    @endphp
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center">{{ $firstTime }}</th>
                        <th class="font1 center">{{ $lastTime }}</th>
                    </tr>
                @endfor
            @elseif ($period == 3)
                @foreach ($time_arrays as $day => $times)
                    @php
                        $overtime = isset($times['overtime']) ? $times['overtime'] : [];
                        sort($overtime);
                        $firstTime = isset($overtime[0]) ? substr($overtime[0], 0, strrpos($overtime[0], ':')) : '';
                        $lastTime = isset($overtime[count($overtime) - 1]) ? substr($overtime[count($overtime) - 1], 0, strrpos($overtime[count($overtime) - 1], ':')) : '';
                    @endphp
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center">{{ $firstTime }}</th>
                        <th class="font1 center">{{ $lastTime }}</th>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>            
        <p style="font-size: 10px; text-align: left;">
            <b style="margin-left: 25px;">CERTIFY</b> on my honor that the above is a true and correct of hours of worked performed, record of which was made daily at of arrival and departure from office:
        </p> 
        <div><br>
            <span class="font"><b>@if(isset($employee)) {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->prefix)) }} {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(ucwords($employee->mname)) }} @endif</b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Employee’s Signature</span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <div style="m; ">
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: left; margin-top: -25px;"><b>VERIFIED</b> as to the prescribed office hours:</span>
        </div>
        <div>
            <span class="font"><b> </b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Immediate Supervisor’s Signature </span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <p style="font-size: 8px; text-align: center;">
            Doc Control Code: CPSU-F-HRMO-03-REV01     Effective Date:  09/04/2024     Page No:  1 of 1
        </p>
    </div>
    <div class="column2"> 
        <img src="{{ asset('Uploads/dtr-header.png') }}" width="110%" class="mt-5" alt="Header Image">
        <div>
            <span class="font">Name of Employee :</span> <span class="header" style="relative; display: inline-block; width: 73%; text-align: left;">&nbsp;  {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->prefix)) }} {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(ucwords($employee->mname)) }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Office : </span> <span class="header" style="relative; display: inline-block; width: 67.5%; text-align: left;">&nbsp;{{ strtoupper(ucwords($employee->office_name)) }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">For the month of : </span> <span class="header" style="relative; display: inline-block; width: 37%; text-align: left;">&nbsp;{{ $startDate }} - {{ $endDate }}</span>, <span class="header" style="relative; display: inline-block; width: 36%; text-align: left;">&nbsp;{{ $year }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Official Hour of Arrival in Regular Days : </span> <span class="header" style="relative; display: inline-block; width: 49.3%; text-align: left;">&nbsp; </span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Saturdays : </span> <span class="header" style="relative; display: inline-block; width: 82.6%; text-align: left;">&nbsp;</span>
        </div> 
        <table class="table-time">
            <thead>
                <tr>
                    <th rowspan="1" width="20"></th>
                    <th colspan="2" class="font1 center">AM</th>
                    <th colspan="2" class="font1 center">PM</th>
                    <th colspan="2" class="font1 center">OVERTIME</th>
                </tr>
            </thead>
            <tbody>    
            @if ($period == 1)
                @for ($day = 1; $day <= 15; $day++)
                    @php
                        $overtime = isset($time_arrays[$day]['overtime']) ? $time_arrays[$day]['overtime'] : [];
                        sort($overtime); // Sort from lowest to highest
                        $firstTime = isset($overtime[0]) ? substr($overtime[0], 0, strrpos($overtime[0], ':')) : '';
                        $lastTime = isset($overtime[count($overtime) - 1]) ? substr($overtime[count($overtime) - 1], 0, strrpos($overtime[count($overtime) - 1], ':')) : '';
                    @endphp
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center">{{ $firstTime }}</th>
                        <th class="font1 center">{{ $lastTime }}</th>
                    </tr>
                @endfor
                @for ($day = 16; $day <= 31; $day++)
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                    </tr>
                @endfor
            @elseif ($period == 2)
                @for ($day = 1; $day <= 15; $day++)
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                    </tr>
                @endfor
                @for ($day = 16; $day <= 31; $day++)
                    @php
                        $overtime = isset($time_arrays[$day]['overtime']) ? $time_arrays[$day]['overtime'] : [];
                        sort($overtime);
                        $firstTime = isset($overtime[0]) ? substr($overtime[0], 0, strrpos($overtime[0], ':')) : '';
                        $lastTime = isset($overtime[count($overtime) - 1]) ? substr($overtime[count($overtime) - 1], 0, strrpos($overtime[count($overtime) - 1], ':')) : '';
                    @endphp
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center">{{ $firstTime }}</th>
                        <th class="font1 center">{{ $lastTime }}</th>
                    </tr>
                @endfor
            @elseif ($period == 3)
                @foreach ($time_arrays as $day => $times)
                    @php
                        $overtime = isset($times['overtime']) ? $times['overtime'] : [];
                        sort($overtime);
                        $firstTime = isset($overtime[0]) ? substr($overtime[0], 0, strrpos($overtime[0], ':')) : '';
                        $lastTime = isset($overtime[count($overtime) - 1]) ? substr($overtime[count($overtime) - 1], 0, strrpos($overtime[count($overtime) - 1], ':')) : '';
                    @endphp
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                        <th class="font1 center">{{ $firstTime }}</th>
                        <th class="font1 center">{{ $lastTime }}</th>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
        <p style="font-size: 10px; text-align: left;">
            <b style="margin-left: 25px;">CERTIFY</b> on my honor that the above is a true and correct of hours of worked performed, record of which was made daily at of arrival and departure from office:
        </p> 
        <div><br>
            <span class="font"><b>@if(isset($employee)) {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->prefix)) }} {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(ucwords($employee->mname)) }} @endif</b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Employee’s Signature</span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <div style="m; ">
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: left; margin-top: -25px;"><b>VERIFIED</b> as to the prescribed office hours:</span>
        </div>
        <div>
            <span class="font"><b> </b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Immediate Supervisor’s Signature </span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <p style="font-size: 8px; text-align: center;">
            Doc Control Code: CPSU-F-HRMO-03-REV01     Effective Date:  09/04/2024     Page No:  1 of 1
        </p>
    </div>
</body>
</html>
