<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ strtoupper($datas['employee']->lname ?? 'N/A') }} {{ strtoupper($datas['employee']->fname ?? 'N/A') }} {{ strtoupper($datas['employee']->suffix ?? 'N/A') }} {{ strtoupper($datas['employee']->mname ?? 'N/A') }}</title>
    <style>
        .div {
            height: 100%;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        }
       
        .table{
            width: 107.5% !important;
            margin-left: -25px !important;
            background-color: transparent;
            border: 1px solid black;
            border-collapse: collapse;
            padding: 0px;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            font-size: 9px;
        }
       
        .bg1{
            background-color: #989494;
            font-style: italic;
            color: #FFFFFF;
            text-align: left;
            font-size: 12px;
            height: 15px !important;
        }
       
        .td-bordered{
        }
        .th-bordered{
       
        }
        .pl1{
            padding-left: 11.5px !important;
            padding: 1px;
        }
        .pl2{
            padding: 1px;
        }
        .bordered{
            border: 1px solid black;
        }
        .tl{
            text-align: left;
        }
        .f1{
            font-size: 9px;
        }
        .vlt{
            vertical-align: top;
        }
        .bb{
            border-bottom: 1px solid black;
        }
        .bt{
            border-top: 1px solid black;
        }
        .checkbox1 {
            transform: scale(0.8);
            margin-bottom: -11px;
        }
        .checkbox-label{
            font-size: 7px;
        }
        .table1 td,
        .table1 th {
            height: 18px !important;
            padding: 2px;
        }
        .table2 td,
        .table2 th{
            height: 21px !important;
            padding: 2px;
        }
        .address-column{
            float: left;
            text-align: center;
            width: 50%;
            font-size: 9px;
            margin-top: 3.5px;
        }
        .vcenter {
            align-items: center;
            text-align: center;
            vertical-align: middle;
            font-size: 7.5px;
        }
        .f2{
            font-size: 8px !important;
        }
        .bg2{
            background-color: #eaeaea;
        }
        .text-red{
            color: red;
        }
        .h-1{
            height: 140px !important;
            font-size: 10px;
            vertical-align: top;
        }
        .h-2{
            height: 66px !important;
            font-size: 10px;
            vertical-align: top;
        }
        .h-3{
            height: 53px !important;
            font-size: 10px;
            vertical-align: top;
        }
        .h-4{
            height: 32px !important;
            font-size: 10px;
            vertical-align: top;
        }
        .h-5{
            height: 141px !important;
            font-size: 10px;
            vertical-align: top;
        }
        .p4-ml{
            margin-left: 15px;
        }
        .hide{
            display: none;
        }
        .pb-1{
            padding-bottom: 15px !important;
        }
    </style>
</head>
@php
    $fam_child_string = $datas['familyBg']->name_child ?? '';
    $fam_child_string_bday = $datas['familyBg']->date_birth ?? '';
    $children_array = explode(',', $fam_child_string);
    $children_bday = explode(',', $fam_child_string_bday);
    $otherinfo_skills_hob_string = $datas['otherinfo']->skills_hob ?? '';
    $otherinfo_recognition_string = $datas['otherinfo']->recognition ?? '';
    $otherinfo_mem_org_string = $datas['otherinfo']->mem_org ?? '';
    $otherinfo_question_string = $datas['infoquestion']->question ?? '';
    $otherinfo_questiondetail_string = $datas['infoquestion']->qdetails ?? '';
    $otherinfo_skills_hob = explode(',', $otherinfo_skills_hob_string);
    $otherinfo_recognition = explode(',', $otherinfo_recognition_string);
    $otherinfo_mem_org = explode(',', $otherinfo_mem_org_string);
    $otherinfo_question = explode(',', $otherinfo_question_string);
    $otherinfo_questiondetail = explode(',', $otherinfo_questiondetail_string);
    $refname_string = $datas['references']->refname ?? '';
    $refadd_string = $datas['references']->refadd ?? '';
    $reftelno_string = $datas['references']->reftelno ?? '';
    $refname = explode(';', $refname_string);
    $refadd = explode(';', $refadd_string);
    $reftelno = explode(';', $reftelno_string);
    $govid_string = $datas['govids']->govid ?? '';
    $govids = explode(',', $govid_string);
