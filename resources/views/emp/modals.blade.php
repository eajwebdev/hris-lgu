<style>
    .select2-container .select2-selection--single {
        overflow: hidden;
        border: 1px solid #ced4da;
    }
    </style>
    <div class="modal fade" id="modal-employee">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        <i class="fas fa-user-plus"></i> Add New Employee
                    </h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form class="form-horizontal add-form" action="{{ route('empCreate') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-4">
                                    <label for="exampleInputName">Last Name:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" name="LastName" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Last Name" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left LastName_error"></span>
                                </div>
    
                                <div class="col-md-4">
                                    <label for="exampleInputName">First Name:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" name="FirstName" oninput="this.value = this.value.toUpperCase()" placeholder="Enter First Name" class="form-control" required>
                                    </div>    
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left FirstName_error"></span>
                                </div>
    
                                <div class="col-md-4">
                                    <label for="exampleInputName">Middle Name:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" name="MiddleName" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Middle Name" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left MiddleName_error"></span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="exampleInputName">Department/Office:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-building"></i>
                                            </span>
                                        </div>
                                        <select class="form-control select2" style="width: 90%;" name="Office">
                                            <option value=""> --- Select Here --- </option>
                                            @foreach ($offices as $q)
                                                <option value="{{ $q->id }}">{{ $q->office_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Office_error"></span>
                                </div>
                            </div>
                        </div>
    
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="exampleInputName">Position:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </div>
                                        <input type="text" name="Position" oninput="this.value = this.value.restOfStr()" placeholder="Enter Position" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Position_error"></span>
                                </div>
    
                                <div class="col-md-6">
                                    <label for="exampleInputName">Employee Biometric ID:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="far fa-id-card"></i>
                                            </span>
                                        </div>
                                        <input type="text" name="emp_ID" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Employee Company ID" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left emp_ID_error"></span>
                                </div>
                            </div>
                        </div>
    
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="exampleInputName">SG-Step</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="sg_step" name="sg_step" oninput="this.value = this.value.restOfStr()" placeholder="SG-Step" class="form-control">
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Position_error1"></span>
                                </div>
                                <div class="col-md-6">
                                    <label for="exampleInputName">Employee Status:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-chart-line"></i>
                                            </span>
                                        </div>
                                        <select class="form-control select2bs4" onchange="stat(this.value)" style="width: 88%;" name="Status" required>
                                            <option value=""> --- Select Here --- </option>
                                            @foreach ($stat as $st)
                                                <option value="{{ $st->id }}">{{ $st->status_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Status_error"></span>
                                </div>
    
                                <div class="col-md-6" id="qualification">
                                    <label for="exampleInputName">Qualification</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-chart-line"></i>
                                            </span>
                                        </div>
                                        <select class="form-control select2bs4" name="Qualification" onchange="quali(this.value)">
                                            <option value=""> --- Select Here --- </option>
                                            @foreach ($quali as $ql)
                                            <option value="{{ $ql->id }}">{{ $ql->qualification }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Qualification_error"></span>
                                </div>
    
                                <div class="col-md-6" id="srate">
                                    <label for="exampleInputName" id="rate-title">Monthly Rate:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-coins"></i>
                                            </span>
                                        </div>
                                        <input type="number" step="any" min="0" id="insrate" name="SalaryRate" placeholder="Monthly Rate" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left SalaryRate_error"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" name="btn-submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
                
                <div class="modal-footer justify-content-between">
                    <!-- <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>
    
    
    
    
    <div class="modal fade" id="modal-employee-edit">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        <i class="fas fa-user-plus"></i> Edit Employee
                    </h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form class="form-horizontal edit-form" action="{{ route('empUpdate') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-4">
                                    <label for="exampleInputName">Last Name:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="hidden" id="edit-id" name="id">
                                        <input type="text" id="lname" name="LastName" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Last Name" class="form-control fname" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left LastName_error1"></span>
                                </div>
    
                                <div class="col-md-4">
                                    <label for="exampleInputName">First Name:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="fname" name="FirstName" oninput="this.value = this.value.toUpperCase()" placeholder="Enter First Name" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left FirstName_error1"></span>
                                </div>
    
                                <div class="col-md-4">
                                    <label for="exampleInputName">Middle Name:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="mname" name="MiddleName" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Middle Name" class="form-control">
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left MiddleName_error1"></span>
                                </div>
                            </div>
                        </div>
    
                        <hr>
    
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="exampleInputName">Department/Office:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-building"></i>
                                            </span>
                                        </div>
                                        <select class="form-control select_office" name="Office" required>
                                            <option value=""> --- Select Here --- </option>
                                            @foreach ($offices as $q)
                                                <option value="{{ $q->id }}">{{ $q->office_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Office_error1"></span>
                                </div>
                            </div>
                        </div>
    
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="exampleInputName">Position</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="position" name="Position" oninput="this.value = this.value.restOfStr()" placeholder="Enter Middle Name" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Position_error1"></span>
                                </div>
    
                                <div class="col-md-6">
                                    <label for="exampleInputName">Employee Biometric ID:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-id-card"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="empid" name="emp_ID" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Employee Company ID" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left emp_ID_error1"></span>
                                </div>
                            </div>
                        </div>
    
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="exampleInputName">SG-Step</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="sg_step1" name="sg_step" oninput="this.value = this.value.restOfStr()" placeholder="SG-Step" class="form-control">
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Position_error1"></span>
                                </div>
                                <div class="col-md-6">
                                    <label for="exampleInputName">Employee Status:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-chart-line"></i>
                                            </span>
                                        </div>
                                        <select class="form-control select2bs4 select_stat" name="Status" onchange="stat1(this.value)" required>
                                            <option value=""> --- Select Here --- </option>
                                            @foreach ($stat as $st)
                                                <option value="{{ $st->id }}">{{ $st->status_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Status_error1"></span>
                                </div>
    
                                <div class="col-md-6" id="qualification1">
                                    <label for="exampleInputName">Qualification</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-chart-line"></i>
                                            </span>
                                        </div>
                                        <select class="form-control select2bs4 select_qual" name="Qualification" onchange="quali1(this.value)">
                                            <option value=""> --- Select Here --- </option>
                                            @foreach ($quali as $ql)
                                            <option value="{{ $ql->id }}" >{{ $ql->qualification }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Qualification_error"></span>
                                </div>
    
                                <div class="col-md-6" id="srate1">
                                    <label for="exampleInputName" id="rate-title1">Salary Rate:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-coins"></i>
                                            </span>
                                        </div>
                                        <input type="number" step="any" min="0" id="insrate1" name="SalaryRate" placeholder="Enter Monthly Salary" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left SalaryRate_error1"></span>
                                </div>
                            </div>
                        </div>
    
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" name="btn-submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
                
                <div class="modal-footer justify-content-between">
                    <!-- <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="modal fade" id="partime-form">
        <div class="modal-dialog modal-m">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        <i class="fas fa-user-plus"></i> Partime 
                    </h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form class="form-horizontal partime-form" method="POST">
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label for="exampleInputName">Partime Rate  / Hour:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-coins"></i>
                                            </span>
                                        </div>
                                        <input type="hidden" class="form-control" id="partime-empid" name="empid">
                                        <input type="number" step="any" min="0" id="PartimeRate" name="PartimeRate" placeholder="Enter Rate / Hour" class="form-control" required>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left PartimeRate_error"></span>
                                </div>
                            </div>
                        </div>
    
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" name="btn-submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
                
                <div class="modal-footer justify-content-between">
                    <!-- <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>