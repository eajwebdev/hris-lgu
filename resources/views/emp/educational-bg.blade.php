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
                        <b>EDUCATIONAL BACKGROUND</b>
                    </h2>
                </div>
                <div class="card-body bg-form">
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2">
                                    <b>ELEMENTARY</b>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-4"> 
                                <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                <input type="text" value="{{ $educBg->elem_school }}" name="elem_school" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                                <input type="text" value="{{ $educBg->elem_period }}" name="elem_period" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="ex: 2021-2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                                <input type="text" value="{{ $educBg->elem_level }}" name="elem_level" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                <input type="number" value="{{ $educBg->elem_grad }}" name="elem_grad" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                <input type="text" value="{{ $educBg->elem_honor }}" name="elem_honor" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2">
                                    <b>SECONDARY</b>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                <input type="text" value="{{ $educBg->sec_school }}" name="sec_school" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                                <input type="text" value="{{ $educBg->sec_period }}" name="sec_period" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="ex: 2021 - 2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                                <input type="text" value="{{ $educBg->sec_level }}" name="sec_level" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                <input type="number" value="{{ $educBg->sec_grad }}" name="sec_grad" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                <input type="text" value="{{ $educBg->sec_honor }}" name="sec_honor" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2">
                                    <b>VOCATIONAL / TRADE COURSE</b>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                <input type="text" value="{{ $educBg->voc_school }}" name="voc_school" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Basic Education/Degree/Course</label>
                                <input type="text" value="{{ $educBg->voc_course }}" name="voc_course" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                                <input type="text" value="{{ $educBg->voc_period }}" name="voc_period" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="ex: 2021 - 2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                                <input type="text" value="{{ $educBg->voc_level }}" name="voc_level" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                <input type="number" value="{{ $educBg->voc_grad }}" name="voc_grad" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                <input type="text" value="{{ $educBg->voc_honor }}" name="voc_honor" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2 w-100 d-flex justify-content-between align-items-center">
                                    <b>COLLEGE</b>
                                    <button class="btn btn-success btn-sm" id="add-row-college">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </h2>
                            </div>
                        </div>
                    </div>
                    
                    @php
                        $schools = explode(',', $educBg->coll_school);
                        $courses = explode(',', $educBg->coll_course);
                        $periods = explode(',', $educBg->coll_period);
                        $levels = explode(',', $educBg->coll_level);
                        $years = explode(',', $educBg->coll_grad);
                        $honors = explode(',', $educBg->coll_honor);

                        $gradSchools = explode(',', $educBg->grad_school);
                        $gradCourses = explode(',', $educBg->grad_course);
                        $gradPeriods = explode(',', $educBg->grad_period);
                        $gradLevels = explode(',', $educBg->grad_level);
                        $gradYears = explode(',', $educBg->grad_grad);
                        $gradHonors = explode(',', $educBg->grad_honor);
                    @endphp
                    
                    <div id="college-container">
                        @foreach($schools as $index => $school)
                            <div class="form-group mtop college-div" data-index="{{ $index }}">
                                <div class="form-row mt-3 lbel">
                                    @if($index > 0)
                                        <div class="col-md-12 mt-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete" style="float: right;">
                                                <i class="fas fa-times fa-sm"></i>
                                            </button>
                                        </div>
                                    @endif
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                        <input type="text" value="{{ trim($school) }}" name="coll_school[]" class="form-control form-control-sm update-child" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        @if($loop->first)
                                            <label class="badge badge-secondary text-wrap lbel">Basic Education/Degree/Course</label>
                                        @else
                                            <label class="badge badge-secondary text-wrap lbel">Degree/Course</label>
                                        @endif
                                        <input type="text" value="{{ trim($courses[$index] ?? '') }}" name="coll_course[]" class="form-control form-control-sm update-child" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Period of Attendance</label>
                                        <input type="text" value="{{ trim($periods[$index] ?? '') }}" name="coll_period[]" class="form-control form-control-sm update-child" placeholder="ex: 2021 - 2024" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                                        <input type="text" value="{{ trim($levels[$index] ?? '') }}" name="coll_level[]" class="form-control form-control-sm update-child" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                        <input type="number" value="{{ trim($years[$index] ?? '') }}" name="coll_grad[]" class="form-control form-control-sm update-child" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                        <input type="text" value="{{ trim($honors[$index] ?? '') }}" name="coll_honor[]" class="form-control form-control-sm update-child" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                                  
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2 w-100 d-flex justify-content-between align-items-center">
                                    <b>GRADUATE STUDIES</b>
                                    <button class="btn btn-success btn-sm" id="add-row-graduate">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div id="graduate-container">
                        @foreach($gradSchools as $index => $school)
                            <div class="form-group mtop graduate-div" data-index="{{ $index }}">
                                <div class="form-row mt-3 lbel">
                                    @if($index > 0)
                                        <div class="col-md-12 mt-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-grad" style="float: right;">
                                                <i class="fas fa-times fa-sm"></i>
                                            </button>
                                        </div>
                                    @endif
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                        <input type="text" value="{{ trim($school) }}" name="grad_school[]" class="form-control form-control-sm update-grad" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Basic Education/Degree/Course</label>
                                        <input type="text" value="{{ trim($gradCourses[$index] ?? '') }}" name="grad_course[]" class="form-control form-control-sm update-grad" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Period of Attendance</label>
                                        <input type="text" value="{{ trim($gradPeriods[$index] ?? '') }}" name="grad_period[]" class="form-control form-control-sm update-grad" placeholder="ex: 2021 - 2024" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                                        <input type="text" value="{{ trim($gradLevels[$index] ?? '') }}" name="grad_level[]" class="form-control form-control-sm update-grad" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                        <input type="number" value="{{ trim($gradYears[$index] ?? '') }}" name="grad_grad[]" class="form-control form-control-sm update-grad" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                    
                                    <div class="col-md-4">
                                        <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                        <input type="text" value="{{ trim($gradHonors[$index] ?? '') }}" name="grad_honor[]" class="form-control form-control-sm update-grad" placeholder="N/A" data-index="{{ $index }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
<script>
    function validateDateRange(input) {
        const value = input.value;
        const regex = /^\d{4}-\d{4}$/;
        if (regex.test(value)) {
            const [startYear, endYear] = value.split('-').map(Number);
            if (startYear < 1900 || endYear > 2099 || startYear > endYear) {
                input.setCustomValidity('Please enter a valid year range (YYYY-YYYY).');
                input.reportValidity();
            } else {
                input.setCustomValidity('');
            }
        } else {
            input.setCustomValidity('Please enter the date range in YYYY-YYYY format.');
            input.reportValidity();
        }
    }
    
    function restrictInput(input) {
        input.value = input.value.replace(/[^0-9-]/g, '');
    }
</script>
@endsection