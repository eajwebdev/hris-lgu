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
                        <b>OTHER INFORMATION QUESTION</b>
                    </h2>
                </div>
                @php
                    $question = explode(',', $infoquestion->question);
                    $qdetails = explode(',', $infoquestion->qdetails);
                    $refname = explode(',', $infoquestion->refname);
                    $refadd = explode(',', $infoquestion->refadd);
                    $reftelno = explode(',', $infoquestion->reftelno);
                    $govid = explode(',', $infoquestion->govid);
                @endphp
                <div class="card-body">
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-12"><p class="text-success1"><b>A. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office,</b></p>
                                <div class="row">
                                    <div class="col-12">
                                        <p class="text-muted"><b>1. Within the third degree?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_0" data-array="0" id="no-0" value="0" {{ ($question[0] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no-0">No</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_0" data-array="0" id="yes-0" value="1" {{ ($question[0] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes-0">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="input-details updated-data" type="hidden" name="qdetails_0" data-array="0" value="{{ $qdetails[0] }}" id="details-0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>2. Within the fourth degree (for Local Government Unit - Career Employees)?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_1" data-array="1" id="no-1" value="0" {{ ($question[1] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_1" data-array="1" id="yes-1" value="1" {{ ($question[1] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, give details: <input class="input-details updated-data" type="text" name="qdetails_1" data-array="1" value="{{ $qdetails[1] }}" id="details-1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>3. Have you ever been found guilty of any administrative offense?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_2" data-array="2" id="no-2" value="0" {{ ($question[2] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_2" data-array="2" id="yes-2" value="1" {{ ($question[2] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, give details: <input class="input-details updated-data" type="text" name="qdetails_2" data-array="2" value="{{ $qdetails[2] }}" id="details-2">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>4. Have you been criminally charged before any court?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_3" data-array="3" id="no-3" value="0" {{ ($question[3] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_3" data-array="3" id="yes-3" value="1" {{ ($question[3] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                Date Filed: <input class="input-details updated-data" type="date" name="qdetails_3" data-array="12" value="{{ $qdetails[12] }}" id="details-3">
                                            
                                                Status of Case/s: <input class="input-details updated-data" type="text" name="qdetails_3" data-array="3" value="{{ $qdetails[3] }}" id="details-3">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>5. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_4" data-array="4" id="no-4" value="0" {{ ($question[4] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_4" data-array="4" id="yes-4" value="1" {{ ($question[4] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, give details: <input class="input-details updated-data" type="text" name="qdetails_4" data-array="4" value="{{ $qdetails[4] }}" id="details-4">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>6. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_5" data-array="5" id="no-5" value="0" {{ ($question[5] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_5" data-array="5" id="yes-5" value="1" {{ ($question[5] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, give details: <input class="input-details updated-data" type="text" name="qdetails_5" data-array="5" value="{{ $qdetails[5] }}" id="details-5">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>7. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_6" data-array="6" id="no-6" value="0" {{ ($question[6] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_6" data-array="6" id="yes-6" value="1" {{ ($question[6] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, give details: <input class="input-details updated-data" type="text" name="qdetails_6" data-array="6" value="{{ $qdetails[6] }}" id="details-6">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>8. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_7" data-array="7" id="no-7" value="0" {{ ($question[7] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_7" data-array="7" id="yes-7" value="1" {{ ($question[7] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, give details: <input class="input-details updated-data" type="text" name="qdetails_7" data-array="7" value="{{ $qdetails[7] }}" id="details-7">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>9. Have you acquired the status of an immigrant or permanent resident of another country?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_8" data-array="8" id="no-8" value="0" {{ ($question[8] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_8" data-array="8" id="yes-8" value="1" {{ ($question[8] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, give details (country):  <input class="input-details updated-data" type="text" name="qdetails_8" data-array="8" value="{{ $qdetails[8] }}" id="details-8">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2"> 
                                        <p class="text-success1"><b>B. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:</b></p>
                                    </div>    
                                    <div class="col-12">
                                        <p class="text-muted"><b>1. Are you a member of any indigenous group?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_9" data-array="9" id="no-9" value="0" {{ ($question[9] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_9" data-array="9" id="yes-9" value="1" {{ ($question[9] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, please specify: <input class="input-details updated-data" type="text" name="qdetails_9" data-array="9" value="{{ $qdetails[9] }}" id="details-9">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>2. Are you a person with disability?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_10" data-array="10" id="no-10" value="0" {{ ($question[10] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_10" data-array="10" id="yes-10" value="1" {{ ($question[10] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, please specify: <input class="input-details updated-data" type="text" name="qdetails_10" data-array="10" value="{{ $qdetails[10] }}" id="details-10">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted"><b>3. Are you a solo parent?</b></p>
                                        <div class="d-flex mtop">
                                            <div class="form-check mr-1">
                                                <input class="form-check-input updated-data" type="radio" name="question_11" data-array="11" id="no-11" value="0" {{ ($question[11] == 0) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="no">
                                                    No
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input updated-data" type="radio" name="question_11" data-array="11" id="yes-11" value="1" {{ ($question[11] == 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                If Yes, please specify: <input class="input-details updated-data" type="text" name="qdetails_11" data-array="11" value="{{ $qdetails[11] }}" id="details-11">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                    
                </div>
            </div>                        
        </div>
    </div>
</div>
</section>
@endsection