<div class="modal fade" id="leaveModal" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">LEAVE CREDITS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavesCreate') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Date</label>
                                <input class="form-control form-control-sm" type="month" id="date" name="date" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Days</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="number" id="days" name="days" min="1" max="30" oninput="updateEquivalent()" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="text" id="sl" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" id="vl" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-4 mb-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Remarks</label>
                                <textarea class="form-control form-control-sm" type="text" name="remarks" step="0.001" rows="2"></textarea>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalSettingLeave" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavescreditDeduct') }}" method="POST">
                    @csrf
                    <div class="row">
               
                        <div class="col-md-9 mt-1">
                            <strong>Special Privilege Leave</strong>
                        </div>
            
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="special_pl" value="{{ $employee->special_pl }}" data-column-id="{{ $empid ?? null }}" data-column-name="special_pl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>

                        <div class="col-md-9 mt-1">
                            <strong>Solo Parent Leave</strong>
                        </div>
            
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="solo_pl" value="{{ $employee->solo_pl }}" data-column-id="{{ $empid ?? null }}" data-column-name="solo_pl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>

                        <div class="col-md-9 mt-1">
                            <strong>Study Leave</strong>
                        </div>
            
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="study_leave" value="{{ $employee->study_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="study_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>10-Day VAWC Leave</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="vawc_leave" value="{{ $employee->vawc_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="vawc_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Rehabilitation Privilege</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="rehab_leave" value="{{ $employee->rehab_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="rehab_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Special Leave Benefits for Women</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="benefits_leave" value="{{ $employee->benefits_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="benefits_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Special Emergency (Calamity) Leave</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="calamity_leave" value="{{ $employee->calamity_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="calamity_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Adoption Leave</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="adopt_leave" value="{{ $employee->adopt_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="adopt_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Vacation Service Credit</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="servcred_leave" value="{{ $employee->servcred_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="servcred_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Wellness Leave</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="well_leave" value="{{ $employee->well_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="well_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="leaveModalDeduct" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">LEAVE CREDITS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavescreditDeduct') }}" method="POST">
                    @csrf
                    <div class="row">
               
                        <div class="col-md-6">
                            <div class="form-check">
                                <label class="badge badge-secondary">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="hidden" id="date" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m') }}" required>
                                <input class="form-control form-control-sm" type="text" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required>
                            </div>
                        </div>
            
                        <div class="col-md-6">
                            <div class="form-check">
                                <label class="badge badge-secondary">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-4 mb-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Remarks</label>
                                <textarea class="form-control form-control-sm" type="text" name="remarks" step="0.001" rows="2"></textarea>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveEditModal" tabindex="-1" role="dialog" aria-labelledby="leaveEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveEditModalLabel">LEAVE CREDITS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavesUpdate') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Date</label>
                                <input class="form-control form-control-sm" type="month" id="date1" name="date" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Days</label>
                                <input type="hidden" id="lcid" name="lcid">
                                <input class="form-control form-control-sm" type="number" id="days1" name="days" min="1" max="30" oninput="updateEquivalent1()" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="number" id="sl1" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" id="vl1" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-4 mb-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Remarks</label>
                                <textarea class="form-control form-control-sm" type="text" id="remarks1" name="remarks" step="0.001" rows="2"></textarea>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveModalDeductEdit" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">LEAVE CREDITS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavescreditDeductUpdate') }}" method="POST">
                    @csrf
                    <div class="row">
               
                        <div class="col-md-6">
                            <div class="form-check">
                                <label class="badge badge-secondary">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input type="hidden" id="lcid-ded" name="lcid">
                                <input class="form-control form-control-sm" type="hidden" id="date-ded" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m') }}" required>
                                <input class="form-control form-control-sm" type="number" id="sl1-ded" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required>
                            </div>
                        </div>
            
                        <div class="col-md-6">
                            <div class="form-check">
                                <label class="badge badge-secondary">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" id="vl1-ded" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-4 mb-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Remarks</label>
                                <textarea class="form-control form-control-sm" type="text" id="remarks1-ded" name="remarks" step="0.001" rows="2"></textarea>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>