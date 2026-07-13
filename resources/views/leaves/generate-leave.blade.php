<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Leave Application</title>
    <style>
        .table{
            width: 100% !important;
            background-color: transparent;
            border: 1px solid black;
            border-collapse: collapse;            
            padding: 0px;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px; 
        }
        .table1{
            margin-top: 0px !important;
        }
        .table-inside{
            font-size: 8px;
            width: 48.5% !important;
            position: absolute; 
            z-index: 999; 
            top: -295px; 
            left: 18px;
        }
        .bordered{
            border: 1px solid black;
        }
        .table1 {
            border-top: 0;
        }
        .fh{
            height: 26px !important;
        }
        .ml1{
            margin-left: 12px;
            font-weight: 700;
        }
        .b{
            font-weight: 700;
        }
        .b-top{
            border-top: 1px solid black;
        }
        .font1{
            font-size: 10px;
        }
        .font2{
            font-size: 12px;
        }
        .b-bottom{
            border-bottom: 1px solid black;
        }
        .center-center{
            align-items: center;
            text-align: center;
            vertical-align: middle;
            font-weight: 700;
        }
        .details{
            font-size: 10px !important;
        }
        .checkbox1 {
            transform: scale(0.8);
            margin-bottom: -8.5px;
        }
        .checkbox-label{
            font-size: 7.5px;
        }
        .vlt{
            vertical-align: top;
        }
        .padd-check{
            margin-bottom: 3px;
        }
        .text-center{
            text-align: center;
            font-weight: 700;
        }
    </style>
