<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tardiness & Undertime</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 0;
                text-align: center;
                font-size: 10px;
            }
            table {
                width: 100%;
                border: 1px solid rgb(255, 255, 255);
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid black;
                padding: 0px;
                text-align: left;
            }
            .text-center{
                text-align: center;
            }
            .pl-2{
                padding-left: 3px;
            }
            .text-danger{
                color: red;
            }
            .p{
                padding: 1px;
            }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th rowspan="2" colspan="2"></th>
                    <th colspan="4" class="text-center">TIREDNESS</th>
                    <th colspan="4" class="text-center">UNDERTIME</th>
                </tr>
                <tr>
                    <th colspan="2" class="text-center">MORNING</th>
                    <th colspan="2" class="text-center">NOON</th>
                    <th colspan="2" class="text-center">MORNING</th>
                    <th colspan="2" class="text-center">AFTERNOON</th>
                </tr>
                <tr>
                    <th></th>
                    <th class="text-center" width="130">NAME</th>
                    <th class="text-center" width="50">DAYS</th>
                    <th class="text-center" width="50">TIME</th>
                    <th class="text-center" width="50">DAYS</th>
                    <th class="text-center" width="50">TIME</th>
                    <th class="text-center" width="50">DAYS</th>
                    <th class="text-center" width="50">TIME</th>
                    <th class="text-center" width="50">DAYS</th>
                    <th class="text-center" width="50">TIME</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($dtrRecords as $record)
                    <tr>
                        <th class="text-center p">{{ $no++ }}</th>
                        <th class="pl-2">{{ $record->lname }} {{ $record->prefix }} {{ $record->fname }} {{ isset($record->mname) ? substr($record->mname, 0, 1).'.' : '' }}</th>
                        <td class="text-center {{ ($record->morning_count >= 10) ? 'text-danger' : '' }}">{{ $record->morning_count }}</td>
                        <td class="text-center">{{ $record->total_hours }} : {{ $record->remaining_minutes }}</td>
                        <td class="text-center {{ ($record->noon_count >= 10) ? 'text-danger' : '' }}">{{ $record->noon_count }}</td>
                        <td class="text-center">{{ floor($record->total_noon_minutes / 60) }} : {{ $record->total_noon_minutes % 60 }}</td>
                        <td class="text-center {{ ($record->undertime_count >= 10) ? 'text-danger' : '' }}">{{ $record->undertime_count }}</td>
                        <td class="text-center">{{ floor($record->total_undertime_minutes / 60) }} : {{ $record->total_undertime_minutes % 60 }}</td>
                        <td class="text-center {{ ($record->afternoon_undertime_count >= 10) ? 'text-danger' : '' }}">{{ $record->afternoon_undertime_count }}</td>
                        <td class="text-center">{{ floor($record->total_afternoon_undertime_minutes / 60) }} : {{ $record->total_afternoon_undertime_minutes % 60 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
