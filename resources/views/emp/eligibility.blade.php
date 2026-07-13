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
                        <b>ELIGIBILITY</b>
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
                          </div>
                          <div id="collapseOne" class="collapse show" data-parent="#accordion" style="">
                            <div class="card-body bg-form">
                                <form class="form-horizontal" action="{{ isset($eligibilityedit) ? route('eligibilityUpdate', $eligibilityedit->id) : route('eligibilityCreate') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @if(isset($eligibilityedit))
                                        <input type="hidden" name="id" value="{{ $eligibilityedit->id }}">
                                    @endif
                                    <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">
                                
                                    <div class="form-row lbel mtop">
                                        <div class="col-md-6">
                                            <label class="badge badge-secondary text-wrap text-center lbel">CAREER SERVICE/ RA 1080 (BOARD/ BAR) UNDER SPECIAL LAWS/ CES/ CSEE BARANGAY ELI.</label>
                                            <input type="text" name="careereligible" class="form-control form-control-sm" placeholder="N/A" 
                                                value="{{ old('careereligible', isset($eligibilityedit) ? $eligibilityedit->careereligible : '') }}" autocomplete="off">
                                        </div>
                                
                                        <div class="col-md-3">
                                            <label class="badge badge-secondary text-wrap lbel">RATING (If Applicable)</label>
                                            <input type="number" name="rating" step="0.01" min="0" class="form-control form-control-sm" placeholder="N/A" 
                                                value="{{ old('rating', isset($eligibilityedit) ? $eligibilityedit->rating : '') }}" autocomplete="off">
                                        </div>
                                
                                        <div class="col-md-3">
                                            <label class="badge badge-secondary text-wrap lbel">Date of Examination / Conferment</label>
                                            <input type="date" name="date_exam" class="form-control form-control-sm" placeholder="N/A" 
                                                value="{{ old('date_exam', isset($eligibilityedit) ? $eligibilityedit->date_exam : '') }}" autocomplete="off">
                                        </div>
                                
                                        <div class="col-md-6">
                                            <label class="badge badge-secondary text-wrap lbel">Place of Examination / Conferment</label>
                                            <input type="text" name="place_exam" class="form-control form-control-sm" placeholder="N/A" 
                                                value="{{ old('place_exam', isset($eligibilityedit) ? $eligibilityedit->place_exam : '') }}" autocomplete="off">
                                        </div>
                                
                                        <div class="col-md-2">
                                            <label class="badge badge-secondary text-wrap lbel">Number</label>
                                            <input type="number" name="number" class="form-control form-control-sm" placeholder="N/A" 
                                                value="{{ old('number', isset($eligibilityedit) ? $eligibilityedit->number : '') }}" autocomplete="off">
                                        </div>
                                
                                        <div class="col-md-2">
                                            <label class="badge badge-secondary text-wrap lbel">Date of Validity</label>
                                            <input type="date" name="date_valid" class="form-control form-control-sm" placeholder="N/A" 
                                                value="{{ old('date_valid', isset($eligibilityedit) ? $eligibilityedit->date_valid : '') }}" oninput="validateDateRange(this)" autocomplete="off">
                                        </div>
                                
                                        <div class="col-md-2">
                                            <label class="badge badge-secondary text-wrap lbel">Attachment</label>
                                            <input type="file" name="attachment" class="form-control form-control-sm" accept="application/pdf" placeholder="N/A">
                                        </div>
                                
                                        <div class="col-md-12 mt-2">
                                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm mt-1 float-right">
                                                <i class="fas fa-save"></i> {{ isset($eligibilityedit) ? 'Update' : 'Submit' }}
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
                                @foreach($eligibility as $eli)
                                <tbody>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
                                        <th class="align-middle" width="50%">CAREER SERVICE/ RA 1080 (BOARD/ BAR) UNDER SPECIAL LAWS/ CES/ CSEE BARANGAY ELIGIBILITY / DRIVER'S LICENSE</th>
                                        <td class="align-middle">{{ $eli->careereligible }}</td>
                                        <th class="text-center align-middle" rowspan="9" width="5%">
                                            @if($guard == "web")
                                                <a href="{{ route('eligibilityEdit', ['id' => $empid, 'eid' => $eli->id]) }}" class="btn btn-info btn-sm mb-2" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button class="btn btn-success btn-sm mb-2 eligible_approve" value="{{ $eli->id }}" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm mb-2 eligible_delete" value="{{ $eli->id }}" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                @if ($eli->status == 0)
                                                <button class="btn btn-warning btn-sm mb-2" data-toggle="modal" data-target="#eligible-modal" onclick="openEligibilityModal({{ $eli->id }})" title="Cancel">
                                                    <i class="fas fa-times" style="padding-left: 1px; padding-right: 1px;"></i>
                                                </button>
                                                @endif
                                            @elseif($guard == "employee" && $eli->status !== 1)
                                                <a href="{{ route('eligibilityEdit', ['id' => $empid, 'eid' => $eli->id]) }}" class="btn btn-info btn-sm mb-2" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button class="btn btn-danger btn-sm mb-2 eligible_delete" value="{{ $eli->id }}" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </th>                                                                                                                            
                                    </tr>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
                                        <th class="align-middle">RATING (If Applicable)</th> 
                                        <td class="align-middle">{{ ($eli->rating != NULL) ? $eli->rating : 'N/A' }}</td>
                                    </tr>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
                                        <th class="align-middle">Date of Examiniation / Conferment</th>
                                        <td class="align-middle">{{ $eli->date_exam }}</td> 
                                    </tr>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
                                        <th class="align-middle">Place of Examinination / Conferment</th>
                                        <td class="align-middle">{{ $eli->place_exam }}</td>
                                    </tr>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
                                        <th class="align-middle">Number</th>
                                        <td class="align-middle">{{ $eli->number }}</td>
                                    </tr>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
                                        <th class="align-middle">Date of Validity</th>
                                        <td class="align-middle">{{ ($eli->date_valid == NULL) ? 'N/A' : $eli->date_valid }}</td>
                                    </tr>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
                                        <th class="align-middle">Attachment</th>
                                        <td class="align-middle">
                                            <a href="#" class="text-info" data-toggle="modal" data-target="#pdfModal" 
                                            data-label="{{ $eli->careereligible }}" 
                                            data-pdf="{{ asset('storage/' . $eli->attachment) }}" onclick="showPdfModal(this)">
                                                <i class="fas fa-eye fa-xs"></i> <b>Preview</b>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
                                        <th class="align-middle">Status</th>
                                        <td class="align-middle">
                                            @if ($eli->status == 0)
                                                <span class="badge badge-warning" id="status-{{ $eli->id }}">To be Reviewed</span>
                                            @elseif($eli->status == 1)
                                                <span class="badge badge-success">Reviewed</span>
                                            @else
                                                <span class="badge badge-danger">Canceled</span>
                                                <div class="mt-1 text-muted">
                                                    <strong>Remarks:</strong> {{ $eli->remarks }}
                                                </div>
                                            @endif
                                        </td>                                
                                    </tr>
                                    <tr class="eligibility-row row-{{ $eli->id }}">
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

<div class="modal fade" id="eligible-modal" tabindex="-1" aria-labelledby="eligibleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('eliCancel') }}">
                @csrf
                <div class="modal-header">
      
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="eli-id">
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

    function openEligibilityModal(id) {
        document.getElementById('eli-id').value = id;

        document.getElementById('remarks').value = '';

        document.getElementById('remarks').focus();
    }
</script>
<script>
    
</script>
@endsection