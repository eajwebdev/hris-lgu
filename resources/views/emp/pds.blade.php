@extends('layouts.master')

@section('body')
@include('emp.style')
<section class="content">
<div class="container-fluid">
    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>PERSONAL INFORMATION</b>
                    </h2>
                </div>
                <div class="card-body bg-form">
                    <form class="form-horizontal add-form" action="{{ route('empCreate') }}" method="POST">
                        @csrf
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Date Hired</label>
                                    <input type="date" value="{{ $employee->date_hired }}" name="date_hired" data-column-id="{{ $empid }}" data-column-name="date_hired" name="date_hired" class="form-control form-control-sm update-field" id="date_hired">
                                </div>
                                @if($guard == "web")
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Item / Plantilla No.</label>
                                    <input type="text" value="{{ $employee->item_no }}" name="item_no" data-column-id="{{ $empid }}" data-column-name="item_no" name="item_no" class="form-control form-control-sm update-field" id="item_no" placeholder="N/A">
                                </div>
                                @endif
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Last Name</label><br>
                                    <input type="text" value="{{ $employee->lname }}" name="lname" data-column-id="{{ $empid }}" data-column-name="lname" class="form-control form-control-sm update-field" placeholder="N/A" required>
                                </div>
                                <div class="col-md-3">  
                                    <label class="badge badge-secondary lbel">First Name</label><br>
                                    <input type="text" value="{{ $employee->fname }}" name="fname" data-column-id="{{ $empid }}" data-column-name="fname" name="fname" class="form-control form-control-sm update-field" placeholder="N/A" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Middle Name</label><br>
                                    <input type="text" value="{{ $employee->mname }}" name="mname" data-column-id="{{ $empid }}" data-column-name="mname" name="mname" class="form-control form-control-sm update-field"  placeholder="N/A" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Suffix</label><br>
                                    <select class="form-control form-control-sm update-field" name="suffix" required>
                                        <option value="" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "") selected @endif>N/A</option>
                                        <option value="Jr." data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "Jr.") selected @endif>Jr.</option>
                                        <option value="Sr." data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "Sr.") selected @endif>Sr.</option>
                                        <option value="I" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "I") selected @endif>I</option>
                                        <option value="II" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "II") selected @endif>II</option>
                                        <option value="III" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "III") selected @endif>III</option>
                                        <option value="IV" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "IV") selected @endif>IV</option>
                                        <option value="V" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "V") selected @endif>V</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Prefix</label><br>
                                    <select class="form-control form-control-sm update-field" name="prefix" required>
                                        <option value="" data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "") selected @endif>N/A</option>
                                        <option value="Ph.D." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Ph.D.") selected @endif>Ph.D.</option>
                                        <option value="Atty." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Atty.") selected @endif>Atty.</option>
                                        <option value="Dr." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Dr.") selected @endif>Dr.</option>
                                        <option value="Engr." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Engr.") selected @endif>Engr.</option>
                                        <option value="RChE." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "RChE.") selected @endif>RChE.</option>
                                        <option value="J.D." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "J.D.") selected @endif>J.D.</option>
                                        <option value="M.S.W." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "M.S.W.") selected @endif>M.S.W.</option>
                                        <option value="C.P.A." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "C.P.A.") selected @endif>C.P.A.</option>
                                        <option value="C.L.E.A." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "C.L.E.A.") selected @endif>C.L.E.A.</option>
                                        <option value="DIT." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "DIT.") selected @endif>DIT.</option>
                                    </select>                                    
                                </div>   

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Title Prefix</label><br>
                                    <select class="form-control form-control-sm update-field" name="title_prefix" data-column-id="{{ $empid }}" data-column-name="title_prefix">
                                        <option value="" @if($employee->title_prefix == "") selected @endif>N/A</option>
                                        <option value="MBA" @if($employee->title_prefix == "MBA") selected @endif>MBA</option>
                                        <option value="DPA" @if($employee->title_prefix == "DPA") selected @endif>DPA</option>
                                        <option value="MPA" @if($employee->title_prefix == "MPA") selected @endif>MPA</option>
                                        <option value="MD" @if($employee->title_prefix == "MD") selected @endif>MD</option>
                                        <option value="RN" @if($employee->title_prefix == "RN") selected @endif>RN</option>
                                        <option value="LLM" @if($employee->title_prefix == "LLM") selected @endif>LLM</option>
                                        <option value="MSW" @if($employee->title_prefix == "MSW") selected @endif>MSW</option>
                                        <option value="CPA" @if($employee->title_prefix == "CPA") selected @endif>CPA</option>
                                        <option value="DIT" @if($employee->title_prefix == "DIT") selected @endif>DIT</option>
                                        <option value="CNA" @if($employee->title_prefix == "CNA") selected @endif>CNA</option>
                                        <option value="CHRP" @if($employee->title_prefix == "CHRP") selected @endif>CHRP</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Birth Date</label>
                                    <input type="date" value="{{ $employee->bdate }}" name="bdate" data-column-id="{{ $empid }}" data-column-name="bdate" name="bdate" class="form-control form-control-sm update-field" id="bday" onchange="calculateAge()">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Age</label>
                                    <input type="text" value="{{ \Carbon\Carbon::parse($employee->bdate)->diffInYears(now()) }}" name="age" class="form-control form-control-sm" id="age" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Birth Place</label><br>
                                    <input type="text" value="{{ $employee->b_place }}" name="b_place" data-column-id="{{ $empid }}" data-column-name="b_place" name="b_place" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Sex</label><br>
                                    <select class="form-control form-control-sm update-field" name="sex" required>
                                        <option disabled selected> Select </option>
                                        <option value="Male" data-column-id="{{ $empid }}" data-column-name="sex" @if($employee->sex == "Male") selected @endif>Male</option>
                                        <option value="Female" data-column-id="{{ $empid }}" data-column-name="sex" @if($employee->sex == "Female") selected @endif>Female</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Civil Status</label><br>
                                    <select class="form-control form-control-sm update-field" name="civil_status">
                                        <option disabled selected> Select </option>
                                        <option value="Single" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Single") selected @endif>Single</option>
                                        <option value="Married" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Married") selected @endif>Married</option>
                                        <option value="Separated" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Separated") selected @endif>Separated</option>
                                        <option value="Widowed" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Widowed") selected @endif>Widowed</option>
                                        <option value="Other" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Other") selected @endif>Other/s</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="badge badge-secondary lbel">Citizenship</label><br>
                                    <div class="row">
                                        <div class="col-4">
                                            <select class="form-control form-control-sm update-field" name="citizenship">
                                                <option disabled selected> Select </option>
                                                <option value="1" data-column-id="{{ $empid }}" data-column-name="citizenship" @if($employee->citizenship == 1) selected @endif>Filipino</option>
                                                <option value="2" data-column-id="{{ $empid }}" data-column-name="citizenship" @if($employee->citizenship == 2) selected @endif>Dual Citizenship</option>
                                            </select>
                                        </div>
                                        <div class="col-8">
                                            <div style="float: left; display: flex; align-items: center;">
                                                <input class="c-radio update-field" value="1" type="radio" name="c_category" data-column-id="{{ $empid }}" data-column-name="c_category" id="by-birth" @if($employee->c_category == 1) checked @endif>&nbsp;
                                                <span class="c-label" style="width: 110px;">By Birth </span>
                                            </div>
                                            <div style="float: left; display: flex; align-items: center;">
                                                &nbsp;<input class="c-radio update-field" value="2" type="radio" name="c_category" data-column-id="{{ $empid }}" data-column-name="c_category" id="by-naturalization"  @if($employee->c_category == 2) checked @endif>&nbsp;
                                                <span class="c-label" style="width: 110px;">By Naturalization</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Country</label><br>
                                    <select class="form-control form-control-sm update-field" name="country">
                                        <option value="" disabled selected>Select</option>
                                        @foreach([
                                            'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan',
                                            'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana',
                                            'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia', 'Cameroon', 'Canada', 'Central African Republic', 'Chad', 'Chile',
                                            'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Democratic Republic of the Congo', 'Denmark',
                                            'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia',
                                            'Fiji', 'Finland', 'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana',
                                            'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy', 'Ivory Coast', 'Jamaica', 'Japan',
                                            'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein',
                                            'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico',
                                            'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands',
                                            'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Korea', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau', 'Panama', 'Papua New Guinea',
                                            'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia',
                                            'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone',
                                            'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Korea', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan',
                                            'Suriname', 'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago',
                                            'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay',
                                            'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe'
                                        ] as $country)
                                            <option value="{{ $country }}" data-column-id="{{ $empid }}" data-column-name="country" 
                                                @if($employee->country == $country) selected @endif>
                                                {{ $country }}
                                            </option>
                                        @endforeach
                                    </select>
                                    
                                </div>   
                                @if($guard == "web")
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Employee Status</label><br>
                                    <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="emp_status" required>
                                        <option value=""> select </option>
                                        @foreach ($stat as $st)
                                            <option value="{{ $st->id }}" data-column-id="{{ $empid }}" data-column-name="emp_status" @if($employee->emp_status == $st->id) selected @endif>{{ $st->status_name }}</option>
                                        @endforeach
                                    </select>                                    
                                </div> 
                                @endif
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Position</label><br>
                                    <input type="text" value="{{ $employee->position }}" name="position" data-column-id="{{ $empid }}" data-column-name="position" id="position" name="position" id="position" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Department/Office</label><br>
                                    <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="emp_dept">
                                        <option value=""> select </option>
                                        @foreach ($offices as $of)
                                            <option  value="{{ $of->id }}" data-column-id="{{ $empid }}" data-column-name="emp_dept" @if($employee->emp_dept == $of->id) selected @endif>{{ $of->office_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Immediate Supervisor</label><br>
                                    <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="supervisor">
                                        <option value="0" data-column-id="{{ $empid }}" data-column-name="supervisor"> select </option>
                                        @foreach ($supervisor as $sup)
                                            <option value="{{ $sup->id }}" data-column-id="{{ $empid }}" data-column-name="supervisor" @if($employee->supervisor == $sup->id) selected @endif>{{ strtoupper($sup->lname) }} {{ strtoupper($sup->fname) }} {{ strtoupper($sup->mname) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Height (cm)</label><br>
                                    <input type="text" name="height_cm" id="height_cm" value="{{ $employee->height_cm }}" data-column-id="{{ $empid }}" data-column-name="height_cm" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Height (m)</label><br>
                                    <input type="text" name="height_m" id="height_m" value="{{ $employee->height_m }}" data-column-id="{{ $empid }}" data-column-name="height_m" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Weight (kg)</label><br>
                                    <input type="text" name="weight_kg" id="weight_kg" value="{{ $employee->weight_kg }}" data-column-id="{{ $empid }}" data-column-name="weight_kg" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Weight (lb)</label><br>
                                    <input type="text" name="weight_lb" id="weight_lb" value="{{ $employee->weight_lb }}" data-column-id="{{ $empid }}" data-column-name="weight_lb" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-{{ ($guard == 'employee') ? 3 : 1 }}">
                                    <label class="badge badge-secondary lbel">Blood Type</label><br>
                                    <select class="form-control form-control-sm update-field" name="b_type">
                                        <option disabled selected> Select </option>
                                        <option value="A+" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "A+") selected @endif>A+</option>
                                        <option value="A-" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "A-") selected @endif>A-</option>
                                        <option value="AB+" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "AB+") selected @endif>AB+</option>
                                        <option value="AB-" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "AB-") selected @endif>AB-</option>
                                        <option value="B+" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "B+") selected @endif>B+</option>
                                        <option value="B-" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "B-") selected @endif>B-</option>
                                        <option value="O+" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "O+") selected @endif>O+</option>
                                        <option value="" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "") selected @endif>N/A</option>
                                    </select>
                                </div>

                                <div class="col-md-{{ ($guard == 'employee') ? 3 : 2 }}">
                                    <label class="badge badge-secondary lbel">GSIS</label><br>
                                    <input type="text" name="gsis" id="gsis" value="{{ $employee->gsis }}" data-column-id="{{ $empid }}" data-column-name="gsis" class="form-control form-control-sm update-field"  placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">PAGIBIG</label><br>
                                    <input type="text" name="pagibig" id="pagibig" value="{{ $employee->pagibig }}" data-column-id="{{ $empid }}" data-column-name="pagibig" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">PHILHEALTH</label><br>
                                    <input type="text" name="philhealth" id="philhealth" value="{{ $employee->philhealth }}" data-column-id="{{ $empid }}" data-column-name="philhealth" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">UMID ID NO.</label><br>
                                    <input type="text" name="sss" id="sss" value="{{ $employee->sss }}" data-column-id="{{ $empid }}" data-column-name="sss" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">TIN</label><br>
                                    <input type="text" name="tin" id="tin" value="{{ $employee->tin }}" data-column-id="{{ $empid }}" data-column-name="tin" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Telephone Number</label><br>
                                    <input type="text" name="telephone" id="telephone" value="{{ $employee->telephone }}" data-column-id="{{ $empid }}" data-column-name="telephone" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Email Address</label><br>
                                    <input type="email" name="org_email" id="org_email" value="{{ $employee->org_email }}" data-column-id="{{ $empid }}" data-column-name="org_email" class="form-control form-control-sm {{ ($guard !== 'employee') ? 'update-field' : ''}}" placeholder="N/A" @if($guard == 'employee') readonly @endif>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Mobile Number</label><br>
                                    <input type="text" name="mobile" id="mobile" value="{{ $employee->mobile }}" data-column-id="{{ $empid }}" data-column-name="mobile" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                @if($guard == "web")
                                    <div class="col-md-3">
                                        <label class="badge badge-secondary lbel">Salary</label><br>
                                        <input type="text" name="emp_salary" id="emp_salary" value="{{ $employee->emp_salary }}" data-column-id="{{ $empid }}" data-column-name="emp_salary" class="form-control form-control-sm update-field" placeholder="N/A">
                                    </div>
                                @endif
                                
                                {{-- <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Biometric Area</label><br>
                                    <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="supervisor">
                                        @foreach ($devices as $dev)
                                            <option value="{{ $dev->id }}" data-column-id="{{ $empid }}" data-column-name="area_id" @if($employee->area_id == $dev->id) selected @endif>{{ $dev->label }}</option>
                                        @endforeach
                                    </select>
                                </div> --}}
                            </div>
                        </div>
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <h2 class="card-title text-success1 mt-3 mb-2">
                                        <b>RESIDENTIAL ADDRESS</b>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Region:</label>
                                    <select id="region" name="add_region" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option value="">Select</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->region_id }}" data-column-id="{{ $empid }}" data-column-name="add_region" @if($employee->add_region == $region->region_id) selected @endif>{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Province:</label>
                                    <select id="province" name="add_prov" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        @foreach($hprovinces as $province)
                                            <option value="{{ $province->province_id }}" data-column-id="{{ $empid }}" data-column-name="add_prov" @if($employee->add_prov == $province->province_id) selected @endif>{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">City / Municipality:</label>
                                    <select id="city" name="add_city" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        @foreach($hcities as $city)
                                            <option value="{{ $city->city_id }}" data-column-id="{{ $empid }}" data-column-name="add_city" @if($employee->add_city == $city->city_id) selected @endif>{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Barangay:</label>
                                    <select id="barangay" name="add_brgy" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        <option value="{{ (isset($employee->add_brgy)) ? $employee->add_brgy : '' }}" 
                                                data-column-id="{{ $empid }}" 
                                                data-column-name="add_brgy" 
                                                selected>
                                            {{ (isset($employee->add_brgy) && $hbarangays) ? $hbarangays->name : 'N/A' }}
                                        </option>
                                    </select>
                                </div>                                

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">House/Block/Lot No.</label><br>
                                    <input type="text" name="add_block" id="add_block" value="{{ $employee->add_block }}" data-column-id="{{ $empid }}" data-column-name="add_block" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Street</label><br>
                                    <input type="text" name="add_street" id="add_street" value="{{ $employee->add_street }}" data-column-id="{{ $empid }}" data-column-name="add_street" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Subdivision/Village</label><br>
                                    <input type="text" name="add_village" id="add_village" value="{{ $employee->add_village }}" data-column-id="{{ $empid }}" data-column-name="add_village" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">ZIP Code</label><br>
                                    <input type="number" name="add_zcode" id="add_zcode" value="{{ $employee->add_zcode }}" data-column-id="{{ $empid }}" data-column-name="add_zcode" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <h2 class="card-title text-success1 mt-3 mb-2">
                                        <b>PERMANENT ADDRESS</b>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Region:</label>
                                    <select id="region1" name="padd_region" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option value="">Select</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->region_id }}" data-column-id="{{ $empid }}" data-column-name="padd_region" @if($employee->padd_region == $region->region_id) selected @endif>{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Province: </label>
                                    <select id="province1" name="padd_prov" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        @foreach($gprovinces as $province)
                                            <option value="{{ $province->province_id }}" data-column-id="{{ $empid }}" data-column-name="padd_prov" @if($employee->padd_prov == $province->province_id) selected @endif>{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">City / Municipality:</label>
                                    <select id="city1" name="padd_city" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        @foreach($gcities as $city)
                                            <option value="{{ $city->city_id }}" data-column-id="{{ $empid }}" data-column-name="padd_city" @if($employee->padd_city == $city->city_id) selected @endif>{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Barangay:</label>
                                    <select id="barangay1" name="padd_brgy" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        <option value="{{ (isset($employee->padd_brgy)) ? $employee->padd_brgy : '' }}" 
                                                data-column-id="{{ $empid }}" 
                                                data-column-name="padd_brgy" 
                                                selected>
                                            {{ (isset($employee->padd_brgy) && $gbarangays) ? $gbarangays->name : 'N/A' }}
                                        </option>
                                    </select>
                                </div>                                

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">House/Block/Lot No.</label><br>
                                    <input type="text" name="padd_block" id="padd_block" value="{{ $employee->padd_block }}" data-column-id="{{ $empid }}" data-column-name="padd_block" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Street</label><br>
                                    <input type="text" name="padd_street" id="padd_street" value="{{ $employee->padd_street }}" data-column-id="{{ $empid }}" data-column-name="padd_street" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Subdivision/Village</label><br>
                                    <input type="text" name="padd_village" id="padd_village" value="{{ $employee->padd_village }}" data-column-id="{{ $empid }}" data-column-name="padd_village" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">ZIP Code</label><br>
                                    <input type="number" name="padd_zcode" id="padd_zcode" value="{{ $employee->padd_zcode }}" data-column-id="{{ $empid }}" data-column-name="padd_zcode" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            </div>
                        </div>  
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection