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
                            <b>VOLUNTARY WORKS</b>
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
                                    <form class="form-horizontal" action="{{ isset($voluntaryworksedit) ? route('voluntaryworksUpdate', $voluntaryworksedit->id) : route('voluntaryworksCreate') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @if(isset($voluntaryworksedit))
                                            <input type="hidden" name="id" value="{{ $voluntaryworksedit->id }}">
                                        @endif
                                        <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">
                                        
                                        <div class="form-row lbel mtop">
                                            <div class="col-md-6">
                                                <label class="badge badge-secondary text-wrap text-center lbel">Name & Address of Organization (Write in Full)</label>
                                                <input type="text" name="org_name" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->org_name : '' }}" autocomplete="off" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="badge badge-secondary text-wrap text-center lbel">Inclusive Dates</label>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <input type="date" id="inc_date1" name="inc_date1" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->inc_date1 : '' }}" autocomplete="off" style="flex: 1; margin-right: 5px;" required>
                                                    <input type="date" id="inc_date2" name="inc_date2" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->inc_date2 : '' }}" autocomplete="off" style="flex: 1; margin-left: 5px;" required>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="badge badge-secondary text-wrap lbel">Number of Hours</label>
                                                <input type="number" name="num_hours" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->num_hours : '' }}" autocomplete="off" required>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label class="badge badge-secondary text-wrap lbel">Position / Nature of Work</label>
                                                <input type="text" name="position" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->position : '' }}" autocomplete="off" required>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="badge badge-secondary text-wrap lbel">Attachment</label>
                                                <input type="file" name="attachment" class="form-control form-control-sm" accept="application/pdf" placeholder="N/A">
                                            </div>
                                    
                                            <div class="col-md-12 mt-2">
                                                <button type="submit" name="btn-submit" class="btn btn-success btn-sm mt-1 float-right">
                                                    <i class="fas fa-save"></i> {{ isset($voluntaryworksedit) ? 'Update' : 'Submit' }}
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
                                    @foreach($voluntaryworks as $vwork)
                                    <tbody>
                                        <tr class="voluntaryworks-row row-{{ $vwork->id }}">
                                            <th class="align-middle">NAME & ADDRESS OF ORGANIZATION</th> 
                                            <td class="align-middle">{{ $vwork->org_name }}</td>
                                            <th class="text-center align-middle" rowspan="9" width="5%">
                                                @if($guard == "web")
                                                    <a href="{{ route('voluntaryworksEdit', ['id' => $empid, 'eid' => $vwork->id]) }}" class="btn btn-info btn-sm mb-2" title="Edit">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                    <button class="btn btn-success btn-sm voluntaryworks_approve mb-2" value="{{ $vwork->id }}" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>

                                                    @if ($vwork->status == 0)
                                                        <button class="btn btn-warning btn-sm mb-2" data-toggle="modal" data-target="#vwork-modal" onclick="openvworkModal({{ $vwork->id }})" title="Cancel">
                                                            <i class="fas fa-times" style="padding-left: 1px; padding-right: 1px;"></i>
                                                        </button>
                                                    @endif
                                                    
                                                    <button class="btn btn-danger btn-sm mb-2 voluntaryworks_delete" value="{{ $vwork->id }}" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @elseif($guard == "employee" && $vwork->status == 0)
                                                    <a href="{{ route('voluntaryworksEdit', ['id' => $empid, 'eid' => $vwork->id]) }}" class="btn btn-info btn-sm mb-2" title="Edit">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                    <button class="btn btn-danger btn-sm mb-2 voluntaryworks_delete" value="{{ $vwork->id }}" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </th> 
                                        </tr>
                                        <tr class="voluntaryworks-row row-{{ $vwork->id }}">
                                            <th class="align-middle" width="50%">INCLUSIVE DATES</th>
                                            <td class="align-middle">
                                                {{ \Carbon\Carbon::parse($vwork->inc_date1)->format('m/d/Y') }} - 
                                                {{ \Carbon\Carbon::parse($vwork->inc_date2)->format('m/d/Y') }}
                                            </td>                                                                                                                
                                        </tr>
                                        <tr class="voluntaryworks-row row-{{ $vwork->id }}">
                                            <th class="align-middle">NUMBER OF HOURS</th>
                                            <td class="align-middle">{{ number_format($vwork->num_hours).' HOURS' }}</td>
                                        </tr>
                                        <tr class="voluntaryworks-row row-{{ $vwork->id }}">
                                            <th class="align-middle">POSITION / NATURE OF WORK</th>
                                            <td class="align-middle">{{ $vwork->position }}</td>
                                        </tr>
                                        <tr class="workexperience-row row-{{ $vwork->id }}">
                                            <th class="align-middle">Attachment</th>
                                            <td class="align-middle">
                                                @if(!empty($vwork->attachment))
                                                <a href="#" class="text-info" data-toggle="modal" data-target="#pdfModal" 
                                                data-label="{{ $vwork->careereligible }}" 
                                                data-pdf="{{ asset('storage/' . $vwork->attachment) }}" onclick="showPdfModal(this)">
                                                    <i class="fas fa-eye fa-xs"></i> <b>Preview</b>
                                                </a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="workexperience-row row-{{ $vwork->id }}">
                                            <th class="align-middle">Status</th>
                                            <td class="align-middle">
                                                @if ($vwork->status == 0)
                                                    <span class="badge badge-warning" id="status-{{ $vwork->id }}">To be Reviewed</span>
                                                @elseif($vwork->status == 1)
                                                    <span class="badge badge-success">Reviewed</span>
                                                @else
                                                    <span class="badge badge-danger">Canceled</span>
                                                    <div class="mt-1 text-muted">
                                                        <strong>Remarks:</strong> {{ $vwork->remarks }}
                                                    </div>
                                                @endif
                                            </td>                                
                                        </tr>
                                        <tr class="workexperience-row row-{{ $vwork->id }}">
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
<div class="modal fade" id="vwork-modal" tabindex="-1" aria-labelledby="eligibleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('voluntaryworksCancel') }}">
                @csrf
                <div class="modal-header">
      
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="vwork-id">
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

    function openvworkModal(id) {
        document.getElementById('vwork-id').value = id;

        document.getElementById('remarks').value = '';

        document.getElementById('remarks').focus();
    }
</script>
@endsection