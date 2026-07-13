@extends('layouts.master')

@section('body')
@include('emp.style')
<style>
    th,td{
        padding: 3px !important;
    }
    .modal-body img {
        max-width: 100%; 
        height: auto; 
        max-height: 80vh; 
    }
    .scrollable {
        height: 600px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 10px;
    }
    .custom-modal {
        max-width: 80%;
    }
</style>
<section class="content">
<div class="container-fluid">
    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>WORK EXPERIENCE</b>
                    </h2>
                </div>
                <div class="card-body">
                    <div id="accordion">
                        <div class="card card-muted">
                            <div class="card-header">
                                <h4 class="card-title w-100">
                                    <a class="d-block w-100 collapsed text-success1" data-toggle="collapse" href="#collapseOne" aria-expanded="false">
                                        <b>FORM</b>
                                    </a>
                                </h4>
                                <i class="fas fa-arrow-left toggle-icon" style="cursor: pointer; float: right; margin-top: -15px;" data-toggle="collapse" data-target="#collapseOne"></i>                   
                            </div>
                            <div id="collapseOne" class="collapse {{ (count($workexperience) > 0 && !isset($workexperienceedit)) ? '' : 'show' }}" data-parent="#accordion">
                             
                            <div class="card-body bg-form">
                                <form class="form-horizontal" action="{{ isset($workexperienceedit) ? route('workexperienceUpdate', $workexperienceedit->id) : route('workexperienceCreate') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @if(isset($workexperienceedit))
                                        <input type="hidden" name="id" value="{{ $workexperienceedit->id }}">
                                    @endif
                                    <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">
                                    
                                    <div class="form-row lbel mtop">
                                        <div class="col-md-6">
                                            <label class="badge badge-secondary text-wrap text-center lbel">Inclusive Dates</label>
                                            <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">
                                            <div style="display: flex; justify-content: space-between;">
                                                <input type="date" id="inc_date1" name="inc_date1" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($workexperienceedit) ? $workexperienceedit->inc_date1 : '' }}" autocomplete="off" style="flex: 1; margin-right: 5px;" required>
                                                <input type="date" id="inc_date2" name="inc_date2" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($workexperienceedit) ? $workexperienceedit->inc_date2 : '' }}" autocomplete="off" style="flex: 1; margin-left: 5px;">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2">       
                                            <label class="badge badge-secondary text-wrap lbel">Immediate Supervisor</label>
                                            <input type="text" name="supervisor" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($workexperienceedit) ? $workexperienceedit->supervisor : '' }}" autocomplete="off">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="badge badge-secondary text-wrap text-center lbel">Position Title (Write in full/Do not abbreviate)</label>
                                            <input type="text" name="position" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($workexperienceedit) ? $workexperienceedit->position : '' }}" autocomplete="off" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="badge badge-secondary text-wrap lbel">Department / Agency / Office / Company (Write in full/Do not abbreviate)</label>
                                            <input type="text" name="department" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($workexperienceedit) ? $workexperienceedit->department : '' }}" autocomplete="off" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="badge badge-secondary text-wrap lbel">Salary/ Job/ Pay Grade (if applicable)& STEP  (Format "00-0")/ Increment</label>
                                            <input type="text" name="sg_grade" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($workexperienceedit) ? $workexperienceedit->sg_grade : '' }}" autocomplete="off">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="badge badge-secondary text-wrap lbel">Monthly Salary</label>
                                            <input type="text" name="salary" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($workexperienceedit) ? $workexperienceedit->salary : '' }}" autocomplete="off"  oninput="autoFormatNumber(this)" required>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="badge badge-secondary text-wrap lbel">Status of Appointment</label>
                                            <input type="text" name="stat_app" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($workexperienceedit) ? $workexperienceedit->stat_app : '' }}" autocomplete="off">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="badge badge-secondary text-wrap lbel">Government Service (Y/N)</label>
                                            <select name="service" class="form-control form-control-sm" autocomplete="off" required>
                                                <option value="" {{ old('service', isset($workexperienceedit) && $workexperienceedit->service === '' ? 'selected' : '') }}>N/A</option>
                                                <option value="N" {{ old('service', isset($workexperienceedit) && $workexperienceedit->service === 'N' ? 'selected' : '') }}>No</option>
                                                <option value="Y" {{ old('service', isset($workexperienceedit) && $workexperienceedit->service === 'Y' ? 'selected' : '') }}>Yes</option>
                                            </select>
                                        </div>      
                                        
                                        <div class="col-md-3">
                                            <label class="badge badge-secondary text-wrap lbel">Attachment</label>
                                            <input type="file" name="attachment" class="form-control form-control-sm" accept="application/pdf" placeholder="N/A">
                                        </div>

                                        @php
                                            $listaccom = isset($workexperienceedit->list_accom) ? explode(';', $workexperienceedit->list_accom) : [];
                                        @endphp
                                        
                                        <div class="col-md-6">       
                                            <label class="badge badge-secondary text-wrap lbel w-100">List of Accomplishments and Contributions (if any)</label>
                                            
                                            @for ($i = 0; $i < 8; $i++)
                                                <input type="text" name="list_accom[{{ $i }}]" class="form-control form-control-sm mb-1" 
                                                    placeholder="N/A" 
                                                    value="{{ isset($listaccom[$i]) ? trim($listaccom[$i]) : '' }}" 
                                                    autocomplete="off">
                                            @endfor
                                        </div>

                                        <div class="col-md-6">       
                                            <label class="badge badge-secondary text-wrap lbel w-100">List of Accomplishments and Contributions (if any)</label>
                                            <textarea 
                                                name="actual_summary" 
                                                id="actual_summary" 
                                                class="form-control form-control-sm mb-1" 
                                                style="text-align: left; white-space: pre-wrap;" 
                                                rows="13">{{ isset($workexperienceedit) ? str_replace(['<br>', '<br/>', '<br />'], "\n", $workexperienceedit->actual_summary) : '' }}
                                            </textarea>
                                        
                                        </div>
                                        
                                        <div class="col-md-12 mt-2">
                                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm mt-1 float-right">
                                                <i class="fas fa-save"></i> {{ isset($workexperienceedit) ? 'Update' : 'Submit' }}
                                            </button>
                                        </div>
                                    </div>
                                </form>                                
                            </div>
                          </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"></h3>
                            <div class="card-tools">
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <input type="text" name="table_search" class="form-control float-right" placeholder="Search">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-default">
                                        <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>    
                            </div>
                        </div>
                        <div class="scrollable">                    
                            <table class="table table-bordered table-hover mt-2">
                                @foreach($workexperience as $work)
                                <tbody>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle" width="50%">Inclusive Dates</th>
                                        <td class="align-middle">
                                            @if($work->inc_date2 != null)
                                                {{ \Carbon\Carbon::parse($work->inc_date1)->format('m/d/Y') }} - 
                                                {{ \Carbon\Carbon::parse($work->inc_date2)->format('m/d/Y') }}
                                            @else
                                                Present
                                            @endif
                                        </td>
                                        <th class="text-center align-middle" rowspan="9" width="5%">
                                            @if($guard == "web")
                                                <a href="{{ route('workexperienceEdit', ['id' => $empid, 'eid' => $work->id]) }}" class="btn btn-info btn-sm mb-2" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button class="btn btn-success btn-sm mb-2 workexperience_approve" value="{{ $work->id }}" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                
                                                @if ($work->status == 0)
                                                    <button class="btn btn-warning btn-sm mb-2" data-toggle="modal" data-target="#workexp-modal" onclick="openworkexpModal({{ $work->id }})" title="Cancel">
                                                        <i class="fas fa-times" style="padding-left: 1px; padding-right: 1px;"></i>
                                                    </button>
                                                @endif

                                                <button class="btn btn-danger btn-sm mb-2 workexperience_delete" value="{{ $work->id }}" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @elseif($guard == "employee")
                                                @if($work->status == 0)
                                                <a href="{{ route('workexperienceEdit', ['id' => $empid, 'eid' => $work->id]) }}" class="btn btn-info btn-sm mb-2" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button class="btn btn-danger btn-sm mb-2 workexperience_delete" value="{{ $work->id }}" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                @else
                                                <a href="{{ route('workexperienceEdit', ['id' => $empid, 'eid' => $work->id]) }}" class="btn btn-info btn-sm mb-2" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                @endif
                                            @endif
                                        </th>                                                                                                                            
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle">Position Title </th> 
                                        <td class="align-middle">{{ $work->position }}</td>
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle">Department / Agency / Office / Company (Write in full/Do not abbreviate)</th>
                                        <td class="align-middle">{{ $work->department }}</td> 
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle">Salary/ Job/ Pay Grade (if applicable)& STEP (Format "00-0")/ Increment</th>
                                        <td class="align-middle">{{ $work->sg_grade }}</td>
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle">Monthly Salary</th>
                                        <td class="align-middle">{{ $work->salary }}</td>
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle">Status of Appointment</th>
                                        <td class="align-middle">{{ $work->stat_app }}</td>
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle">Government Service (Y/N)</th>
                                        <td class="align-middle">{{ ($work->service == "Y") ? 'Yes' : 'No' }}</td>
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle">Attachment</th>
                                        <td class="align-middle">
                                            @if(!empty($work->attachment))
                                                <a href="#" class="text-info" data-toggle="modal" data-target="#pdfModal" 
                                                   data-label="{{ $work->careereligible }}" 
                                                   data-pdf="{{ asset('storage/' . $work->attachment) }}" onclick="showPdfModal(this)">
                                                    <i class="fas fa-eye fa-xs"></i> <b>Preview</b>
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>                                        
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <th class="align-middle">Status</th>
                                        <td class="align-middle">
                                            @if ($work->status == 0)
                                                <span class="badge badge-warning" id="status-{{ $work->id }}">To be Reviewed</span>
                                            @elseif($work->status == 1)
                                                <span class="badge badge-success">Reviewed</span>
                                            @else
                                                <span class="badge badge-danger">Canceled</span>
                                                <div class="mt-1 text-muted">
                                                    <strong>Remarks:</strong> {{ $work->remarks }}
                                                </div>
                                            @endif
                                        </td>                                
                                    </tr>
                                    <tr class="workexperience-row row-{{ $work->id }}">
                                        <td colspan="3"></td>
                                    </tr>
                                </tbody>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
