@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card"> 
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
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Date Hired</label>
                                    <input type="date" name="date_hired" class="form-control form-control-sm" id="date_hired">
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Item / Plantilla No.</label>
                                    <input type="text" name="item_no" class="form-control form-control-sm" id="item_no" placeholder="N/A" autocomplete="off">
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Last Name</label><span class="text-danger"> <i class="fas fa-asterisk "></i></span><br>
                                    <input type="text" name="lname" class="form-control form-control-sm" placeholder="N/A" autocomplete="off" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">First Name</label><span class="text-danger"> <i class="fas fa-asterisk "></i></span><br>
                                    <input type="text" name="fname" class="form-control form-control-sm" placeholder="N/A" autocomplete="off" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Middle Name</label><br>
                                    <input type="text" name="mname" class="form-control form-control-sm" placeholder="N/A" autocomplete="off" oninput="this.value = this.value.replace(/\./g, '')">
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Suffix</label><br>
                                    <select class="form-control form-control-sm" name="suffix">
                                        <option disabled selected> Select </option>
                                        <option value="Jr.">Jr.</option>
                                        <option value="Sr.">Sr.</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Prefix</label><br>
                                    <select class="form-control form-control-sm" name="prefix">
                                        <option disabled selected> Select </option>
                                        <option>Atty.</option>
                                        <option>Dr.</option>
                                        <option>Eng.</option>
                                        <option>Mr.</option>
                                        <option>Mrs.</option>
                                        <option>Ms.</option>
                                        <option>Not Applicable</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Title Prefix</label><br>
                                    <input type="text" name="title_prefix" class="form-control form-control-sm" placeholder="e.g MBA/ DPA / MD etc.">
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Birth Date</label>
                                    <input type="date" name="bdate" class="form-control form-control-sm" id="bday" onchange="calculateAge()">
                                </div>

                                <div class="col-md-1">
                                    <label class="badge badge-secondary lbel">Age</label>
                                    <input type="text" name="age" class="form-control form-control-sm" id="age" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Birth Place</label><br>
                                    <input type="text" name="b_place" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Sex</label><span class="text-danger"> <i class="fas fa-asterisk "></i></span><br>
                                    <select class="form-control form-control-sm" name="sex" autocomplete="off" required>
                                        <option disabled selected> Select </option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Civil Status</label><br>
                                    <select class="form-control form-control-sm" name="civil_status">
                                        <option disabled selected> Select </option>
                                        <option>Single</option>
                                        <option>Married</option>
                                        <option>Separated</option>
                                        <option>Widowed</option>
                                        <option value="Other">Other/s</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="badge badge-secondary lbel">Citizenship</label><br>
                                    <div class="row">
                                        <div class="col-4">
                                            <select class="form-control form-control-sm" name="citizenship">
                                                <option disabled selected> Select </option>
                                                <option value="1">Filipino</option>
                                                <option value="2">Dual Citizenship</option>
                                            </select>
                                        </div>
                                        <div class="col-8">
                                            <div style="float: left; display: flex; align-items: center;">
                                                <input class="c-radio" type="radio" name="c_category" id="by-birth">
                                                <span class="c-label" style="width: 90px;">By Birth</span>
                                            </div>
                                            <div style="float: right; display: flex; align-items: center;">
                                                <input class="c-radio" type="radio" name="c_category" id="by-naturalization">
                                                <span class="c-label" style="width: 110px;">By Naturalization</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Country</label><br>
                                    <select class="form-control form-control-sm select2" name="country">
                                        <option value="" disabled selected>Select</option>
                                        <option value="Afghanistan">Afghanistan</option>
                                        <option value="Albania">Albania</option>
                                        <option value="Algeria">Algeria</option>
                                        <option value="Andorra">Andorra</option>
                                        <option value="Angola">Angola</option>
                                        <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                                        <option value="Argentina">Argentina</option>
                                        <option value="Armenia">Armenia</option>
                                        <option value="Australia">Australia</option>
                                        <option value="Austria">Austria</option>
                                        <option value="Azerbaijan">Azerbaijan</option>
                                        <option value="Bahamas">Bahamas</option>
                                        <option value="Bahrain">Bahrain</option>
                                        <option value="Bangladesh">Bangladesh</option>
                                        <option value="Barbados">Barbados</option>
                                        <option value="Belarus">Belarus</option>
                                        <option value="Belgium">Belgium</option>
                                        <option value="Belize">Belize</option>
                                        <option value="Benin">Benin</option>
                                        <option value="Bhutan">Bhutan</option>
                                        <option value="Bolivia">Bolivia</option>
                                        <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                                        <option value="Botswana">Botswana</option>
                                        <option value="Brazil">Brazil</option>
                                        <option value="Brunei">Brunei</option>
                                        <option value="Bulgaria">Bulgaria</option>
                                        <option value="Burkina Faso">Burkina Faso</option>
                                        <option value="Burundi">Burundi</option>
                                        <option value="Cabo Verde">Cabo Verde</option>
                                        <option value="Cambodia">Cambodia</option>
                                        <option value="Cameroon">Cameroon</option>
                                        <option value="Canada">Canada</option>
                                        <option value="Central African Republic">Central African Republic</option>
                                        <option value="Chad">Chad</option>
                                        <option value="Chile">Chile</option>
                                        <option value="China">China</option>
                                        <option value="Colombia">Colombia</option>
                                        <option value="Comoros">Comoros</option>
                                        <option value="Congo">Congo</option>
                                        <option value="Costa Rica">Costa Rica</option>
                                        <option value="Croatia">Croatia</option>
                                        <option value="Cuba">Cuba</option>
                                        <option value="Cyprus">Cyprus</option>
                                        <option value="Czech Republic">Czech Republic</option>
                                        <option value="Democratic Republic of the Congo">Democratic Republic of the Congo</option>
                                        <option value="Denmark">Denmark</option>
                                        <option value="Djibouti">Djibouti</option>
                                        <option value="Dominica">Dominica</option>
                                        <option value="Dominican Republic">Dominican Republic</option>
                                        <option value="Ecuador">Ecuador</option>
                                        <option value="Egypt">Egypt</option>
                                        <option value="El Salvador">El Salvador</option>
                                        <option value="Equatorial Guinea">Equatorial Guinea</option>
                                        <option value="Eritrea">Eritrea</option>
                                        <option value="Estonia">Estonia</option>
                                        <option value="Eswatini">Eswatini</option>
                                        <option value="Ethiopia">Ethiopia</option>
                                        <option value="Fiji">Fiji</option>
                                        <option value="Finland">Finland</option>
                                        <option value="France">France</option>
                                        <option value="Gabon">Gabon</option>
                                        <option value="Gambia">Gambia</option>
                                        <option value="Georgia">Georgia</option>
                                        <option value="Germany">Germany</option>
                                        <option value="Ghana">Ghana</option>
                                        <option value="Greece">Greece</option>
                                        <option value="Grenada">Grenada</option>
                                        <option value="Guatemala">Guatemala</option>
                                        <option value="Guinea">Guinea</option>
                                        <option value="Guinea-Bissau">Guinea-Bissau</option>
                                        <option value="Guyana">Guyana</option>
                                        <option value="Haiti">Haiti</option>
                                        <option value="Honduras">Honduras</option>
                                        <option value="Hungary">Hungary</option>
                                        <option value="Iceland">Iceland</option>
                                        <option value="India">India</option>
                                        <option value="Indonesia">Indonesia</option>
                                        <option value="Iran">Iran</option>
                                        <option value="Iraq">Iraq</option>
                                        <option value="Ireland">Ireland</option>
                                        <option value="Israel">Israel</option>
                                        <option value="Italy">Italy</option>
                                        <option value="Ivory Coast">Ivory Coast</option>
                                        <option value="Jamaica">Jamaica</option>
                                        <option value="Japan">Japan</option>
                                        <option value="Jordan">Jordan</option>
                                        <option value="Kazakhstan">Kazakhstan</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Kiribati">Kiribati</option>
                                        <option value="Kuwait">Kuwait</option>
                                        <option value="Kyrgyzstan">Kyrgyzstan</option>
                                        <option value="Laos">Laos</option>
                                        <option value="Latvia">Latvia</option>
                                        <option value="Lebanon">Lebanon</option>
                                        <option value="Lesotho">Lesotho</option>
                                        <option value="Liberia">Liberia</option>
                                        <option value="Libya">Libya</option>
                                        <option value="Liechtenstein">Liechtenstein</option>
                                        <option value="Lithuania">Lithuania</option>
                                        <option value="Luxembourg">Luxembourg</option>
                                        <option value="Madagascar">Madagascar</option>
                                        <option value="Malawi">Malawi</option>
                                        <option value="Malaysia">Malaysia</option>
                                        <option value="Maldives">Maldives</option>
                                        <option value="Mali">Mali</option>
                                        <option value="Malta">Malta</option>
                                        <option value="Marshall Islands">Marshall Islands</option>
                                        <option value="Mauritania">Mauritania</option>
                                        <option value="Mauritius">Mauritius</option>
                                        <option value="Mexico">Mexico</option>
                                        <option value="Micronesia">Micronesia</option>
                                        <option value="Moldova">Moldova</option>
                                        <option value="Monaco">Monaco</option>
                                        <option value="Mongolia">Mongolia</option>
                                        <option value="Montenegro">Montenegro</option>
                                        <option value="Morocco">Morocco</option>
                                        <option value="Mozambique">Mozambique</option>
                                        <option value="Myanmar">Myanmar</option>
                                        <option value="Namibia">Namibia</option>
                                        <option value="Nauru">Nauru</option>
                                        <option value="Nepal">Nepal</option>
                                        <option value="Netherlands">Netherlands</option>
                                        <option value="New Zealand">New Zealand</option>
                                        <option value="Nicaragua">Nicaragua</option>
                                        <option value="Niger">Niger</option>
                                        <option value="Nigeria">Nigeria</option>
                                        <option value="North Korea">North Korea</option>
                                        <option value="North Macedonia">North Macedonia</option>
                                        <option value="Norway">Norway</option>
                                        <option value="Oman">Oman</option>
                                        <option value="Pakistan">Pakistan</option>
                                        <option value="Palau">Palau</option>
                                        <option value="Panama">Panama</option>
                                        <option value="Papua New Guinea">Papua New Guinea</option>
                                        <option value="Paraguay">Paraguay</option>
                                        <option value="Peru">Peru</option>
                                        <option value="Philippines">Philippines</option>
                                        <option value="Poland">Poland</option>
                                        <option value="Portugal">Portugal</option>
                                        <option value="Qatar">Qatar</option>
                                        <option value="Romania">Romania</option>
                                        <option value="Russia">Russia</option>
                                        <option value="Rwanda">Rwanda</option>
                                        <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                                        <option value="Saint Lucia">Saint Lucia</option>
                                        <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
                                        <option value="Samoa">Samoa</option>
                                        <option value="San Marino">San Marino</option>
                                        <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                                        <option value="Saudi Arabia">Saudi Arabia</option>
                                        <option value="Senegal">Senegal</option>
                                        <option value="Serbia">Serbia</option>
                                        <option value="Seychelles">Seychelles</option>
                                        <option value="Sierra Leone">Sierra Leone</option>
                                        <option value="Singapore">Singapore</option>
                                        <option value="Slovakia">Slovakia</option>
                                        <option value="Slovenia">Slovenia</option>
                                        <option value="Solomon Islands">Solomon Islands</option>
                                        <option value="Somalia">Somalia</option>
                                        <option value="South Africa">South Africa</option>
                                        <option value="South Korea">South Korea</option>
                                        <option value="South Sudan">South Sudan</option>
                                        <option value="Spain">Spain</option>
                                        <option value="Sri Lanka">Sri Lanka</option>
                                        <option value="Sudan">Sudan</option>
                                        <option value="Suriname">Suriname</option>
                                        <option value="Sweden">Sweden</option>
                                        <option value="Switzerland">Switzerland</option>
                                        <option value="Syria">Syria</option>
                                        <option value="Taiwan">Taiwan</option>
                                        <option value="Tajikistan">Tajikistan</option>
                                        <option value="Tanzania">Tanzania</option>
                                        <option value="Thailand">Thailand</option>
                                        <option value="Timor-Leste">Timor-Leste</option>
                                        <option value="Togo">Togo</option>
                                        <option value="Tonga">Tonga</option>
                                        <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                                        <option value="Tunisia">Tunisia</option>
                                        <option value="Turkey">Turkey</option>
                                        <option value="Turkmenistan">Turkmenistan</option>
                                        <option value="Tuvalu">Tuvalu</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Ukraine">Ukraine</option>
                                        <option value="United Arab Emirates">United Arab Emirates</option>
                                        <option value="United Kingdom">United Kingdom</option>
                                        <option value="United States">United States</option>
                                        <option value="Uruguay">Uruguay</option>
                                        <option value="Uzbekistan">Uzbekistan</option>
                                        <option value="Vanuatu">Vanuatu</option>
                                        <option value="Vatican City">Vatican City</option>
                                        <option value="Venezuela">Venezuela</option>
                                        <option value="Vietnam">Vietnam</option>
                                        <option value="Yemen">Yemen</option>
                                        <option value="Zambia">Zambia</option>
                                        <option value="Zimbabwe">Zimbabwe</option>
                                    </select>
                                </div>                                

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Employe Status</label><span class="text-danger"> <i class="fas fa-asterisk "></i></span><br>
                                    <select class="form-control form-control-sm select2" style="width: 100%;" name="emp_status" autocomplete="off" required>
                                        <option value=""> select </option>
                                        @foreach ($stat as $st)
                                            <option value="{{ $st->id }}">{{ $st->status_name }}</option>
                                        @endforeach
                                    </select>
                                </div> 
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Position</label><span class="text-danger"> <i class="fas fa-asterisk "></i></span><br>
                                    <input type="text" name="position" id="position" class="form-control form-control-sm" placeholder="N/A" autocomplete="off" required>
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Department/Office</label><span class="text-danger"> <i class="fas fa-asterisk "></i></span><br>
                                    <select class="form-control form-control-sm select2" style="width: 100%;" name="emp_dept" autocomplete="off" required>
                                        <option value=""> select </option>
                                        @foreach ($offices as $q)
                                            <option value="{{ $q->id }}">{{ $q->office_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Immediate Supervisor</label><br>
                                    <select class="form-control form-control-sm select2" style="width: 100%;" name="supervisor">
                                        <option value="0"> select </option>
                                        @foreach ($supervisor as $sup)
                                            <option value="{{ $sup->id }}">{{ strtoupper($sup->lname) }} {{ strtoupper($sup->fname) }} {{ isset($sup->mname) ? strtoupper(substr($sup->mname, 0, 1)).'.' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Height (cm)</label><br>
                                    <input type="text" name="height_cm" id="height_cm" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Height (ft)</label><br>
                                    <input type="text" name="height_ft" id="height_ft" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Weight (kg)</label><br>
                                    <input type="text" name="weight_kg" id="weight_kg" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Weight (lb)</label><br>
                                    <input type="text" name="weight_lb" id="weight_lb" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Blood Type</label><br>
                                    <select class="form-control form-control-sm" name="b_type">
                                        <option disabled selected> Select </option>
                                        <option>A+</option>
                                        <option>A-</option>
                                        <option>AB+</option>
                                        <option>AB-</option>
                                        <option>B+</option>
                                        <option>B-</option>
                                        <option>O+</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">GSIS</label><br>
                                    <input type="text" name="gsis" class="form-control form-control-sm"  placeholder="N/A" autocomplete="off">
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">PAGIBIG</label><br>
                                    <input type="text" name="pagibig" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">PHILHEALTH</label><br>
                                    <input type="text" name="philhealth" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">SSS</label><br>
                                    <input type="text" name="sss" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">TIN</label><br>
                                    <input type="text" name="tin" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Telephone Number</label><br>
                                    <input type="text" name="telephone" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Email Address</label><br>
                                    <input type="text" name="org_email" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Mobile Number</label><br>
                                    <input type="text" name="mobile" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>

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
                                    <select id="region" name="add_region" class="form-control select2 form-control-sm" style="width: 100%;">
                                        <option value="">Select</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->region_id }}">{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Province:</label>
                                    <select id="province" name="add_prov" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">City / Municipality:</label>
                                    <select id="city" name="add_city" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Barangay:</label>
                                    <select id="barangay" name="add_brgy" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">House/Block/Lot No.</label><br>
                                    <input type="text" name="add_block" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Street</label><br>
                                    <input type="text" name="add_street" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Subdivision/Village</label><br>
                                    <input type="text" name="add_village" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">ZIP Code</label><br>
                                    <input type="number" name="add_zcode" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
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
                                    <select id="region1" name="padd_region" class="form-control select2 form-control-sm" style="width: 100%;">
                                        <option value="">Select</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->region_id }}">{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Province:</label>
                                    <select id="province1" name="padd_prov" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">City / Municipality:</label>
                                    <select id="city1" name="padd_city" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Barangay:</label>
                                    <select id="barangay1" name="padd_brgy" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">House/Block/Lot No.</label><br>
                                    <input type="text" name="padd_block" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Street</label><br>
                                    <input type="text" name="padd_street" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Subdivision/Village</label><br>
                                    <input type="text" name="padd_village" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">ZIP Code</label><br>
                                    <input type="number" name="padd_zcode" class="form-control form-control-sm" placeholder="N/A" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-row float-right">
                                <div class="col-md-12">
                                    <button type="submit" name="btn-submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('emp.modal-camera')

@endsection