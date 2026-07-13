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

    function convertTimeFormat($timeString) {
        // Define the timezone for Asia/Manila
        $timezone = new DateTimeZone('Asia/Manila');
        
        // Split the time range into start and end times
        $times = explode('-', $timeString);

        // Check if the time range is valid (contains exactly two parts)
        if (count($times) === 2) {
            // Convert start time to DateTime object with the Manila timezone
            $startTime = new DateTime($times[0], $timezone);
            $startFormatted = $startTime->format('g:i A');
            
            // Convert end time to DateTime object with the Manila timezone
            $endTime = new DateTime($times[1], $timezone);
            $endFormatted = $endTime->format('g:i A');
            
            return $startFormatted . ' - ' . $endFormatted;
        }
        
        // Return a default value in case of invalid input
        return 'Invalid time range';
    }


    $mornMonFormatted = convertTimeFormat($offtime->morn_mon);
    $afterMonFormatted = convertTimeFormat($offtime->aft_mon);
    
    $monday = $mornMonFormatted.' || '.$afterMonFormatted;

    $mornTueFormatted = convertTimeFormat($offtime->morn_tue);
    $afterTueFormatted = convertTimeFormat($offtime->aft_tue);

    $tuesday = $mornTueFormatted.' || '.$afterTueFormatted;

    $mornWedFormatted = convertTimeFormat($offtime->morn_wed);
    $afterWedFormatted = convertTimeFormat($offtime->aft_wed);

    $wendesday = $mornWedFormatted.' || '.$afterWedFormatted;

    $mornThuFormatted = convertTimeFormat($offtime->morn_thu);
    $afterThuFormatted = convertTimeFormat($offtime->aft_thu);

    $thursday = $mornThuFormatted.' || '.$afterThuFormatted;

    $mornFriFormatted = convertTimeFormat($offtime->morn_fri);
    $afterFriFormatted = convertTimeFormat($offtime->aft_fri);

    $friday = $mornFriFormatted.' || '.$afterFriFormatted;

   $regulartime = "8:00 AM - 12:00 PM || 1:00 PM - 5:00 PM";

    $arrayday = [
        '1' => $monday ?? '',
        '2' => $tuesday ?? '',
        '3' => $wendesday ?? '',
        '4' => $thursday ?? '',
        '5' => $friday ?? '',
    ];
    
    $notnatch = [];

    foreach ($arrayday as $day => $time) {
        if ($time !== $regulartime) {
            switch ($day) {
                case '1':
                    $notnatch[$day] = 'MON. ' . $time;
                    break;
                case '2':
                    $notnatch[$day] = 'TUE. ' . $time;
                    break;
                case '3':
                    $notnatch[$day] = 'WED. ' . $time;
                    break;
                case '4':
                    $notnatch[$day] = 'THU. ' . $time;
                    break;
                case '5':
                    $notnatch[$day] = 'FRI. ' . $time;
                    break;
            }
        }
    }

    $countnmatch = count($notnatch);
    $first_two_non_matching_days = array_slice($notnatch, 0, 2);
    $third_and_fourth_non_matching_days = array_slice($notnatch, 2, 2);
    $last_non_matching_day = $countnmatch === 5 ? end($notnatch) : null;

@endphp