<div class="modal fade" id="workexp-modal" tabindex="-1" aria-labelledby="eligibleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('workexperienceCancel') }}">
                @csrf
                <div class="modal-header">
      
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="workexp-id">
                    <div class="form-group">
                        <span class="badge badge-secondary">Remarks</span>
                      <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter remarks" required></textarea>
                    </div>
                    <div style="float: right;">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                    </div>
                </div>
                <div class="modal-footer mt-3">
             
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="pdfModal" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg custom-modal" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closePdfModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <iframe id="modalPdf" src="" width="100%" height="600px" style="border: none;"></iframe>
            </div>
        </div>
    </div>
</div>
<script>
    function showPdfModal(link) {
        var label = link.getAttribute('data-label');
        var pdfUrl = link.getAttribute('data-pdf');
        
        document.getElementById('pdfModalLabel').innerText = label;
        document.getElementById('modalPdf').src = pdfUrl;
        
        var modal = new bootstrap.Modal(document.getElementById('pdfModal'));
        modal.show();
    }

    function closePdfModal() {
        document.getElementById('modalPdf').src = '';
    }

    function openworkexpModal(id) {
        document.getElementById('workexp-id').value = id;

        document.getElementById('remarks').value = '';

        document.getElementById('remarks').focus();
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleIcon = document.querySelector('.toggle-icon');

        toggleIcon.addEventListener('click', () => {
            if (toggleIcon.classList.contains('fa-arrow-left')) {
                toggleIcon.classList.remove('fa-arrow-left');
                toggleIcon.classList.add('fa-arrow-down');
            } else {
                toggleIcon.classList.remove('fa-arrow-down');
                toggleIcon.classList.add('fa-arrow-left');
            }
        });
    });
</script>
<script>
    function autoFormatNumber(input) {
      let value = input.value.replace(/,/g, '').replace(/\D/g, '');
      input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
</script>  
@endsection