<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>ATTENDANCE SHEET</title>
  <style>
    /* Page styling */
    @page {
      margin-top: 20px;
      margin-bottom: 40px;  /* Ensure enough space for footer */
    }

    body {
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
      min-height: 100%; /* Allow content to stretch the page */
      padding-bottom: 50px; /* To create space for footer */
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid #000000;
      padding: 2px;
      text-align: left;
    }

    td {
      font-size: 12px;
    }

    .text-center {
      text-align: center;
    }

    .bnone {
      border: none !important;
    }

    /* Footer Styling */
    .footer-content {
      text-align: center;
      font-size: 10px;
      padding: 5px 0;
      position: absolute;
      bottom: 0;
      width: 100%;
      margin-top: 20px; /* Space above footer */
    }

    /* Page break styling */
    .page-break {
      page-break-before: always;
    }

  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(asset('Uploads/attendance-header.png'))) }}" alt="Logo" style="width: 100%; ">
    <table>
      <tr>
        <td class="bnone" colspan="6" style="padding: 0; border: none; text-align: left;">
          <b>Event:</b> <span style="border-bottom: 1px solid #000; display: inline-block; width: 94.3%; margin-bottom: -4px;">{{ strtoupper($eventsdatas->title) }}</span><br>
          <b>Date:</b> <span style="border-bottom: 1px solid #000; display: inline-block; width: 30%; margin-bottom: -4px;">{{ strtoupper(\Carbon\Carbon::parse($eventsdatas->start)->format('F d, Y')) }}</span>
          <b>Venue:</b> <span style="border-bottom: 1px solid #000; display: inline-block; width: 58.8%; margin-bottom: -4px;">{{ strtoupper($eventsdatas->venue) }}</span>
          <b>Organizing Department/s:</b> <span style="border-bottom: 1px solid #000; display: inline-block; width: 79%; margin-bottom: -4px;">{{ strtoupper($eventsdatas->org_dept) }}</span><br><br>
        </td>        
      </tr>
    </table>
  </div>

  <!-- Table Content -->
  @php 
    $pageNumber = 1;
  @endphp

  @foreach($chunkedEvents as $events)
    <table>
      <thead>
        <tr>
          <td class="text-center" rowspan="2" colspan="2">NAME</td>
          <td class="text-center" rowspan="2" width="200">POSITION/<br>DESIGNATION/<br>FUNCTIONAL AREA</td>
          <td class="text-center" colspan="2">SIGNATURE</td>
        </tr>
        <tr>
          <td class="text-center" width="46">IN</td>
          <td class="text-center" width="46">OUT</td>
        </tr>
      </thead>
      <tbody>
        @foreach($events as $event)
          <tr>
            <td class="text-center" width="20">{{ $loop->iteration }}</td>
            <td width="140">{{ strtoupper($event->lname).' '.strtoupper($event->fname) }}</td>
            <td width="130" class="text-left pl-2">{{ ($event->emp_status == 1) ? strtoupper($event->position) : 'OFFICE STAFF'}}</td>
            
            <td class="text-center">
              @if($event->in)
                {{ \Carbon\Carbon::parse($event->in)->format('h:i A') }} <!-- 12-hour format with AM/PM -->
              @else
               
              @endif
            </td>
            
            <td class="text-center">
              @if($event->out)
                {{ \Carbon\Carbon::parse($event->out)->format('h:i A') }} <!-- 12-hour format with AM/PM -->
              @else
                
              @endif
            </td>
            
          </tr>
        @endforeach
      </tbody>      
    </table>

    <!-- Footer (This will appear on every page) -->
    <div class="footer-content">
      <span>Doc Control Code: CPSU-F-QA-19 Effective Date: 09/12/20218</span> <span>Page {{ $pageNumber }} of {{ count($chunkedEvents) }}</span>
    </div>

    <!-- Force page break after every set of 20 rows -->
    @if (!$loop->last)
      <div class="page-break"></div>
    @endif

    @php 
      $pageNumber++; 
    @endphp
  @endforeach

</body>
</html>