<body>
    
    <div class="column1"> 
        <img src="{{ pdf_image('Uploads/dtr-header.png') }}" width="110%" class="mt-5" alt="Header Image">
        <div>
            <span class="font">Name of Employee :</span> <span class="header" style="relative; display: inline-block; width: 73%; text-align: left;">&nbsp; @if(isset($employee)) {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(substr($employee->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->suffix)) }}@endif</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Office : </span>
            <span class="header" style="position: relative; display: inline-block; width: 67.5%; text-align: left;">
                &nbsp;
                {{ isset($employee) ? strtoupper(ucwords($employee->office_name)) : '' }}
            </span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">For the month of : </span> <span class="header" style="relative; display: inline-block; width: 37%; text-align: left;">&nbsp;{{ isset($employee) ? $startDate : '' }} - {{ isset($employee) ? $endDate : '' }}</span>, <span class="header" style="relative; display: inline-block; width: 36%; text-align: left;">&nbsp;{{ isset($employee) ? $year : '' }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Official Hour of Arrival in Regular Days : </span> <span class="header" style="relative; display: inline-block; width: 49.3%; text-align: left;">&nbsp; 8:00 AM - 12:00 PM || 1:00 PM - 5:00 PM</span>
        </div>
        @if($countnmatch > 0)
            <div style="margin-top: -2px;">
                <span class="header" style="relative; display: inline-block; width: 98%; text-align: left; font-size: 8px;">&nbsp; {{ $first_two_non_matching_days[0] ?? ''}} @if(isset($first_two_non_matching_days[1]))||@endif {{ $first_two_non_matching_days[1] ?? ''}}</span>
            </div>
        @endif
        @if($countnmatch > 2)
            <div style="margin-top: -8px;">
                <span class="header" style="relative; display: inline-block; width: 98%; text-align: left; font-size: 8px;">&nbsp; {{ $third_and_fourth_non_matching_days[0] ?? ''}} @if(isset($third_and_fourth_non_matching_days[1]))||@endif {{ $third_and_fourth_non_matching_days[1] ?? ''}}</span>
            </div>
        @endif
        @if($countnmatch == 5)
            <div style="margin-top: -9px;">
                <span class="header" style="relative; display: inline-block; width: 98%; text-align: left; font-size: 8px;">&nbsp; {{ $last_non_matching_day ?? ''}}</span>
            </div>
        @endif
        <div style="margin-top: {{ ($countnmatch == 0) ? '-9px;' : '' }}{{ ($countnmatch > 0 && $countnmatch < 3) ? '-18px;' : '' }}{{ ($countnmatch > 2 && $countnmatch < 5) ? '-20px;' : '' }}{{ ($countnmatch == 5) ? '-20px;' : '' }}">
            <span class="font">Saturdays : </span> <span class="header mb-2 mb-2" style="relative; display: inline-block; width: 82.6%; text-align: left;">&nbsp;</span>
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
                @php 
                    $morningCutoff = new DateTime('10:00');
                    $morningOutStart = new DateTime('10:00');
                    $morningOutEnd = new DateTime('13:30');
                    $noonInStart = new DateTime('11:00');
                    $noonInEnd = new DateTime('11:30');
                    $afternoonOutStart = new DateTime('13:31');
                    $afternoonOutEnd = new DateTime('18:00');
                @endphp
                @for ($day = 1; $day <= 31; $day++)
                <tr>
                    <th class="font center" width="15">{{ $day }}</th>

                    <!-- Morning Time In -->
                    <td class="font1 center">
                        @if (($period == 0) || ($period == 3) || ($period == 1 && $day <= 15) || ($period == 2 && $day > 15))
                            @if (isset($time_arrays[$day]['in']) && count($time_arrays[$day]['in']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                @endphp
                                @if ($inTime < $morningCutoff)
                                    {{ $inTime->format('g:i') }}
                                @endif
                            @endif
                        @endif
                    </td>

                    <!-- Morning Time Out -->
                    <td class="font1 center">
                        @if (($period == 0) || ($period == 3) || ($period == 1 && $day <= 15) || ($period == 2 && $day > 15))
                            @if (isset($time_arrays[$day]['out']) && count($time_arrays[$day]['out']) > 0)
                                @php
                                    $outTime = new DateTime(reset($time_arrays[$day]['out']));
                                @endphp
                                @if ($outTime > $morningOutStart && $outTime < $morningOutEnd)
                                    {{ $outTime->format('g:i') }}
                                @endif
                            @endif
                        @endif
                    </td>

                    <!-- Afternoon Time In -->
                    <td class="font1 center">
                        @if (($period == 0) || ($period == 3) || ($period == 1 && $day <= 15) || ($period == 2 && $day > 15))
                            @if (isset($time_arrays[$day]['in']) && count($time_arrays[$day]['in']) > 0)
                                @php
                                    $noonInTime = new DateTime(end($time_arrays[$day]['in']));
                                @endphp
                                @if ($noonInTime > $noonInStart && $noonInTime < $afternoonOutStart)
                                    {{ $noonInTime->format('g:i') }}
                                @endif
                            @endif
                        @endif
                    </td>
                    
                    <!-- Afternoon Time Out -->
                    <td class="font1 center">
                        @if (($period == 0) || ($period == 3) || ($period == 1 && $day <= 15) || ($period == 2 && $day > 15))
                            @if (isset($time_arrays[$day]['out']) && count($time_arrays[$day]['out']) > 0)
                                @php
                                    $afternoonOutTime = new DateTime(end($time_arrays[$day]['out']));
                                @endphp
                                @if ($afternoonOutTime > $afternoonOutStart)
                                    {{ $afternoonOutTime->format('g:i') }}
                                @endif
                            @endif
                        @endif
                    </td>

                    <!-- Overtime Columns -->
                    <td></td>
                    <td></td>
                </tr>
                @endfor

            </tbody>

        </table>
        <p style="font-size: 9.5px; text-align: left;">
            <span style="margin-left: 20px;">I <b>CERTIFY</b></span> on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
        </p>    
        <div>
            <span class="font"><b>@if(isset($employee)) {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(substr($employee->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->suffix)) }}@endif</b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Employee’s Signature</span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <div style="m; ">
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: left; margin-top: -25px;"><b>VERIFIED</b> as to the prescribed office hours:</span>
        </div>
        <div>
            <span class="font"><b>@if(isset($supervisor))
                @if(in_array($supervisor->prefix, ['Dr.', 'Engr.']))
                    {{ strtoupper(ucwords($supervisor->prefix)) }}
                @endif
                {{ strtoupper(ucwords($supervisor->fname)) }} {{ strtoupper(substr($supervisor->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($supervisor->lname)) }}{{ ($supervisor->suffix) ? ', '.$supervisor->suffix : ''}}
                @if(!in_array($supervisor->prefix, ['Dr.', 'Engr.']))
                    {{ ', '.strtoupper(ucwords($supervisor->prefix)) }}
                @endif
                @endif
            </b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Immediate Supervisor’s Signature </span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <p style="font-size: 8px; text-align: center;">
            Doc Control Code: CPSU-F-HRMO-03-REV01 Effective Date: 09/04/2024 Page No:  1 of 1
        </p>
    </div>
    <div class="column2"> 
        <img src="{{ pdf_image('Uploads/dtr-header.png') }}" width="110%" class="mt-5" alt="Header Image">
        <div>
            <span class="font">Name of Employee :</span> <span class="header" style="relative; display: inline-block; width: 73%; text-align: left;">&nbsp; @if(isset($employee)) {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(substr($employee->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->suffix)) }}@endif</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Office : </span>
            <span class="header" style="position: relative; display: inline-block; width: 67.5%; text-align: left;">
                &nbsp;
                {{ isset($employee) ? strtoupper(ucwords($employee->office_name)) : '' }}
            </span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">For the month of : </span> <span class="header" style="relative; display: inline-block; width: 37%; text-align: left;">&nbsp;{{ isset($employee) ? $startDate : '' }} - {{ isset($employee) ? $endDate : '' }}</span>, <span class="header" style="relative; display: inline-block; width: 36%; text-align: left;">&nbsp;{{ isset($employee) ? $year : '' }}</span>
        </div>
        <div style="margin-top: -9px;">
            <span class="font">Official Hour of Arrival in Regular Days : </span> <span class="header" style="relative; display: inline-block; width: 49.3%; text-align: left;">&nbsp; 8:00 AM - 12:00 PM || 1:00 PM - 5:00 PM</span>
        </div>
        @if($countnmatch > 0)
            <div style="margin-top: -2px;">
                <span class="header" style="relative; display: inline-block; width: 98%; text-align: left; font-size: 8px;">&nbsp; {{ $first_two_non_matching_days[0] ?? ''}} @if(isset($first_two_non_matching_days[1]))||@endif {{ $first_two_non_matching_days[1] ?? ''}}</span>
            </div>
        @endif
        @if($countnmatch > 2)
            <div style="margin-top: -8px;">
                <span class="header" style="relative; display: inline-block; width: 98%; text-align: left; font-size: 8px;">&nbsp; {{ $third_and_fourth_non_matching_days[0] ?? ''}} @if(isset($third_and_fourth_non_matching_days[1]))||@endif {{ $third_and_fourth_non_matching_days[1] ?? ''}}</span>
            </div>
        @endif
        @if($countnmatch == 5)
            <div style="margin-top: -9px;">
                <span class="header" style="relative; display: inline-block; width: 98%; text-align: left; font-size: 8px;">&nbsp; {{ $last_non_matching_day ?? ''}}</span>
            </div>
        @endif
        <div style="margin-top: {{ ($countnmatch == 0) ? '-9px;' : '' }}{{ ($countnmatch > 0 && $countnmatch < 3) ? '-18px;' : '' }}{{ ($countnmatch > 2 && $countnmatch < 5) ? '-20px;' : '' }}{{ ($countnmatch == 5) ? '-20px;' : '' }}">
            <span class="font">Saturdays : </span> <span class="header mb-2" style="relative; display: inline-block; width: 82.6%; text-align: left;">&nbsp;</span>
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
                @php 
                    $morningCutoff = new DateTime('10:00');
                    $morningOutStart = new DateTime('10:00');
                    $morningOutEnd = new DateTime('13:30');
                    $noonInStart = new DateTime('11:00');
                    $noonInEnd = new DateTime('11:30');
                    $afternoonOutStart = new DateTime('13:31');
                    $afternoonOutEnd = new DateTime('18:00');
                @endphp
                @for ($day = 1; $day <= 31; $day++)
                <tr>
                    <th class="font center" width="15">{{ $day }}</th>

                    <!-- Morning Time In -->
                    <td class="font1 center">
                        @if (($period == 0) || ($period == 3) || ($period == 1 && $day <= 15) || ($period == 2 && $day > 15))
                            @if (isset($time_arrays[$day]['in']) && count($time_arrays[$day]['in']) > 0)
                                @php
                                    $inTime = new DateTime(reset($time_arrays[$day]['in']));
                                @endphp
                                @if ($inTime < $morningCutoff)
                                    {{ $inTime->format('g:i') }}
                                @endif
                            @endif
                        @endif
                    </td>
                    
                    <!-- Morning Time Out -->
                    <td class="font1 center">
                        @if (($period == 0) || ($period == 3) || ($period == 1 && $day <= 15) || ($period == 2 && $day > 15))
                            @if (isset($time_arrays[$day]['out']) && count($time_arrays[$day]['out']) > 0)
                                @php
                                    $outTime = new DateTime(reset($time_arrays[$day]['out']));
                                @endphp
                                @if ($outTime > $morningOutStart && $outTime < $morningOutEnd)
                                    {{ $outTime->format('g:i') }}
                                @endif
                            @endif
                        @endif
                    </td>

                    <!-- Afternoon Time In -->
                    <td class="font1 center">
                        @if (($period == 0) || ($period == 3) || ($period == 1 && $day <= 15) || ($period == 2 && $day > 15))
                            @if (isset($time_arrays[$day]['in']) && count($time_arrays[$day]['in']) > 0)
                                @php
                                    $noonInTime = new DateTime(end($time_arrays[$day]['in']));
                                @endphp
                                @if ($noonInTime > $noonInStart && $noonInTime < $afternoonOutStart)
                                    {{ $noonInTime->format('g:i') }}
                                @endif
                            @endif
                        @endif
                    </td>

                    <!-- Afternoon Time Out -->
                    <td class="font1 center">
                        @if (($period == 0) || ($period == 3) || ($period == 1 && $day <= 15) || ($period == 2 && $day > 15))
                            @if (isset($time_arrays[$day]['out']) && count($time_arrays[$day]['out']) > 0)
                                @php
                                    $afternoonOutTime = new DateTime(end($time_arrays[$day]['out']));
                                @endphp
                                @if ($afternoonOutTime > $afternoonOutStart)
                                    {{ $afternoonOutTime->format('g:i') }}
                                @endif
                            @endif
                        @endif
                    </td>

                    <!-- Overtime Columns -->
                    <td></td>
                    <td></td>
                </tr>
                @endfor
            </tbody>

        </table>
        <p style="font-size: 9.5px; text-align: left;">
            <span style="margin-left: 20px;">I <b>CERTIFY</b></span> on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
        </p> 
        <div>
            <span class="font"><b>@if(isset($employee)) {{ strtoupper(ucwords($employee->fname)) }} {{ strtoupper(substr($employee->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($employee->lname)) }} {{ strtoupper(ucwords($employee->suffix)) }}@endif</b></span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Employee’s Signature</span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <div style="m; ">
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: left; margin-top: -25px;"><b>VERIFIED</b> as to the prescribed office hours:</span>
        </div>
        <div>
            <span class="font">
            <b>@if(isset($supervisor))
                @if(in_array($supervisor->prefix, ['Dr.', 'Engr.']))
                    {{ strtoupper(ucwords($supervisor->prefix)) }}
                @endif
                {{ strtoupper(ucwords($supervisor->fname)) }} {{ strtoupper(substr($supervisor->mname, 0, 1)) . '.' }} {{ strtoupper(ucwords($supervisor->lname)) }}{{ ($supervisor->suffix) ? ', '.$supervisor->suffix : ''}}
                @if(!in_array($supervisor->prefix, ['Dr.', 'Engr.']))
                    {{ ', '.strtoupper(ucwords($supervisor->prefix)) }}
                @endif
                @endif
            </b>
            </span><br>
            <span class="header" style="relative; display: inline-block; width: 50%; text-align: center;"></span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -25px;">Immediate Supervisor’s Signature </span>
            <span class="font" style="relative; display: inline-block; width: 100%; text-align: center; margin-top: -37px;">over Printed Name</span>
        </div>
        <p style="font-size: 8px; text-align: center;">
            Doc Control Code: CPSU-F-HRMO-03-REV01 Effective Date:  09/04/2024     Page No:  1 of 1
        </p>
    </div>
</body>
</html>
