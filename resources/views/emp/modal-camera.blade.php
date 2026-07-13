<div class="modal fade" id="camera-modal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content"> 
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="form-row mt-2">
                                <div class="col-lg-12">
                                    <div id="camera" style="width: 100%; height: 210px; margin-bottom: 20px !important;">
                                        <label class="badge badge-secondary">Profile</label><br>
                                        <div id="capture-image"></div>
                                        <video id="webcam-preview" style="width: 100%; height: 100%;"></video><br>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-5 col-lg-5">
                                    <button type="button" id="capture-button" class="btn btn-primary btn-block btn-sm"><i class="fas fa-camera"></i> Capture</button>
                                </div>
                                <div class="col-5 col-lg-5">
                                    <button type="button" id="capture-again-button" class="btn btn-secondary btn-block btn-sm"><i class="fas fa-redo"></i> Recapture</button>
                                </div>
                                <div class="col-2 col-lg-2">
                                    <button type="button" id="capture-button" class="btn btn-danger btn-block btn-sm " data-dismiss="modal"><i class="fas fa-close"></i> </button>
                                </div>
                            </div>
                        </div>
                    </div>            
                </div>
            </div>
        </div>
    </div>
</div>