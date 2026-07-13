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
        }
        .column2 {
            margin-left: 15px;
            float: left;
            width: 52%;
        }
        .table-head{
            width: 100%;
            border-collapse: collapse;
        }
        .b-none{
            border: none !important;
            font-size: 10px;
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

        $time_array_in = [];
        $time_array_out = [];

        if ($day_record) {
            $day_time_in = $day_record->time_in;
            $day_time_out = $day_record->time_out;

            $time_array_in = $day_time_in ? explode(',', $day_time_in) : [];
            $time_array_out = $day_time_out ? explode(',', $day_time_out) : [];

            sort($time_array_in);
            sort($time_array_out);

            $time_array_in = array_filter($time_array_in, function($time) {
                $time_obj = strtotime($time);
                return $time_obj < strtotime('14:00:00');
            });

            $time_array_in = array_values($time_array_in);

            foreach ($time_array_in as $key => $time) {
                $time_array_in[$key] = date('g:i:s A', strtotime($time));
            }

            foreach ($time_array_out as $key => $time) {
                $time_array_out[$key] = date('g:i:s A', strtotime($time));
            }
        }

        $time_arrays[$day] = [
            'in' => $time_array_in,
            'out' => $time_array_out,
        ];
    }
@endphp

<body>
    <div class="column1"> 
        <img src="{{ asset('Uploads/dtr-header.jpg') }}" width="110%" style="margin-top: 30px;" alt="Header Image">
        <div>
            <span class="font">Name of Employee :</span> <span class="header" style="relative; display: inline-block; width: 73%; text-align: left;">&nbsp; @if(isset($employee)) {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(substr($employee->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->suffix)) }}@endif</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Office/Campus/College : </span> <span class="header" style="relative; display: inline-block; width: 67.5%; text-align: left;">&nbsp;{{ isset($employee) ? strtoupper(ucwords($employee->office_name)) : '' }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">For the month of : </span> <span class="header" style="relative; display: inline-block; width: 37%; text-align: left;">&nbsp;{{ isset($employee) ? $startDate : '' }} - {{ isset($employee) ? $endDate : '' }}</span>, <span class="header" style="relative; display: inline-block; width: 36%; text-align: left;">&nbsp;{{ isset($employee) ? $year : '' }}</span>
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
            @php $morningcutoff = new DateTime('10:00'); @endphp
            @if ($period == 1)
                {{-- Display days 1-15 --}}
                @for ($day = 1; $day <= 15; $day++)
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['in']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $inTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['out']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                    $outTime = new DateTime(reset($time_arrays[$day]['out']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $outTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">{{ count($time_arrays[$day]['in']) > 1 ? substr(end($time_arrays[$day]['in']), 0, strrpos(end($time_arrays[$day]['in']), ':')) : '' }}</th>
                        <th class="font1 center">{{ count($time_arrays[$day]['out']) > 1 ? substr(end($time_arrays[$day]['out']), 0, strrpos(end($time_arrays[$day]['out']), ':')) : '' }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
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
                    
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['in']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $inTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['out']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                    $outTime = new DateTime(reset($time_arrays[$day]['out']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $outTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">{{ count($time_arrays[$day]['in']) > 1 ? substr(end($time_arrays[$day]['in']), 0, strrpos(end($time_arrays[$day]['in']), ':')) : '' }}</th>
                        <th class="font1 center">{{ count($time_arrays[$day]['out']) > 1 ? substr(end($time_arrays[$day]['out']), 0, strrpos(end($time_arrays[$day]['out']), ':')) : '' }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                    </tr>
                @endfor
            @elseif ($period == 3)
                @foreach ($time_arrays as $day => $times)
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['in']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $inTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['out']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                    $outTime = new DateTime(reset($time_arrays[$day]['out']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $outTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">{{ count($time_arrays[$day]['in']) > 1 ? substr(end($time_arrays[$day]['in']), 0, strrpos(end($time_arrays[$day]['in']), ':')) : '' }}</th>
                        <th class="font1 center">{{ count($time_arrays[$day]['out']) > 1 ? substr(end($time_arrays[$day]['out']), 0, strrpos(end($time_arrays[$day]['out']), ':')) : '' }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
        <p style="font-size: 10px; text-align: left;">
            <b style="margin-left: 25px;">CERTIFY</b> on my honor that the above is a true and correct of hours of worked performed, record of which was made daily at of arrival and departure from office:
        </p> 
        <div><br>
            <span class="font"><b>@if(isset($employee)) {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(substr($employee->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->suffix)) }}@endif</b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Employee’s Signature</span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <div style="m; ">
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: left; margin-top: -25px;"><b>VERIFIED</b> as to the prescribed office hours:</span>
        </div>
        <div>
            <span class="font"><b>@if(isset($supervisor)) {{ strtoupper(ucwords($supervisor->fname)) }} {{ strtoupper(substr($supervisor->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($supervisor->lname)) }}{{ ($supervisor->prefix) ? ', '.strtoupper(ucwords($supervisor->prefix)) : ''}}@endif</b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Immediate Supervisor’s Signature </span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <p style="font-size: 8px; text-align: center;">
            Doc Control Code: CPSU-F-HRMO-03 Effective Date:  09/12/2018     Page No:  1 of 1
        </p>
    </div>
    <div class="column2"> 
        <img src="{{ asset('Uploads/dtr-header.jpg') }}" width="110%" style="margin-top: 30px;" alt="Header Image">
        <div>
            <span class="font">Name of Employee :</span> <span class="header" style="relative; display: inline-block; width: 73%; text-align: left;">&nbsp; @if(isset($employee)) {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(substr($employee->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->suffix)) }}@endif</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Office/Campus/College : </span> <span class="header" style="relative; display: inline-block; width: 67.5%; text-align: left;">&nbsp;{{ isset($employee) ? strtoupper(ucwords($employee->office_name)) : '' }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">For the month of : </span> <span class="header" style="relative; display: inline-block; width: 37%; text-align: left;">&nbsp;{{ isset($employee) ? $startDate : '' }} - {{ isset($employee) ? $endDate : '' }}</span>, <span class="header" style="relative; display: inline-block; width: 36%; text-align: left;">&nbsp;{{ isset($employee) ? $year : '' }}</span>
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
            @php $morningcutoff = new DateTime('10:00'); @endphp
            @if ($period == 1)
                {{-- Display days 1-15 --}}
                @for ($day = 1; $day <= 15; $day++)
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['in']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $inTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['out']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                    $outTime = new DateTime(reset($time_arrays[$day]['out']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $outTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">{{ count($time_arrays[$day]['in']) > 1 ? substr(end($time_arrays[$day]['in']), 0, strrpos(end($time_arrays[$day]['in']), ':')) : '' }}</th>
                        <th class="font1 center">{{ count($time_arrays[$day]['out']) > 1 ? substr(end($time_arrays[$day]['out']), 0, strrpos(end($time_arrays[$day]['out']), ':')) : '' }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
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
                    
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['in']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $inTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['out']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                    $outTime = new DateTime(reset($time_arrays[$day]['out']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $outTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">{{ count($time_arrays[$day]['in']) > 1 ? substr(end($time_arrays[$day]['in']), 0, strrpos(end($time_arrays[$day]['in']), ':')) : '' }}</th>
                        <th class="font1 center">{{ count($time_arrays[$day]['out']) > 1 ? substr(end($time_arrays[$day]['out']), 0, strrpos(end($time_arrays[$day]['out']), ':')) : '' }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                    </tr>
                @endfor
            @elseif ($period == 3)
                @foreach ($time_arrays as $day => $times)
                    <tr>
                        <th class="font center" width="15">{{ $day }}</th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['in']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $inTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">
                            @if(count($time_arrays[$day]['out']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                    $outTime = new DateTime(reset($time_arrays[$day]['out']));
                                @endphp
                                @if($inTime < $morningcutoff)
                                    {{ $outTime->format('g:i') }}
                                @endif
                            @endif
                        </th>
                        <th class="font1 center">{{ count($time_arrays[$day]['in']) > 1 ? substr(end($time_arrays[$day]['in']), 0, strrpos(end($time_arrays[$day]['in']), ':')) : '' }}</th>
                        <th class="font1 center">{{ count($time_arrays[$day]['out']) > 1 ? substr(end($time_arrays[$day]['out']), 0, strrpos(end($time_arrays[$day]['out']), ':')) : '' }}</th>
                        <th class="font1 center"></th>
                        <th class="font1 center"></th>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
        <p style="font-size: 10px; text-align: left;">
            <b style="margin-left: 25px;">CERTIFY</b> on my honor that the above is a true and correct of hours of worked performed, record of which was made daily at of arrival and departure from office:
        </p> 
        <div><br>
            <span class="font"><b>@if(isset($employee)) {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(substr($employee->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->suffix)) }}@endif</b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Employee’s Signature</span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <div style="m; ">
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: left; margin-top: -25px;"><b>VERIFIED</b> as to the prescribed office hours:</span>
        </div>
        <div>
            <span class="font"><b>@if(isset($supervisor)) {{ strtoupper(ucwords($supervisor->fname)) }} {{ strtoupper(substr($supervisor->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($supervisor->lname)) }}{{ ($supervisor->prefix) ? ', '.strtoupper(ucwords($supervisor->prefix)) : ''}}@endif</b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Immediate Supervisor’s Signature </span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <p style="font-size: 8px; text-align: center;">
            Doc Control Code: CPSU-F-HRMO-03 Effective Date:  09/12/2018     Page No:  1 of 1
        </p>
    </div>
</body>
</html>