@endphp
<body>
    <div class="div">
        <table class="table table1" style="margin-top: -10px !important;">
            <thead>
                <tr>
                    <th colspan="9" class="bg1">
                        <img src="{{ pdf_image('Uploads/pds-header-2025.png') }}" width="100.1%" alt="" srcset="">
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th colspan="9" class="bg1">I. PERSONAL INFORMATION</th>
                </tr>
                <tr>
                    <td class="pl2 bt bg2" width="100">1. SURNAME</td>
                    <th colspan="8" class="bordered pl1 tl">{{ strtoupper($datas['employee']->lname ?? 'N/A') }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">FIRST NAME</td>
                    <th colspan="7" class="bordered pl1 tl">{{ strtoupper($datas['employee']->fname ?? 'N/A') }}</th>
                    <th width="135.5" class="tl" style=""><span style="font-size: 5px; display: block;">NAME EXTENSION (JR., SR)</span> <span class="f1">{{ strtoupper($datas['employee']->suffix ?? 'N/A') }}</span></th>
                </tr>
                <tr>
                    <td class="pl1 bg2">MIDDLE NAME</td>
                    <th colspan="8" class="bordered pl1 tl">{{ strtoupper($datas['employee']->mname ?? 'N/A') }}</th>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">3. DATE OF BIRTH &nbsp;&nbsp;&nbsp;&nbsp;(dd/mm/yyyy)</td>
                    <th class="bordered pl2" colspan="2" width="1000">{{ $datas['employee']->bdate ? \Carbon\Carbon::parse($datas['employee']->bdate)->format('d/m/Y') : 'N/A' }}</th>
                    <td class="bordered pl2 text-align-top-left bg2" rowspan="3" colspan="2" width="740">16. CITIZENSHIP <p style="text-align: center;">if holder of dual citizenship, </p> <p style="text-align: center; margin-top: -5px;">please indicate the details.</p> </td>
                    <td class="bordered pl2" colspan="4" rowspan="2">
                        <div style="float: left;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" @if(isset($datas['employee']->citizenship) && $datas['employee']->citizenship == 1) checked @endif><span>Filipino</span><br>
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: 70px;" @if(isset($datas['employee']->c_category) && $datas['employee']->c_category == 1) checked @endif><span>by birth</span><br>
                        </div>
                        <div style="float: right; margin-right: 17px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -73px;" @if(isset($datas['employee']->citizenship) && $datas['employee']->citizenship == 2) checked @endif><span>Dual Citizenship</span><br>
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" @if(isset($datas['employee']->c_category) && $datas['employee']->c_category == 2) checked @endif><span>by naturalization</span>
                        </div><br><br><br>
                        <span style="margin-left: 85px">Pls. indicate country:</span>
                    </td>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">4. PLACE OF BIRTH</td>
                    <th class="bordered pl2" colspan="2">{{ strtoupper($datas['employee']->b_place ?? 'N/A') }}</th>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">5. SEX AT BIRTH</td>
                    <td class="bordered pl2" colspan="2">
                        <input type="checkbox" class="checkbox1" style="margin-bottom: -7px; margin-left: 1.4px;" {{ (isset($datas['employee']->sex) && $datas['employee']->sex == "Male") ? 'checked' : '' }}> <span style="margin-top: -50px;">Male</span>
                        <input type="checkbox" class="checkbox1" style="margin-bottom: -7px; margin-left: 22.9%;" {{ (isset($datas['employee']->sex) && $datas['employee']->sex == "Female") ? 'checked' : '' }}><span style="margin-top: -50px;">Female</span>
                    </td>
                    <th class="bordered pl2" colspan="4">{{ strtoupper($datas['employee']->country ?? 'N/A') }}</th>
                </tr>
                <tr>
                    <td class="bordered bg2" rowspan="2" style="text-align: left; vertical-align: top;">6. CIVIL STATUS</td>
                    <td class="bordered" colspan="2" rowspan="2">
                        <div style="float: left; margin-top: -6px;">
                            <input type="checkbox" class="checkbox1" {{ (isset($datas['employee']->civil_status) && $datas['employee']->civil_status == "Single") ? 'checked' : '' }}><br>
                            <input type="checkbox" class="checkbox1" {{ (isset($datas['employee']->civil_status) && $datas['employee']->civil_status == "Widowed") ? 'checked' : '' }}><br>
                            <input type="checkbox" class="checkbox1" {{ (isset($datas['employee']->civil_status) && $datas['employee']->civil_status == "Others") ? 'checked' : '' }}><br>
                        </div>
                        <div style="float: left; padding-top: 2px;">
                            <span>Single</span><br>
                            <span>Widowed</span><br>
                            <span>Others</span>
                        </div>
                       
                        <div style="float: right; margin-right: 17px; padding-top: 2px;">
                            <span>Married</span><br>
                            <span>Separated</span><br>
                            <span>Solo Parent</span>
                        </div>
                        <div style="float: right; margin-top: -6px;">
                            <input type="checkbox" class="checkbox1" {{ (isset($datas['employee']->civil_status) && $datas['employee']->civil_status == "Married") ? 'checked' : '' }}><br>
                            <input type="checkbox" class="checkbox1" {{ (isset($datas['employee']->civil_status) && $datas['employee']->civil_status == "Separated") ? 'checked' : '' }}><br>
                            <input type="checkbox" class="checkbox1" {{ (isset($datas['employee']->civil_status) && $datas['employee']->civil_status == "Solo Parent") ? 'checked' : '' }}>
                        </div>
                    </td>
                    <td class="bordered tl bg2" rowspan="4" colspan="1" width="97" style="vertical-align: top; text-align: left;">
                        17. RESIDENTIAL ADDRESS<div style="margin-top: 70px; text-align: center; padding: 0px;">ZIPCODE</div>
                    </td>
                    <td class="bb" colspan="5">
                        <div class="address-column">
                            <b>{{ strtoupper($datas['employee']->add_block ?? 'N/A') }}</b><br>House/Block/Lot No.
                        </div>
                        <div class="address-column">
                            <b>{{ strtoupper($datas['employee']->add_street ?? 'N/A') }}</b><br>Street
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bb" colspan="5">
                        <div class="address-column">
                            <b>{{ strtoupper($datas['employee']->add_village ?? 'N/A') }}</b><br>Subdivision/Village
                        </div>
                        <div class="address-column">
                            <b>{{ isset($datas['barangay']->name) ? strtoupper($datas['barangay']->name) : 'N/A' }}</b><br>Barangay
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">7. HEIGHT (m)</td>
                    <th class="bordered pl2" colspan="2">{{ isset($datas['employee']->height_m) ? $datas['employee']->height_m.'m' : 'N/A' }}</th>
                    <td class="bordered pl2" colspan="5">
                        <div class="address-column">
                            <b>{{ isset($datas['city']->name) ? strtoupper($datas['city']->name) : 'N/A' }}</b><br>City/Municipality
                        </div>
                        <div class="address-column">
                            <b>{{ isset($datas['province']->name) ? strtoupper($datas['province']->name) : 'N/A' }}</b><br>Province
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">8. WEIGHT (kg)</td>
                    <th class="bordered pl2" colspan="2">{{ isset($datas['employee']->weight_kg) ? $datas['employee']->weight_kg.'kgs' : 'N/A' }}</th>
                    <th class="bordered pl2" colspan="5">{{ $datas['employee']->add_zcode ?? 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">9. BLOOD TYPE</td>
                    <th class="bordered pl2" colspan="2">{{ $datas['employee']->b_type ?? 'N/A' }}</th>
                    <td class="bordered tl bg2" rowspan="4" colspan="1" width="97" style="vertical-align: top; text-align: left;">
                        18. PERMANENT ADDRESS<div style="margin-top: 70px; text-align: center;">ZIPCODE</div>
                    </td>
                    <td class="bb" colspan="5">
                        <div class="address-column">
                            <b>{{ strtoupper($datas['employee']->padd_block ?? 'N/A') }}</b><br>House/Block/Lot No.
                        </div>
                        <div class="address-column">
                            <b>{{ strtoupper($datas['employee']->padd_street ?? 'N/A') }}</b><br>Street
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">10. UMID ID NO.</td>
                    <th class="bordered pl2" colspan="2">{{ $datas['employee']->sss ?? 'N/A' }}</th>
                    <td class="bordered pl2" colspan="5">
                        <div class="address-column">
                            <b>{{ strtoupper($datas['employee']->padd_village ?? 'N/A') }}</b><br>Subdivision/Village
                        </div>
                        <div class="address-column">
                            <b>{{ isset($datas['barangay1']->name) ? strtoupper($datas['barangay1']->name) : 'N/A' }}</b><br>Barangay
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">11. PAG-IBIG ID NO.</td>
                    <th class="bordered pl2" colspan="2">{{ $datas['employee']->pagibig ?? 'N/A' }}</th>
                    <td class="bordered pl2" colspan="5">
                        <div class="address-column">
                            <b>{{ isset($datas['city1']->name) ? strtoupper($datas['city1']->name) : 'N/A' }}</b><br>City/Municipality
                        </div>
                        <div class="address-column">
                            <b>{{ isset($datas['province1']->name) ? strtoupper($datas['province1']->name) : 'N/A' }}</b><br>Province
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">12. PHILHEALTH NO.</td>
                    <th class="bordered pl2" colspan="2">{{ $datas['employee']->philhealth ?? 'N/A' }}</th>
                    <th class="bordered pl2" colspan="5">{{ $datas['employee']->padd_zcode ?? 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">13. PhilSys Number (PSN)</td>
                    <th class="bordered pl2" colspan="2">{{ $datas['employee']->philsys ?? 'N/A' }}</th>
                    <td class="bordered pl2 bg2">19. TELEPHONE NO.</td>
                    <th class="bordered pl2" colspan="5">{{ $datas['employee']->telephone ?? 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">14. TIN NO.</td>
                    <th class="bordered pl2" colspan="2">{{ $datas['employee']->tin ?? 'N/A' }}</th>
                    <td class="bordered pl2 bg2">20. MOBILE NO.</td>
                    <th class="bordered pl2" colspan="5">{{ $datas['employee']->mobile ?? 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="bordered pl2 bg2">15. AGENCY EMPLOYEE NO.</td>
                    <th class="bordered pl2" colspan="2">{{ $datas['employee']->emp_ID ?? 'N/A' }}</th>
                    <td class="bordered pl2 bg2">21. E-MAIL ADDRESS (if any)</td>
                    <th class="bordered pl2" colspan="5">{{ $datas['employee']->org_email ?? 'N/A' }}</th>
                </tr>
            </tbody>
        </table>
        <table class="table table1">
            <thead>
                <tr>
                    <th colspan="9" class="bg1" style="height: 0px !important;">
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th colspan="9" class="bg1">II. FAMILY BACKGROUND</th>
                </tr>
                <tr>
                    <td class="pl2 bt bg2" width="100">22. SPOUSE'S SURNAME</td>
                    <th colspan="3" class="bordered pl1 tl" width="245">{{ strtoupper($datas['familyBg']->spouse_sname ?? 'N/A') }}</th>
                    <td class="bordered bg2" colspan="3" width="160" style="font-size: 7px">23. NAME of CHILDREN (Write full name and list all)</td>
                    <td class="bordered bg2" colspan="2" width="90" style="font-size: 7px">DATE OF BIRTH (dd/mm/yyyy)</td>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;FIRST NAME</td>
                    <th colspan="2" class="bordered pl1 tl">{{ strtoupper($datas['familyBg']->spouse_fname ?? 'N/A') }}</th>
                    <th class="tl bg2"><span style="font-size: 5px; display: block;">NAME EXTENSION (JR., SR)</span> <span class="f1">{{ strtoupper($datas['familyBg']->spouse_ext ?? 'N/A') }}</span></th>
                    <th class="bordered" colspan="3">{{ isset($children_array[0]) ? strtoupper(trim($children_array[0])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[0]) && !empty($children_bday[0]) ? \Carbon\Carbon::parse(trim($children_bday[0]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;MIDDLE NAME</td>
                    <th colspan="3" class="bordered pl1 tl">{{ strtoupper($datas['familyBg']->spouse_mname ?? 'N/A') }}</th>
                    <th class="bordered" colspan="3">{{ isset($children_array[1]) ? strtoupper(trim($children_array[1])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[1]) && !empty($children_bday[1]) ? \Carbon\Carbon::parse(trim($children_bday[1]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;OCCUPATION</td>
                    <th colspan="3" class="bordered pl1 tl">{{ strtoupper($datas['familyBg']->occupation ?? 'N/A') }}</th>
                    <th class="bordered" colspan="3">{{ isset($children_array[2]) ? strtoupper(trim($children_array[2])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[2]) && !empty($children_bday[2]) ? \Carbon\Carbon::parse(trim($children_bday[2]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2" style="font-size: 8px;">&nbsp;&nbsp;EMPLOYER/BUSINESS NAME</td>
                    <th colspan="3" class="bordered pl1 tl">{{ strtoupper($datas['familyBg']->bus_name ?? 'N/A') }}</th>
                    <th class="bordered" colspan="3">{{ isset($children_array[3]) ? strtoupper(trim($children_array[3])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[3]) && !empty($children_bday[3]) ? \Carbon\Carbon::parse(trim($children_bday[3]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;BUSINESS ADDRESS</td>
                    <th colspan="3" class="bordered pl1 tl">{{ strtoupper($datas['familyBg']->bus_address ?? 'N/A') }}</th>
                    <th class="bordered" colspan="3">{{ isset($children_array[4]) ? strtoupper(trim($children_array[4])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[4]) && !empty($children_bday[4]) ? \Carbon\Carbon::parse(trim($children_bday[4]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;TELEPHONE NO.</td>
                    <th colspan="3" class="bordered pl1 tl">{{ $datas['familyBg']->telephone ?? 'N/A' }}</th>
                    <th class="bordered" colspan="3">{{ isset($children_array[5]) ? strtoupper(trim($children_array[5])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[5]) && !empty($children_bday[5]) ? \Carbon\Carbon::parse(trim($children_bday[5]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl2 bt bg2" width="100">24. FATHER'S SURNAME</td>
                    <th colspan="3" class="bordered pl1 tl">{{ str_replace('ñ', 'Ñ', mb_strtoupper($datas['familyBg']->father_sname ?? 'N/A')) }}</th>
                    <th colspan="3" class="bordered">{{ isset($children_array[6]) ? strtoupper(trim($children_array[6])) : 'N/A' }}</th>
                    <th colspan="2" class="bordered">{{ isset($children_bday[6]) && !empty($children_bday[6]) ? \Carbon\Carbon::parse(trim($children_bday[6]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;FIRST NAME</td>
                    <th colspan="2" class="bordered pl1 tl">{{ str_replace('ñ', 'Ñ', mb_strtoupper($datas['familyBg']->father_fname ?? 'N/A')) }}</th>
                    <th class="tl bg2" style=""><span style="font-size: 5px; display: block;">NAME EXTENSION (JR, SR)</span> <span class="f1">{{ strtoupper($datas['familyBg']->father_ext ?? 'N/A') }}</span></th>
                    <th class="bordered" colspan="3">{{ isset($children_array[7]) ? strtoupper(trim($children_array[7])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[7]) && !empty($children_bday[7]) ? \Carbon\Carbon::parse(trim($children_bday[7]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;MIDDLE NAME</td>
                    <th colspan="3" class="bordered pl1 tl">{{ str_replace('ñ', 'Ñ', mb_strtoupper($datas['familyBg']->father_mname ?? 'N/A')) }}</th>
                    <th class="bordered" colspan="3">{{ isset($children_array[8]) ? strtoupper(trim($children_array[8])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[8]) && !empty($children_bday[8]) ? \Carbon\Carbon::parse(trim($children_bday[8]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl2 bt bg2" width="100" style="font-size: 8.8px !important;">25. MOTHER'S MAIDEN NAME</td>
                    <th colspan="3" class="bordered pl1 tl">{{ strtoupper($datas['employee']->mname ?? 'N/A') }}</th>
                    <th colspan="3" class="bordered">{{ isset($children_array[9]) ? strtoupper(trim($children_array[9])) : 'N/A' }}</th>
                    <th colspan="2" class="bordered">{{ isset($children_bday[9]) && !empty($children_bday[9]) ? \Carbon\Carbon::parse(trim($children_bday[9]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;SURNAME</td>
                    <th colspan="3" class="bordered pl1 tl">{{ str_replace('ñ', 'Ñ', mb_strtoupper($datas['familyBg']->mother_sname ?? 'N/A')) }}</th>
                    <th class="bordered" colspan="3">{{ isset($children_array[10]) ? strtoupper(trim($children_array[10])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[10]) && !empty($children_bday[10]) ? \Carbon\Carbon::parse(trim($children_bday[10]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bg2">&nbsp;&nbsp;FIRST NAME</td>
                    <th colspan="3" class="bordered pl1 tl">{{ str_replace('ñ', 'Ñ', mb_strtoupper($datas['familyBg']->mother_fname ?? 'N/A')) }}</th>
                    <th class="bordered" colspan="3">{{ isset($children_array[11]) ? strtoupper(trim($children_array[11])) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($children_bday[11]) && !empty($children_bday[11]) ? \Carbon\Carbon::parse(trim($children_bday[11]))->format('d/m/Y') : 'N/A' }}</th>
                </tr>
                <tr>
                    <td class="pl1 bb bg2">&nbsp;&nbsp;MIDDLE NAME</td>
                    <th colspan="3" class="bordered pl1 tl">{{ str_replace('ñ', 'Ñ', mb_strtoupper($datas['familyBg']->mother_mname ?? 'N/A')) }}</th>
                    <td class="bordered vcenter text-red" colspan="5">(Continue on separate sheet if necessary)</td>
                </tr>
            </tbody>
        </table>
        <table class="table table1">
            <thead>
                <tr>
                    <th colspan="9" class="bg1">III. EDUCATIONAL BACKGROUND</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="bordered bg2" rowspan="2" width="98.5">26. <span style="margin-left: 35px;">LEVEL</span></td>
                    <td class="bordered vcenter bg2" rowspan="2" width="142">NAME OF SCHOOL<br>(Write in full)</td>
                    <td class="bordered vcenter bg2" rowspan="2" colspan="2" width="900">BASIC EDUCATION/DEGREE/COURSE<br>(Write in full)</td>
                    <td class="bordered vcenter bg2" colspan="2" width="260">PERIOD OF ATTENDANCE</td>
                    <td class="bordered vcenter bg2" rowspan="2" width="20">HIGHEST LEVEL/UNITS EARNED<br>(if not graduated)</td>
                    <td class="bordered vcenter bg2" rowspan="2" width="20">YEAR GRADUATED</td>
                    <td class="bordered vcenter bg2" rowspan="2" width="20">SCHOLARSHIP/ ACADEMIC HONORS RECEIVED</td>
                </tr>
                <tr>
                    <td class="bordered vcenter bg2" width="23">
                        From
                    </td>
                    <td class="bordered vcenter bg2" width="23">
                        To
                    </td>
                </tr>
                <tr>
                    <td class="bordered pl1 bg2">ELEMENTARY</td>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->elem_school ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter" colspan="2">{{ trim($datas['educBg']->elem_school ?? '') ? 'PRIMARY EDUCATION' : 'N/A' }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim(explode('-', trim($datas['educBg']->elem_period ?? ''))[0] ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim(explode('-', trim($datas['educBg']->elem_period ?? ''))[1] ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->elem_level ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->elem_grad ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->elem_honor ?? '') ?: 'N/A') }}</th>
                </tr>
                <tr>
                    <td class="bordered pl1 bg2">SECONDARY</td>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->sec_school ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter" colspan="2">{{ trim($datas['educBg']->sec_school ?? '') ? 'SECONDARY EDUCATION' : 'N/A' }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim(explode('-', trim($datas['educBg']->sec_period ?? ''))[0] ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim(explode('-', trim($datas['educBg']->sec_period ?? ''))[1] ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->sec_level ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->sec_grad ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->sec_honor ?? '') ?: 'N/A') }}</th>
                </tr>
                <tr>
                    <td class="bordered pl1 bg2">VOCATIONAL / TRADE COURSE</td>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->voc_school ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter" colspan="2">{{ strtoupper(trim($datas['educBg']->voc_course ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim(explode('-', trim($datas['educBg']->voc_period ?? ''))[0] ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim(explode('-', trim($datas['educBg']->voc_period ?? ''))[1] ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->voc_level ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->voc_grad ?? '') ?: 'N/A') }}</th>
                    <th class="bordered vcenter">{{ strtoupper(trim($datas['educBg']->voc_honor ?? '') ?: 'N/A') }}</th>
                </tr>
                @if ($datas['educBg'])
                @php
                    $schools = explode(',', $datas['educBg']->coll_school ?? '');
                    $courses = explode(',', $datas['educBg']->coll_course ?? '');
                    $periods = explode(',', $datas['educBg']->coll_period ?? '');
                    $levels = explode(',', $datas['educBg']->coll_level ?? '');
                    $grads = explode(',', $datas['educBg']->coll_grad ?? '');
                    $honors = explode(',', $datas['educBg']->coll_honor ?? '');
                    $maxRows = max(count($schools), count($courses), count($periods), count($levels), count($grads), count($honors));
                    $gradSchools = explode(',', $datas['educBg']->grad_school ?? '');
                    $gradCourses = explode(',', $datas['educBg']->grad_course ?? '');
                    $gradPeriods = explode(',', $datas['educBg']->grad_period ?? '');
                    $gradLevels = explode(',', $datas['educBg']->grad_level ?? '');
                    $gradGrads = explode(',', $datas['educBg']->grad_grad ?? '');
                    $gradHonors = explode(',', $datas['educBg']->grad_honor ?? '');
                    $maxGradRows = max(count($gradSchools), count($gradCourses), count($gradPeriods), count($gradLevels), count($gradGrads), count($gradHonors));
        
                @endphp
        
                    @for ($i = 0; $i < $maxRows; $i++)
                    <tr>
                        <td class="bordered pl1 bg2">COLLEGE</td>
                        <th class="bordered vcenter">{{ strtoupper(trim($schools[$i] ?? '') ?: 'N/A') }}</th>
                        <th class="bordered vcenter" colspan="2">{{ strtoupper(trim($courses[$i] ?? '') ?: 'N/A') }}</th>
                        <th class="bordered vcenter">
                            {{ strtoupper(trim(explode('-', trim($periods[$i] ?? ''))[0] ?? '') ?: 'N/A') }}
                        </th>
                        <th class="bordered vcenter">
                            {{ strtoupper(trim(explode('-', trim($periods[$i] ?? ''))[1] ?? '') ?: 'N/A') }}
                        </th>
                        <th class="bordered vcenter">{{ strtoupper(trim($levels[$i] ?? '') ?: 'N/A') }}</th>
                        <th class="bordered vcenter">{{ strtoupper(trim($grads[$i] ?? '') ?: 'N/A') }}</th>
                        <th class="bordered vcenter">{{ strtoupper(trim($honors[$i] ?? '') ?: 'N/A') }}</th>
                    </tr>
                    @endfor
                    @for ($i = 0; $i < $maxGradRows; $i++)
                        <tr>
                            <td class="bordered pl1 bg2">GRADUATE STUDIES</td>
                            <th class="bordered vcenter">{{ strtoupper(trim($gradSchools[$i] ?? '') ?: 'N/A') }}</th>
                            <th class="bordered vcenter" colspan="2">{{ strtoupper(trim($gradCourses[$i] ?? '') ?: 'N/A') }}</th>
                            <th class="bordered vcenter">
                                {{ strtoupper(trim(explode('-', trim($gradPeriods[$i] ?? ''))[0] ?? '') ?: 'N/A') }}
                            </th>
                            <th class="bordered vcenter">
                                {{ strtoupper(trim(explode('-', trim($gradPeriods[$i] ?? ''))[1] ?? '') ?: 'N/A') }}
                            </th>
                            <th class="bordered vcenter">{{ strtoupper(trim($gradLevels[$i] ?? '') ?: 'N/A') }}</th>
                            <th class="bordered vcenter">{{ strtoupper(trim($gradGrads[$i] ?? '') ?: 'N/A') }}</th>
                            <th class="bordered vcenter">{{ strtoupper(trim($gradHonors[$i] ?? '') ?: 'N/A') }}</th>
                        </tr>
                    @endfor
                @else
                    <tr>
                        <td class="bordered pl1 bg2">COLLEGE</td>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter" colspan="2">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                    </tr>
                    <tr>
                        <td class="bordered pl1 bg2">GRADUATE STUDIES</td>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter" colspan="2">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                        <th class="bordered vcenter">N/A</th>
                    </tr>
                @endif
            
                <tr>
                    <td colspan="9" class="vcenter text-red" style="height: 5px !important;">(Continue on separate sheet if necessary)</td>
                </tr>
                <tr>
                    <th class="bordered bg2"><em>SIGNATURE</em></th>
                    <th class="bordered" colspan="3"></th>
                    <th class="bordered bg2" colspan="2"><em>DATE</em></th>
                    <th class="bordered" colspan="3">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</th>
                </tr>
            </tbody>
        </table>
    </div>
   
    <div class="div">
        <table class="table table2">
            <thead>
                <tr>
                    <th colspan="6" class="bg1">IV. CIVIL SERVICE ELIGIBILITY</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="bordered f2 bg2" rowspan="2" width="170">27. CES/CSEE/CAREER SERVICE/RA 1080 (BOARD/ BAR)/UNDER SPECIAL LAWS/CATEGORY II/ IV ELIGIBILITY and ELIGIBILITIES FOR UNIFORMED PERSONNEL</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2">RATING <br>(If Applicable)</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2">DATE OF <br>EXAMINATION /<br> CONFERMENT</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2">PLACE OF EXAMINATION / CONFERMENT</td>
                    <td class="bordered vcenter f2 bg2" colspan="2">LICENSE (if applicable)</td>
                </tr>
                <tr>
                    <td class="bordered vcenter bg2">NUMBER</td>
                    <td class="bordered vcenter bg2">Valid Until</td>
                </tr>
            </tbody>
            <tbody>
                @foreach($datas['eligibility'] ?? [] as $eligble)
                    <tr>
                        <th class="bordered tl">{{ strtoupper($eligble->careereligible ?? 'N/A') }}</th>
                        <th class="bordered">{{ $eligble->rating ?? 'N/A' }}</th>
                        <th class="bordered">{{ $eligble->date_exam ? \Carbon\Carbon::parse($eligble->date_exam)->format('d/m/Y') : 'N/A' }}</th>
                        <th class="bordered">{{ strtoupper($eligble->place_exam ?? 'N/A') }}</th>
                        <th class="bordered">{{ $eligble->number ?? 'N/A' }}</th>
                        <th class="bordered">{{ $eligble->date_valid ? \Carbon\Carbon::parse($eligble->date_valid)->format('d/m/Y') : 'N/A' }}</th>
                    </tr>
                @endforeach
                @php $elicount = 7 - count($datas['eligibility'] ?? []); @endphp
                @for($i = 1; $i <= $elicount; $i++)
                    <tr>
                        <th class="bordered tl">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                    </tr>
                @endfor
                <tr>
                    <td colspan="6" class="vcenter text-red" style="height: 10px !important;">(Continue on separate sheet if necessary)</td>
                </tr>
            </tbody>
        </table>
        <table class="table table2">
            <thead>
                <tr>
                    <th colspan="10" class="bg1">V. WORK EXPERIENCE<br><span style="font-size: 9.2px;">(Include private employment. Start from your recent work) Description of duties should be indicated in the attached Work Experience sheet.</span> </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="bordered f2 bg2" colspan="2">28. &nbsp;&nbsp;INCLUSIVE DATES<br><span style="margin-left: 30px;">(dd/mm/yyyy)</span></td>
                    <td class="bordered vcenter f2 bg2" rowspan="2" colspan="3">POSITION TITLE <br> (Write in full/Do not abbreviate)</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2" colspan="3">DEPARTMENT / AGENCY / OFFICE / COMPANY <br> (Write in full/Do not abbreviate)</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2">STATUS OF <br>APPOINTMENT</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2" width="25">GOV'T <br>SERVICE <br>(Y/ N)</td>
                </tr>
                <tr>
                    <td class="bordered vcenter f2 bg2" width="38">From</td>
                    <td class="bordered vcenter f2 bg2" width="38">To</td>
                </tr>
                @foreach($datas['workexperience'] ?? [] as $experience)
                    <tr>
                        <th class="bordered">{{ $experience->inc_date1 ? \Carbon\Carbon::parse($experience->inc_date1)->format('d/m/Y') : 'N/A' }}</th>
                        <th class="bordered">@if($experience->inc_date2 != null){{ $experience->inc_date2 ? \Carbon\Carbon::parse($experience->inc_date2)->format('d/m/Y') : 'N/A' }}@else PRESENT @endif</th>
                        <th class="bordered" colspan="3">{{ strtoupper($experience->position ?? 'N/A') }}</th>
                        <th class="bordered" colspan="3">{{ strtoupper($experience->department ?? 'N/A') }}</th>
                        <th class="bordered">{!! nl2br(e(preg_replace('/(\s|\/|,|-)/', "$1\n", strtoupper($experience->stat_app ?? 'N/A')) )) !!}</th>
                        <th class="bordered">{{ strtoupper($experience->service ?? 'N/A') }}</th>
                    </tr>
                @endforeach
                @php $workcount = 28 - count($datas['workexperience'] ?? []); @endphp
                @for($i = 1; $i <= $workcount; $i++)
                    <tr>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered" colspan="3">N/A</th>
                        <th class="bordered" colspan="3">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                    </tr>
                @endfor
                <tr>
                    <td colspan="10" class="vcenter text-red" style="height: 10px !important;">(Continue on separate sheet if necessary)</td>
                </tr>
                <tr>
                    <th class="bordered" colspan="3">SIGNATURE</th>
                    <td class="bordered" colspan="3"></td>
                    <th class="bordered" colspan="2">DATE</th>
                    <th class="bordered" colspan="2">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</th>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="div">
        <table class="table table2">
            <thead>
                <tr>
                    <th colspan="7" class="bg1">VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="bordered f2 bg2" rowspan="2" width="255">29.<span style="margin-left: 25%;">NAME & ADDRESS OF ORGANIZATION</span><br><span style="margin-left: 45%;">(Write in full)</span></td>
                    <td class="bordered vcenter f2 bg2" colspan="2" width="100">INCLUSIVE DATES <br> (dd/mm/yyyy)</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2" width="45">NUMBER OF <br> HOURS</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2" colspan="3" width="130">POSITION / NATURE OF WORK</td>
                </tr>
                <tr>
                    <td class="bordered vcenter f2 bg2" width="38">From</td>
                    <td class="bordered vcenter f2 bg2" width="38">To</td>
                </tr>
                @foreach($datas['voluntaryworks'] ?? [] as $voluntary)
                    <tr>
                        <th class="bordered tl">{{ strtoupper($voluntary->org_name ?? 'N/A') }}</th>
                        <th class="bordered">{{ $voluntary->inc_date1 ? \Carbon\Carbon::parse($voluntary->inc_date1)->format('d/m/Y') : 'N/A' }}</th>
                        <th class="bordered">{{ $voluntary->inc_date2 ? \Carbon\Carbon::parse($voluntary->inc_date2)->format('d/m/Y') : 'N/A' }}</th>
                        <th class="bordered">{{ $voluntary->num_hours.' Hrs.' ?? 'N/A' }}</th>
                        <th class="bordered" colspan="3">{{ strtoupper($voluntary->position ?? 'N/A') }}</th>
                    </tr>
                @endforeach
                @php $voluntary = 7 - count($datas['voluntaryworks'] ?? []); @endphp
                @for($i = 1; $i <= $voluntary; $i++)
                    <tr>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered" colspan="3">N/A</th>
                    </tr>
                @endfor
                <tr>
                    <td colspan="7" class="vcenter text-red" style="height: 10px !important;">(Continue on separate sheet if necessary)</td>
                </tr>
                <tr>
                    <th colspan="7" class="bg1 bordered">VII. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED<br><span style="font-size: 8px;">(Start from the most recent L&D/training program and include only the relevant L&D/training taken for the last five (5) years for Division Chief/Executive/Managerial positions)</span></th>
                </tr>
                <tr>
                    <td class="bordered f2 bg2" rowspan="2">30.<span style="margin-left: 2%;">TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS</span><br><span style="margin-left: 45%;">(Write in full)</span></td>
                    <td class="bordered vcenter f2 bg2" colspan="2">INCLUSIVE DATES OF<br> ATTENDANCE<br> (dd/mm/yyyy)</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2">NUMBER OF <br> HOURS</td>
                    <td class="bordered vcenter f2 bg2" rowspan="2">Type of L&D <br>( Managerial/ <br>Supervisory/<br>Technical/etc) </td>
                    <td class="bordered vcenter f2 bg2" rowspan="2" colspan="2">CONDUCTED/ SPONSORED BY <br> (Write in full)</td>
                </tr>
                <tr>
                    <td class="bordered vcenter f2 bg2" width="38">From</td>
                    <td class="bordered vcenter f2 bg2" width="38">To</td>
                </tr>
                @foreach($datas['learningdev'] ?? [] as $learning)
                    <tr>
                        <th class="bordered tl">{{ strtoupper($learning->learning_dev ?? 'N/A') }}</th>
                        <th class="bordered">{{ $learning->inc_date1 ? \Carbon\Carbon::parse($learning->inc_date1)->format('d/m/Y') : 'N/A' }}</th>
                        <th class="bordered">{{ $learning->inc_date2 ? \Carbon\Carbon::parse($learning->inc_date2)->format('d/m/Y') : 'N/A' }}</th>
                        <th class="bordered">{{ $learning->num_hours.' Hrs.' ?? 'N/A' }}</th>
                        <th class="bordered">{{ strtoupper($learning->types ?? 'N/A') }}</th>
                        @php
                            $text = strtoupper($learning->conducted ?? 'N/A');
                            $fontSize = strlen($text) > 40 ? '6px' : '';
                        @endphp
                        <th class="bordered" colspan="2" style="font-size: {{ $fontSize }};">
                            {{ $text }}
                        </th>
                    </tr>
                @endforeach
                @php
                    $current = count($datas['learningdev'] ?? []);
                    $maxRows = $current > 19 ? 40 : 19;
                    $learning = max(0, $maxRows - $current);
                @endphp

                @for ($i = 1; $i <= $learning; $i++)
                    <tr>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered">N/A</th>
                        <th class="bordered" colspan="2">N/A</th>
                    </tr>
                @endfor
                <tr>
                    <td colspan="7" class="vcenter text-red" style="height: 10px !important;">(Continue on separate sheet if necessary)</td>
                </tr>
            </tbody>
        </table>
        <table class="table table2">
            <thead>
                <tr>
                    <th colspan="6" class="bg1 bordered">VIII. OTHER INFORMATION</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="bordered vcenter f2 bg2" colspan="2" width="200">31. <span style="margin-left: 20%;">SPECIAL SKILLS and HOBBIES</span><br><span style="color: #eaeaea;">.</span></td>
                    <td class="bordered vcenter f2 bg2" colspan="2" width="200">32. <span style="margin-left: 8%;">NON-ACADEMIC DISTINCTIONS / RECOGNITION</span> <br> <span style="margin-left: 42%;">(Write in full)</span></td>
                    <td class="bordered vcenter f2 bg2" colspan="2" width="200">33. <span style="margin-left: 7%;">MEMBERSHIP IN ASSOCIATION/ORGANIZATION</span> <br> <span style="margin-left: 42%;">(Write in full)</span></td>
                </tr>
                <tr>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_skills_hob[0]) && trim($otherinfo_skills_hob[0]) !== '' ? trim($otherinfo_skills_hob[0]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_recognition[0]) && trim($otherinfo_recognition[0]) !== '' ? trim($otherinfo_recognition[0]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_mem_org[0]) && trim($otherinfo_mem_org[0]) !== '' ? trim($otherinfo_mem_org[0]) : 'N/A' }}</th>
                </tr>
                <tr>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_skills_hob[1]) && trim($otherinfo_skills_hob[1]) !== '' ? trim($otherinfo_skills_hob[1]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_recognition[1]) && trim($otherinfo_recognition[1]) !== '' ? trim($otherinfo_recognition[1]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_mem_org[1]) && trim($otherinfo_mem_org[1]) !== '' ? trim($otherinfo_mem_org[1]) : 'N/A' }}</th>
                </tr>
                <tr>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_skills_hob[2]) && trim($otherinfo_skills_hob[2]) !== '' ? trim($otherinfo_skills_hob[2]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_recognition[2]) && trim($otherinfo_recognition[2]) !== '' ? trim($otherinfo_recognition[2]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_mem_org[2]) && trim($otherinfo_mem_org[2]) !== '' ? trim($otherinfo_mem_org[2]) : 'N/A' }}</th>
                </tr>
                <tr>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_skills_hob[3]) && trim($otherinfo_skills_hob[3]) !== '' ? trim($otherinfo_skills_hob[3]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_recognition[3]) && trim($otherinfo_recognition[3]) !== '' ? trim($otherinfo_recognition[3]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_mem_org[3]) && trim($otherinfo_mem_org[3]) !== '' ? trim($otherinfo_mem_org[3]) : 'N/A' }}</th>
                </tr>
                <tr>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_skills_hob[4]) && trim($otherinfo_skills_hob[4]) !== '' ? trim($otherinfo_skills_hob[4]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_recognition[4]) && trim($otherinfo_recognition[4]) !== '' ? trim($otherinfo_recognition[4]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_mem_org[4]) && trim($otherinfo_mem_org[4]) !== '' ? trim($otherinfo_mem_org[4]) : 'N/A' }}</th>
                </tr>
                <tr>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_skills_hob[5]) && trim($otherinfo_skills_hob[5]) !== '' ? trim($otherinfo_skills_hob[5]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_recognition[5]) && trim($otherinfo_recognition[5]) !== '' ? trim($otherinfo_recognition[5]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_mem_org[5]) && trim($otherinfo_mem_org[5]) !== '' ? trim($otherinfo_mem_org[5]) : 'N/A' }}</th>
                </tr>
                <tr>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_skills_hob[6]) && trim($otherinfo_skills_hob[6]) !== '' ? trim($otherinfo_skills_hob[6]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_recognition[6]) && trim($otherinfo_recognition[6]) !== '' ? trim($otherinfo_recognition[6]) : 'N/A' }}</th>
                    <th class="bordered" colspan="2">{{ isset($otherinfo_mem_org[6]) && trim($otherinfo_mem_org[6]) !== '' ? trim($otherinfo_mem_org[6]) : 'N/A' }}</th>
                </tr>
                <tr>
                    <td colspan="6" class="vcenter text-red" style="height: 10px !important;">(Continue on separate sheet if necessary)</td>
                </tr>
                <tr>
                    <th class="bordered bg2" colspan="2">SIGNATURE</th>
                    <td class="bordered"></td>
                    <th class="bordered bg2">DATE</th>
                    <th class="bordered" colspan="2">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</th>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="div">
        <table class="table">
            <tbody>
                <tr>
                    <td class="bordered h-1 bg2" width="65%">
                        <span class="pb-1">34. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the</span>,<br>
                        <span class="p4-ml">chief of bureau or office or to the person who has immediate supervision over you in the Office,</span><br>
                        <span class="p4-ml">Bureau or Department where you will be appointed,</span><br>
                        <span class="p4-ml">a. within the third degree?</span><br>
                        <span class="p4-ml">b. within the fourth degree (for Local Government Unit - Career Employees)?</span>
                    </td>
                    <td class="bordered h-1" width="35%">
                        <div style="float: left; margin-top: 31px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[0]) && $otherinfo_question[0] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[1]) && $otherinfo_question[1] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <div style="margin-left: 5px; margin-top: 5px;">If YES, give details:</div>
                            <div style="margin-left: 5px; font-size: 8px !important; margin-top: {{ ($otherinfo_question[1] == 1) ? '5px;' : '17px;'}} width: 238px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[1]) && $otherinfo_question[1] == 1) ? $otherinfo_questiondetail[1] : ''}}</div>
                        </div>
                        <div style="float: right; margin-right: 17px; margin-top: 31px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[0]) && $otherinfo_question[0] == 0) ? 'checked' : ''}}><span>NO</span><br>
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[1]) && $otherinfo_question[1] == 0) ? 'checked' : ''}}><span>NO</span> 
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered h-2 bg2" width="65%" style="border-bottom: none !important;">
                        <span>35. a. Have you ever been found guilty of any administrative offense?</span>,<br>
                    </td>
                    <td class="bordered h-2" width="35%">
                        <div style="float: left;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[2]) && $otherinfo_question[2] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <div style="margin-left: 5px; margin-top: 5px;">If YES, give details:</div>
                            <div style="margin-left: 5px; font-size: 8px !important; margin-top: {{ ($otherinfo_question[2] == 1) ? '5px;' : '17px;'}} width: 238px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[2]) && $otherinfo_question[2] == 1) ? $otherinfo_questiondetail[2] : ''}}</div>
                        </div>
                        <div style="float: right; margin-right: 17px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[2]) && $otherinfo_question[2] == 0) ? 'checked' : ''}}><span>NO</span><br>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered h-2 bg2" width="65%" style="border-top: none !important;">
                        <span class="p4-ml">b. Have you been criminally charged before any court?</span><br>
                    </td>
                    <td class="bordered h-3" width="35%">
                        <div style="float: left;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[3]) && $otherinfo_question[3] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <span style="margin-left: 5px;">If YES, give details:</span><br>
                            <span style="margin-left: 5px;">Date Filed:</span> <span style="margin-left: 38px; width: 150px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[3]) && $otherinfo_question[3] == 1) ? $otherinfo_questiondetail[12] : ''}}</span><br>
                            <span style="margin-left: 5px;">Status of Case/s:</span> <span style="margin-left: 10.3px; width: 150px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[3]) && $otherinfo_question[3] == 1) ? $otherinfo_questiondetail[3] : ''}}</span>
                        </div>
                        <div style="float: right; margin-right: 17px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[3]) && $otherinfo_question[3] == 0) ? 'checked' : ''}}><span>NO</span><br>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered h-2 bg2" width="65%" style="border-top: none !important;">
                        <span>36. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation</span><br><span style="margin-left: 17px;">by any court or tribunal?</span><br>
                    </td>
                    <td class="bordered h-2" width="35%">
                        <div style="float: left;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[4]) && $otherinfo_question[4] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <div style="margin-left: 5px; margin-top: 5px;">If YES, give details:</div>
                            <div style="margin-left: 5px; font-size: 8px !important; margin-top: {{ ($otherinfo_question[4] == 1) ? '5px;' : '17px;'}} width: 238px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[4]) && $otherinfo_question[4] == 1) ? $otherinfo_questiondetail[4] : ''}}</div>
                        </div>
                        <div style="float: right; margin-right: 17px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[4]) && $otherinfo_question[4] == 0) ? 'checked' : ''}}><span>NO</span><br>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered h-2 bg2" width="65%" style="border-top: none !important;">
                        <span>37. Have you ever been separated from the service in any of the following modes: resignation,</span><br><span style="margin-left: 17px;">retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out</span><br><span style="margin-left: 17px;">(abolition) in the public or private sector?</span><br>
                    </td>
                    <td class="bordered h-2" width="35%">
                        <div style="float: left;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[5]) && $otherinfo_question[5] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <div style="margin-left: 5px; margin-top: 5px;">If YES, give details:</div>
                            <div style="margin-left: 5px; font-size: 8px !important; margin-top: {{ ($otherinfo_question[5] == 1) ? '5px;' : '17px;'}} width: 238px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[5]) && $otherinfo_question[5] == 1) ? $otherinfo_questiondetail[5] : ''}}</div>
                        </div>
                        <div style="float: right; margin-right: 17px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[5]) && $otherinfo_question[5] == 0) ? 'checked' : ''}}><span>NO</span><br>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered h-4 bg2" width="65%" style="border-bottom: none !important;">
                        <span>38. a. Have you ever been a candidate in a national or local election held within the last year (except</span><br><span style="margin-left: 17px;">Barangay election)?</span><br>
                    </td>
                    <td class="bordered h-4" width="35%" style="border-bottom: none !important;">
                        <div style="float: left;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[6]) && $otherinfo_question[6] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <span style="margin-left: 5px;">If YES, give details:</span>
                            <span style="margin-left: 93px; {{ ($otherinfo_questiondetail[6] != null) ? 'margin-top: -13px;' : ''}} width: 151px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[6]) && $otherinfo_question[6] == 1) ? $otherinfo_questiondetail[6] : ''}}</span>
                        </div>
                        <div style="float: right; margin-right: 17px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[6]) && $otherinfo_question[6] == 0) ? 'checked' : ''}}><span>NO</span><br>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered h-4 bg2" width="65%" style="border-top: none !important;">
                        <span style="margin-left: 17px;"> b. Have you resigned from the government service during the three (3)-month period before the</span><br><span style="margin-left: 17px;">last election to promote/actively campaign for a national or local candidate?</span><br>
                    </td>
                    <td class="bordered h-4" width="35%" style="border-top: none !important;">
                        <div style="float: left;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[7]) && $otherinfo_question[7] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <span style="margin-left: 5px;">If YES, give details:</span>
                            <span style="margin-left: 93px; {{ ($otherinfo_questiondetail[7] != null) ? 'margin-top: -13px;' : ''}} width: 151px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[7]) && $otherinfo_question[7] == 1) ? $otherinfo_questiondetail[7] : ''}}</span>
                        </div>
                        <div style="float: right; margin-right: 17px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[7]) && $otherinfo_question[7] == 0) ? 'checked' : ''}}><span>NO</span><br>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered h-2 bg2" width="65%" style="border-top: none !important;">
                        <span>39. Have you acquired the status of an immigrant or permanent resident of another country?</span><br>
                    </td>
                    <td class="bordered h-2" width="35%">
                        <div style="float: left;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[8]) && $otherinfo_question[8] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <div style="margin-left: 5px; margin-top: 5px;">If YES, give details: (country): </div>
                            <div style="margin-left: 5px; margin-top: {{ ($otherinfo_question[8] == 1) ? '5px;' : '17px;'}} width: 238px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[8]) && $otherinfo_question[8] == 1) ? $otherinfo_questiondetail[8] : ''}}</div>
                        </div>
                        <div style="float: right; margin-right: 17px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[8]) && $otherinfo_question[8] == 0) ? 'checked' : ''}}><span>NO</span><br>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="bordered h-5 bg2" width="65%" style="border-bottom: none !important;">
                        <span>40. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA</span><br><span style="margin-left: 16px;">7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:</span><br>
                        <br><span style="margin-left: 16px;">a. Are you a member of any indigenous group?</span><br><br>
                        <br><span style="margin-left: 16px;">b. Are you a person with disability?</span><br><br>
                        <br><span style="margin-left: 16px;">c. Are you a solo parent?</span><br><br>
                    </td>
                    <td class="bordered h-5" width="35%" style="border-bottom: none !important;">
                        <div style="float: left; margin-top: 31px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[9]) && $otherinfo_question[9] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <span style="margin-left: 5px;">If YES, please specify:</span>
                            <span style="margin-left: 105px; {{ ($otherinfo_questiondetail[9] != null) ? 'margin-top: -12px;' : ''}} width: 139px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[9]) && $otherinfo_question[9] == 1) ? $otherinfo_questiondetail[9] : ''}}</span>

                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[10]) && $otherinfo_question[10] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <span style="margin-left: 5px;">If YES, please specify:</span>
                            <span style="margin-left: 105px; {{ ($otherinfo_questiondetail[10] != null) ? 'margin-top: -12px;' : ''}} width: 139px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[10]) && $otherinfo_question[10] == 1) ? $otherinfo_questiondetail[10] : ''}}</span>

                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px;" {{ (isset($otherinfo_question[11]) && $otherinfo_question[11] == 1) ? 'checked' : ''}}><span>YES</span><br>
                            <span style="margin-left: 5px;">If YES, please specify:</span>
                            <span style="margin-left: 105px; {{ ($otherinfo_questiondetail[11] != null) ? 'margin-top: -12px;' : ''}} width: 139px; display: inline-block; border-bottom: 1px solid black;">{{ (isset($otherinfo_question[11]) && $otherinfo_question[11] == 1) ? $otherinfo_questiondetail[11] : ''}}</span>
                        </div>
                        <div style="float: right; margin-right: 17px; margin-top: 31px;">
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[9]) && $otherinfo_question[9] == 0) ? 'checked' : ''}}><span>NO</span><br><br><br>
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[10]) && $otherinfo_question[10] == 0) ? 'checked' : ''}}><span>NO</span><br><br><br>
                            <input type="checkbox" class="checkbox1" style="margin-bottom: -8px; margin-left: -130px;" {{ (isset($otherinfo_question[11]) && $otherinfo_question[11] == 0) ? 'checked' : ''}}><span>NO</span><br>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="table">
            <thead>
                <tr>
                    <td class="bordered bg2" colspan="3">41. REFERENCES <span style="font-size: 8px !important;">(Person not related by consanguinity or affinity to applicant /appointee)</span></td>
                    <td rowspan="6" width="27%" style="text-align: center; vertical-align: middle;">
                        <div style="border: 1px solid black; width: 65%; min-height: 150px; padding: 10px; box-sizing: border-box; margin: -18px auto 6px auto; display: block; text-align: center;">
                            <div style="margin-top: 55px;">
                                <span>Passport-sized unfiltered</span><br>
                                <span>digital picture taken within</span><br>
                                <span>the last  6 months</span><br>
                                <span>4.5 cm. X 3.5 cm</span>
                            </div>
                        </div>
                        
                        <span>PHOTO</span>
                    </td>
                </tr>
                <tr>
                    <td class="bordered vcenter bg2" width="120">NAME</td>
                    <td class="bordered vcenter bg2" width="170">OFFICE / RESIDENTIAL ADDRESS</td>
                    <td class="bordered vcenter bg2">CONTACT NO. <br>AND/OR EMAIL</td>
                </tr>
                <tr>
                    <th class="bordered">{{ (isset($refname[0])) ? $refname[0] : ''}}</th>
                    <th class="bordered">{{ (isset($refadd[0])) ? $refadd[0] : ''}}</th>
                    <th class="bordered">{{ (isset($reftelno[0])) ? $reftelno[0] : ''}}</th>
                </tr>
                <tr>
                    <th class="bordered">{{ (isset($refname[1])) ? $refname[1] : ''}}</th>
                    <th class="bordered">{{ (isset($refadd[1])) ? $refadd[1] : ''}}</th>
                    <th class="bordered">{{ (isset($reftelno[1])) ? $reftelno[1] : ''}}</th>
                </tr>
                <tr>
                    <th class="bordered">{{ (isset($refname[2])) ? $refname[2] : ''}}</th>
                    <th class="bordered">{{ (isset($refadd[2])) ? $refadd[2] : ''}}</th>
                    <th class="bordered">{{ (isset($reftelno[2])) ? $reftelno[2] : ''}}</th>
                </tr>
                <tr>
                    <td class="bordered bg2" colspan="3" style="height: 72px !important; font-size: 10px !important;">
                        <span>42. I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and</span><br>
                        <span style="margin-left: 18px;">complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the</span><br>
                        <span style="margin-left: 18px;">Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein.</span><br>
                        <span style="margin-left: 18px;">I  agree that any misrepresentation made in this document and its attachments shall cause the filing of</span><br>
                        <span style="margin-left: 18px;">administrative/criminal case/s against me.</span><br>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="height: 134px !important;">
                        <div style="border: 1px solid black; width: 251px; height: 120px; margin: 6px 6px 6px 6px; float: left;">
                            <div class="bg2" style="height: 25px; border-bottom: 1px solid black; padding: 2px;">
                                <span>Government Issued ID </span><span style="font-size: 8.2px"> (i.e.Passport, GSIS, SSS, PRC, Driver's)</span><br>
                                <span>License, etc.)</span><em style="margin-left: 55px;">PLEASE INDICATE ID Number</em>
                            </div>
                            <div style="height: 25px; border-bottom: 1px solid black; padding: 2px;">
                                <p>Government Issued ID: <span>{{ (isset($govids[0])) ? $govids[0] : ''}}</span></p>
                            </div>                                      
                            <div style="height: 25px; border-bottom: 1px solid black; padding: 2px;">
                                <p>ID/License/Passport No.: <span>{{ (isset($govids[1])) ? $govids[1] : ''}}</span></p>
                            </div>
                            <div style="height: 25px; border-bottom: 1px solid black; padding: 2px;">
                                <p>Date/Place of Issuance: <span>{{ (isset($govids[2])) ? $govids[2] : ''}}</span></p>
                            </div>
                        </div>
                        <div style="border: 1px solid black; width: 251px; height: 120px; margin: 6px -2px 6px 7px; float: right;">
                            <div style="height: 70px; border-bottom: 1px solid black; padding: 2px;">
                                
                            </div>
                            <div class="bg2" style="height: 15px; border-bottom: 1px solid black; text-align: center;">
                                Signature (Sign inside the box)
                            </div>
                            <div style="height: 15px; border-bottom: 1px solid black; text-align: center;">
                                <span style="font-size: 11px;">{{ \Carbon\Carbon::now()->format('m/d/Y') }}</span>
                            </div>
                            
                            <div  class="bg2" style="height: 12.8px; text-align: center;">
                                Date Accomplished
                            </div>
                        </div>
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        <div style="border: 1px solid black; width: 90%; height: 145px; margin: -18.5px auto 6px auto; display: block;">
                            <div style="height: 127px; border-bottom: 1px solid black; padding: 2px; color: #FFFF;">
                                .
                            </div>
                            <div class="bg2" style="height: 13px">
                                Right Thumbmark
                            </div>
                        </div>                        
                    </td>                    
                </tr>
                <tr>
                    <td class="bordered" colspan="4" style="font-size: 10px;  text-align: center; vertical-align: middle;">
                        <span>SUBSCRIBED AND SWORN to before me this</span> <span style="width: 125px; display: inline-block; border-bottom: 1px solid black;"></span>, <span>affiant exhibiting his/her validly issued government ID as indicated above.</span>
                        <div style="border: 1px solid black; width: 40%; height: 80px; margin: 10px auto 10px auto; display: block;">
                            <div style="height: 80%; border-bottom: 1px solid black; padding: 2px; color: #FFFF;">
                                .
                            </div>
                            <div class="bg2" style="height: 10.7px; font-size: 9px; ">
                                Person Administering Oath
                            </div> 
                        </div>                        
                    </td>   
                </tr> 
            </thead>
        </table>
    </div>
</body>
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script('
        if ($PAGE_NUM == $PAGE_COUNT) {
            return;
        }

        $font = $fontMetrics->get_font("DejaVu Sans", "italic");
        $size = 6;
        $color = array(0, 0, 0);

        $total_pages = $PAGE_COUNT - 1;

        $footer_text = "CS FORM 212 (Revised 2025), Page " . $PAGE_NUM . " of " . $total_pages;

        $text_width = $fontMetrics->get_text_width($footer_text, $font, $size);

        // MOVE LEFT (away from page edge)
        $x = $pdf->get_width() - $text_width - 14;

        // Keep vertical position
        $y = $pdf->get_height() - 60;

        $pdf->text($x, $y, $footer_text, $font, $size, $color);
    ');
}
</script>

</html>