</head>
<body style="margin-top: -10px">
    <img src="{{ asset('Uploads/leave-header.png') }}" style="width: 100%; margin-bottom: 8px;">

    {{-- 
    <img src="{{ $leaveApplication->approver_esign }}" class="signature" alt="Floating image" style="position: fixed; top: 83.7%; left: 45%; transform: translate(-50%, -50%); width: 350px; z-index: 9999;"> --}}

    <table class="table">
        <thead>
            <tr><td colspan="6" class="bordered"></td></tr>
            <tr>
                <td width="35%">1. OFFICE/DEPARTMENT</td>
                <td width="13%">2. NAME:</td>
                <td width=""></td>
                <td width="18.6%">(Last)</td>
                <td width="18.6%">(First)</td>
                <td width="18.6%">(Middle)</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fh ml1 font1"><span class="ml1">{{ $leaveApplication->office->office_name ?? '' }}</span></td>
                <td></td>
                <td></td>
                <td class="fh b font1">{{ strtoupper($leaveApplication->lname) }} {{ strtoupper($leaveApplication->suffix) }}</td>
                <td class="fh b font1">{{ strtoupper($leaveApplication->fname) }}</td>
                <td class="fh b font1">{{ isset($leaveApplication->mname) ? substr($leaveApplication->mname, 0, 1).'.' : ''}}</td>
            </tr>
            <tr>
                <td colspan="1" class="fh b-top">2. DATE OF FILING 
                    <span style="width: 95px; display: inline-block; margin-bottom: -4px;  border-bottom: 1px solid black;">
                        <center><b>{{ isset($leaveApplication->date_filing) ? strtoupper(\Carbon\Carbon::parse($leaveApplication->date_filing)->format('M d, Y')) : '' }}</b></center>
                    </span>
                </td>
                <td colspan="4" class="fh b-top">4. POSITION 
                    <span style="width: 180px; display: inline-block; margin-bottom: -4px;  border-bottom: 1px solid black;">
                        <center><b>{{ strtoupper($leaveApplication->position) }}</b></center>
                    </span>
                </td> 
                <td colspan="1" class="fh b-top"><span style="margin-left: -35px;">5. SALARY <span style="font-size: 10px !important;">₱</span></span>
                    <span style="width: 80px; display: inline-block; margin-bottom: -4px;  border-bottom: 1px solid black;">
                        <b>{{ number_format($leaveApplication->salary, 2) }}</b>  
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
    <table class="table table1">
        <thead>
            <tr>
                <td colspan="3"></td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td colspan="6" class="bordered center-center font2">6. DETAILS OF APPLICATION</td>
            </tr>
            <tr>
                <td colspan="3" class="bordered details vlt" width="116">
                    <span>6.A TYPE OF LEAVE TO BE AVAILED OF</span><br>
                    <div style="font-size: 8px !important; margin-bottom: 10px; margin-top: 10px;">
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 1) checked @endif> <b>Vacation Leave</b> <span class="checkbox-label">(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 2) checked @endif> <b>Mandatory/Forced Leave</b> <span class="checkbox-label"> (Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 3) checked @endif> <b>Sick Leave</b> <span class="checkbox-label">(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 4) checked @endif> <b>Maternity Leave</b> <span class="checkbox-label">(R.A No. 11210/IRR issued by CSC, DOLE and SSS)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 5) checked @endif> <b>Paternity Leave</b> <span class="checkbox-label">(R.A No. 8187/CSC MC No. 71,s. 1998, as amended)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 6) checked @endif> <b>Special Privilege Leave</b> <span class="checkbox-label">(Sec. 21, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 7) checked @endif> <b>Solo Parent Leave</b> <span class="checkbox-label">(R.A. No. 8972/CSC MC No. 8, s. 2004)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 8) checked @endif> <b>Study Leave</b> <span class="checkbox-label">(Sec. 68, Rule XVI, Omnibus Rules Implementing E.O No. 292)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 9) checked @endif> <b>10-Day VAWC Leave</b> <span class="checkbox-label">(R.A No. 9262/CSC MO No. 15,s. 2005)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 10) checked @endif> <b>Rehabilitation Privilege</b> <span class="checkbox-label">(Sec. 55, Rule XVI, omnibus Rules Implementing E.O No. 292)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 11) checked @endif> <b>Special Leave Benefits for Women</b> <span class="checkbox-label">(R.A No. 9710/CSC MC No. 25,s. 2010)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 12) checked @endif> <b>Special Emergency (Calamity) Leave</b> <span class="checkbox-label">(CSC MC No. 2,s. 2012, as amended)</span></div>
                        <div class="padd-check"><input type="checkbox" class="checkbox1" @if($leaveApplication->leave_type == 13) checked @endif> <b>Adoption Leave</b> <span class="checkbox-label">(R.A. No. 8552)</span></div>
                        <div style="margin-top: 20px;">&nbsp;Others: <br></div>
                        &nbsp;<span style="width: 58%;  display: inline-block; margin-bottom: -8px;  border-bottom: 1px solid black; text-align: center;">
                        @php
                            // Extract start date from date_range
                            $dates = explode(' to ', $leaveApplication->date_range);
                            $startDate = new DateTime($dates[0]);
                            $filingDate = new DateTime($leaveApplication->date_filing);
                            // Check if Emergency applies
                            $isEmergency = $startDate < $filingDate;
                        @endphp

                        @if($leaveApplication->leave_type == 14) <b>Vacation Service Credit</b> @endif 
                        @if($leaveApplication->leave_type == 15)
                            <b>Wellness Leave</b>
                            @if($isEmergency)
                                &nbsp;"Emergency"
                            @endif
                        @endif
                    </div>
                </td>
                <td colspan="3" class="bordered details vlt" width="100">
                    <span>6.B DETAILS OF LEAVE</span><br>
                    <div style="font-size: 8px !important; margin-bottom: 10px; margin-top: 10px; margin-left: 14px;">
                        <div style="margin-bottom: 4px;"><i>&nbsp;In case of Vacation/Special Privilege Leave</i></div>
                        <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 1) checked @endif> Within the Philippines 
                        <span style="width: 60%; display: inline-block; margin-bottom: -3px;  border-bottom: 1px solid black;">
                            @if($leaveApplication->leave_purpose == 1) {{ strtoupper($leaveApplication->leave_detail) }} @endif
                        </span><br>

                        <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 2) checked @endif> Abroad (Specify) 
                        <span style="width: 66.6%; display: inline-block; margin-bottom: -3px;  border-bottom: 1px solid black;">
                            @if($leaveApplication->leave_purpose == 2) {{ strtoupper($leaveApplication->leave_detail) }} @endif
                        </span><br>

                        <div style="margin-bottom: 4px; margin-top: 4px;"><i>&nbsp;In case of Sick Leave</i></div>
                        <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 3) checked @endif> In Hospital (Specify Illness) 
                        <span style="width: 53.2%; display: inline-block; margin-bottom: -3px;  border-bottom: 1px solid black;">
                            @if($leaveApplication->leave_purpose == 3) {{ strtoupper($leaveApplication->leave_detail) }} @endif
                        </span><br>

                        @php
                            $leaveDetail = explode(' ', strtoupper($leaveApplication->leave_detail));
                            $firstThreeWords = implode(' ', array_slice($leaveDetail, 0, 4));
                            $remainingWords = implode(' ', array_slice($leaveDetail, 4));
                        @endphp

                        <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 4) checked @endif> Out Patient (Specify Illness) 
                        <span style="width: 52.3%; display: inline-block; margin-bottom: -3px;  border-bottom: 1px solid black;">
                            {{ ($leaveApplication->leave_purpose == 4) ? strtoupper($firstThreeWords) : '' }}
                        </span><br>

                        <br>&nbsp;<span style="width: 94.1%; display: inline-block;  border-bottom: 1px solid black;">{{ ($leaveApplication->leave_purpose == 4) ? strtoupper($remainingWords) : '' }}</span><br>
                        <div style="margin-bottom: 4px; margin-top: 4px;"><i>&nbsp;In case of Special Leave benefits for Women</i></div>
                        (Specify Illness) <span style="width: 73.53%; display: inline-block; margin-bottom: -3px;  border-bottom: 1px solid black;"></span><br>
                        <br>&nbsp;<span style="width: 94.1%; display: inline-block;  border-bottom: 1px solid black;"></span><br>
                        <div style="margin-bottom: 4px; margin-top: 4px;"><i>&nbsp;In case Study Leave</i></div>
                        <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 5) checked @endif> Completion of Master's Degree</span><br>
                        <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 6) checked @endif> BAR/Board Examination Review<br>
                        <div style="margin-bottom: 4px; margin-top: 4px;"><i>&nbsp;Other purpose:</i></div>
                        <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 7) checked @endif> Monetization of Leave Credits</span><br>
                        <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 8) checked @endif> Terminal Leave<br>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3" class="bordered details vlt" width="116">
                    @php
                        if (strpos($leaveApplication->date_range, 'to') !== false) {
                            // Split date range into start and end date
                            [$startDate, $endDate] = explode(' to ', $leaveApplication->date_range);
                            
                            $formattedStartDate = \Carbon\Carbon::parse($startDate)->format('M d, Y');
                            $formattedEndDate = \Carbon\Carbon::parse($endDate)->format('M d, Y');
                        } else {
                            // Only a single date is provided
                            $formattedStartDate = \Carbon\Carbon::parse($leaveApplication->date_range)->format('M d, Y');
                            $formattedEndDate = null;
                        }
                    @endphp
                    
                    <span>6.C NUMBER OF WORKING DAYS APPLIED FOR</span><br>
                    <div style="font-size: 8px !important; margin-bottom: 10px; margin-top: 5px; margin-left: 20px;">
                        <span style="width: 66.6%; display: inline-block; font-size: 10px !important; border-bottom: 1px solid black;">
                            <span style="color: white;">{{ ($formattedEndDate) ? '' : '.' }}</span>
                            <b>{{ $leaveApplication->days }}</b>
                        </span><br>
                        <span style="margin-top: 4px;">INCLUSIVE DATES</span><br><br>
                        <span style="width: 66.6%; margin-top: -5.4px; font-size: 10px !important; display: inline-block; border-bottom: 1px solid black;">
                            <span style="color: white;">{{ ($formattedEndDate) ? '' : '.' }}</span>
                            <b>{{ strtoupper($formattedStartDate) }}</b>
                            @if($formattedEndDate)
                                <b>- {{ strtoupper($formattedEndDate) }}</b>
                            @endif
                        </span>
                    </div>
                </td>
                <td colspan="3" class="bordered details vlt" width="100">
                    <span>6.D COMMUTATION</span><br>
                    <div style="position: relative;">
                        <div style="position: relative;">
                            <div style="font-size: 8px !important; margin-bottom: 0px; margin-top: 3px; margin-left: 14px;">
                                <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose !== 7 && $leaveApplication->leave_purpose !== 8) checked @endif> Not Requested<br>
                                <input type="checkbox" class="checkbox1" @if($leaveApplication->leave_purpose == 7 || $leaveApplication->leave_purpose == 8) checked @endif> Requested<br>                        
                                <center>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="width: 94%; display: inline-block;  margin-bottom: -14px;  border-bottom: 1px solid black;"></span></center>
                                @if($leaveApplication->emp_esign == 2)
                                    <img src="{{ $leaveApplication->employee_esign }}" class="signature" width="100" height="40" style="position: absolute; top: 15px; left: 50%; transform: translateX(-50%); z-index: 10;">
                                @endif
                                <center>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>(Signature of Applicant)</center>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td colspan="6" class="bordered center-center font2">7. DETAILS OF ACTION ON APPLICATION</td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td colspan="3" class="bordered details vlt" width="116" style="height: 100px !important;">
                    <span>7.A CERTIFICATION OF LEAVE CREDITS</span><br>
                    <center>As of <span style="width: 45.9%; display: inline-block;  border-bottom: 1px solid black;"><b>{{ isset($leaveApplication->as_of) ? strtoupper(\Carbon\Carbon::parse($leaveApplication->as_of)->format('M d, Y')) : '' }}</b></span></center>
                    <div style="margin-top: 17%; margin-left: 29%;">
                        <span style="font-size: 8px !important; color: white;">.</span>
                    </div>
                    <div style="margin-top: -2.10%">
                    <span style="font-size: 7px;  margin-top: {{ $leaveApplication->oic == null ? '-2.35%;' : '3%;' }}; float: right; margin-right: 10px;">
                        <em>Total earned  and balances are for reconciliation.</em>
                    </span>
                    </div>
                    <div style="{{ $leaveApplication->oic == null ? 'margin-top: 2.45%;' : 'margin-top: 7.50%;' }}">
                        @if($leaveApplication->hr_sign == 2)
                            <img src="{{ $leaveApplication->hr_esign }}" class="signature" width="100" height="40" style="position: absolute; top: {{ $leaveApplication->oic == null ? '71%;' : '73%;' }} left: 26.7%; transform: translateX(-50%); z-index: 10;">
                        @endif
                        <center><span class="font1" style="width: 90%; display: inline-block; margin-top:7.3;  border-bottom: 1px solid black;"><b>{{ strtoupper($leaveApplication->hr_fname) }} {{ isset($leaveApplication->hr_mname) ? substr($leaveApplication->hr_mname, 0, 1).'.' : ''}} {{ strtoupper($leaveApplication->hr_lname) }} {{ strtoupper($leaveApplication->hr_suffix) }}{{ ($leaveApplication->hr_prefix) ? strtoupper(', '.$leaveApplication->hr_prefix) : '' }}</b></span></center>
                        <center>Human Resource Management Officer</center>
                    </div>
                </td>
                <td colspan="3" class="bordered details vlt" width="100">
                    @php
                        $leaveDetail = explode(' ', strtoupper($leaveApplication->remarks_details));
                    
                        $firstFiveWords = implode(' ', array_slice($leaveDetail, 0, 5));
                    
                        $nextSixWords = implode(' ', array_slice($leaveDetail, 5, 6));
                    
                        $thirdSixWords = implode(' ', array_slice($leaveDetail, 11, 6));
                    
                        $remainingWords = implode(' ', array_slice($leaveDetail, 17));
                    @endphp
                
                    <span>7.B RECOMMENDATION</span><br>
                    <div style="font-size: 9px !important; margin-bottom: 0px; margin-top: 3px; margin-left: 14px;">
                        <input type="checkbox" class="checkbox1" @if($leaveApplication->remarks_stat == 0) checked @endif> For Approval<br>
                        <input type="checkbox" class="checkbox1" @if($leaveApplication->remarks_stat !== 0) checked @endif> For disapproval due to <span style="width: 56.8%; display: inline-block; margin-bottom: -3px;  border-bottom: 1px solid black;"><span style="color: white;">.</span> {{ ($firstFiveWords && $leaveApplication->remarks_stat !== 0) ? $firstFiveWords : '' }} </span></div>
                        <div style="margin-top: 2px; margin-left: 33px;"><span style="width: 95.7%; display: inline-block;  border-bottom: 1px solid black;"><span style="color: white;">.</span> {{ ($nextSixWords && $leaveApplication->remarks_stat !== 0) ? $nextSixWords : '' }}</span></div>
                        <div style="margin-top: 2px; margin-left: 33px;"><span style="width: 95.7%; display: inline-block;  border-bottom: 1px solid black;"><span style="color: white;">.</span> {{ ($thirdSixWords && $leaveApplication->remarks_stat !== 0) ? $thirdSixWords : '' }}</span></div>
                        @if($leaveApplication->oic == null)
                        <div style="margin-top: 7px; margin-left: 33px;"><span style="width: 95.7%; display: inline-block;  border-bottom: 1px solid black;"><span style="color: white;">.</span> {{ ($remainingWords && $leaveApplication->remarks_stat !== 0) ? $remainingWords : '' }}</span></div>
                        @endif
                        @if($leaveApplication->oic != null)
                            <img src="{{ $leaveApplication->oic_esign }}" class="signature" width="100" height="40" style="position: absolute; top: 68.2%; left: 77%; transform: translateX(-50%); z-index: 10;">
                            <div style="margin-top: 12px; margin-left: 33px;"><span class="font1" style="width: 95.7%; display: inline-block;  border-bottom: 1px solid black;"><center><span style="color: white;">.</span><b><span style="padding-right: 26px;">for: {{ strtoupper($leaveApplication->oic_fname) }} {{ isset($leaveApplication->oic_mname) ? substr($leaveApplication->oic_mname, 0, 1).'.' : '' }} {{ strtoupper($leaveApplication->oic_lname) }} {{ strtoupper($leaveApplication->oic_suffix) }}
                                {{-- {{ ($leaveApplication->oic_prefix) ? strtoupper(', '.$leaveApplication->oic_prefix) : '' }} --}}
                            </span></b></span></center></div>
                            <center>Officer-In-Charge</center>
                        @endif
                        @if($leaveApplication->sup_sign == 2 && ($leaveApplication->supervisor !== $leaveApplication->approver))
                            <img src="{{ $leaveApplication->supervisor_esign }}" class="signature" width="100" height="40" style="position: absolute; top: 70.2%; left: 77%; transform: translateX(-50%); z-index: 10;">
                        @endif
                        <div style="margin-top: 12px; margin-left: 33px;"><span class="font1" style="width: 95.7%; display: inline-block;  border-bottom: 1px solid black;"><center><span style="color: white;">.</span><b><span style="padding-right: 26px;">{{ strtoupper($leaveApplication->supervisor_fname) }} {{ isset($leaveApplication->supervisor_mname) ? substr($leaveApplication->supervisor_mname, 0, 1).'.' : '' }} {{ strtoupper($leaveApplication->supervisor_lname) }} {{ strtoupper($leaveApplication->supervisor_suffix) }}
                            {{-- {{ ($leaveApplication->supervisor_prefix) ? strtoupper(', '.$leaveApplication->supervisor_prefix) : '' }} --}}
                        </span></b></span></center></div>
                        <center>Immediate Supervisor</center>
                        <center>(Signature over Printed Name)</center>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td colspan="3"></td>
            </tr>
            @php
                $leave_days = $leaveApplication->days - $leaveApplication->day_wpay;
                $is_vl_sl = in_array($leaveApplication->leave_type, [1, 2]);
                $leavetype = $leaveApplication->leave_type;
                $emp_esign = $leaveApplication->emp_esign;

                $totalvl = $leaveApplication->total_vl;
                $totalsl = $leaveApplication->total_sl;

                $totalremain = $leave_days - $leaveApplication->total_sl;
            @endphp
            <tr>
                <td colspan="3" class="bordered details vlt" style="height: 120px !important; border-right: none !important; border-bottom: none !important;">
                    <span>7.C APPROVED FOR:</span><br>
                    <span style="width: 11.5%; margin-left: 20px; display: inline-block; text-align: center; border-bottom: 1px solid black;"><center><b>{{ $leave_days }}</b></center></span> days with pay <br>
                    <span style="width: 11.5%; margin-left: 20px; display: inline-block; text-align: center; border-bottom: 1px solid black;"><center><b>{{ $leaveApplication->day_wpay }}</b></center></span> days without pay<br>
                    <span style="width: 11.5%; margin-left: 20px; display: inline-block; text-align: center; border-bottom: 1px solid black;"></span> others (Specify)<br>
                </td>
                <td colspan="3" class="bordered details vlt" style="height: 120px !important; border-left: none !important; border-bottom: none !important;">
                    @php
                        $leaveDetail1 = explode(' ', strtoupper($leaveApplication->remarks_details1));
                    
                        $firstFiveWords1 = implode(' ', array_slice($leaveDetail1, 0, 5));
                    
                        $nextSixWords1 = implode(' ', array_slice($leaveDetail1, 5, 6));
                    
                        $thirdSixWords1 = implode(' ', array_slice($leaveDetail1, 11, 6));
                    
                        $remainingWords1 = implode(' ', array_slice($leaveDetail1, 17));
                    @endphp
                    <span>7.D DISAPPROVED DUE TO:</span><br>
                    <span style="width: 90%; margin-left: 20px; display: inline-block; text-align: left; border-bottom: 1px solid black;"><span style="color: white;">.</span> {{ ($firstFiveWords1 && $leaveApplication->remarks_stat1 !== 0) ? $firstFiveWords1 : '' }} {{ ($nextSixWords1 && $leaveApplication->remarks_stat1 !== 0) ? $nextSixWords1 : '' }}</span>
                    <span style="width: 90%; margin-left: 20px; display: inline-block; text-align: left; border-bottom: 1px solid black;"><span style="color: white;">.</span> {{ ($thirdSixWords1 && $leaveApplication->remarks_stat1 !== 0) ? $thirdSixWords1 : '' }}</span>
                    <span style="width: 90%; margin-left: 20px; display: inline-block; text-align: left; border-bottom: 1px solid black;"><span style="color: white;">.</span> {{ ($remainingWords1 && $leaveApplication->remarks_stat1 !== 0) ? $remainingWords1 : '' }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="6">
                    @if($leaveApplication->approver_sign == 2)
                        <img src="{{ $leaveApplication->approver_esign }}" class="signature" width="100" height="40" style="position: absolute; top: 86.5%; left: 50%; transform: translateX(-50%); z-index: 10;">
                    @endif
                    <center>
                        <span class="font1" style="width: 28%; display: inline-block; border-bottom: 1px solid black;">
                            <b>
                                {{
                                    strtoupper($leaveApplication->approver_fname) . ' ' .
                                    (isset($leaveApplication->approver_mname) ? strtoupper(substr($leaveApplication->approver_mname, 0, 1)) . '. ' : '') .
                                    strtoupper($leaveApplication->approver_lname) .
                                    (!empty($leaveApplication->approver_suffix) ? ' ' . $leaveApplication->approver_suffix : '') .
                                    (!empty($leaveApplication->approver_prefix) ? ', ' . $leaveApplication->approver_prefix : '')
                                }}
                            </b>
                        </span>
                    </center>
                    <center>{{ $leaveApplication->approver_role ?? 'Mayor' }}</center>
                </td>
            </tr>
        </thead>
    </table>
    <div style="position: relative; z-index: 1;">
        <table class="table table-inside">
            <tr>
                <th width="50%"></th>
                <th width="50%" class="bordered">Vacation Leave</th>
                <th width="50%" class="bordered">Sick Leave</th>
            </tr>
            <tr>
                <th class="bordered">Total Earned</th>
                <td class="bordered text-center">{{ $leaveApplication->total_vl }}</td>
                <td class="bordered text-center">{{ $leaveApplication->total_sl }}</td>
            </tr>
            <tr>
                <th class="bordered">Less this application</th>
                <td class="bordered text-center">
                    {{ ($emp_esign != 0) ? $leaveApplication->less_vl : 0 }}
                </td>
                <td class="bordered text-center">
                    {{ ($emp_esign != 0) ? $leaveApplication->less_sl : 0 }}
                </td>
            </tr>
            <tr>
                <th class="bordered">Balance</th>
                <td class="bordered text-center">{{ ($emp_esign != 0) ? $leaveApplication->total_vl - $leaveApplication->less_vl : 0}}</td>
                <td class="bordered text-center">{{ ($emp_esign != 0) ? $leaveApplication->total_sl - $leaveApplication->less_sl : 0}}</td>
            </tr>
        </table>
        <span style="font-size: 9px; text-align: center; margin-left: 26%; margin-top: -5px; opacity: 0.8">Doc Control Code: CPSU-F-HRMO-15 REV-01 Effective Date: 08/31/2022 Page No. <b>1</b> of <b>2</b><br>
            {{-- <img src="{{ asset($leaveApplication->transnum.'.png') }}" style="width: 100px; margin-left: 5px; margin-top: -6%; height: 20px;"></span> --}}
    </div>
    
    <div class="back-page">
        <img src="{{ asset('Uploads/leave-back-page.jpg') }}" style="width: 120%; margin-left: -10%; margin-top: -10%;">
        <span style="color:rgb(59, 59, 59); font-size: 9.3px; margin-top: -75px; position: absolute; z-index: 999; top: 88%; left: 5%; width: 90%; font-family:Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;">
            *For leave of absence for thirty (30) calendar days or more and terminal leave, application shall be accompanied by a <u> clearance from money, property and work-related accountabilities</u> (pursuant to CSC Memorandum Circular No. 2, s. 1985)
        </span>
        <span style="font-size: 9px; text-align: center; margin-left: 26%; margin-top: -110px;">Doc Control Code: CPSU-F-HRMO-15 REV-01 Effective Date: 08/31/2022 Page No. <b>2</b> of <b>2</b></span>
    </div>

</body>
</html>