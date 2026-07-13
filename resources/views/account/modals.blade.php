@if(Route::currentRouteName() === 'drive-account')
<div class="modal fade" id="newAccountModal" tabindex="-1" role="dialog" aria-labelledby="createAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAccountModalLabel">New Account</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{  route('uCreate') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-md-12">
                                <label for="exampleInputName">Employee:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-users"></i>
                                        </span>
                                    </div>
                                    <select class="form-control select2" style="width: 90%;">
                                        <option value=""> --- Select Here --- </option>
                                        @foreach ($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->lname }} {{ $emp->fname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left EmployeeName_error"></span>
                           </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-md-12">
                                <label for="exampleInputName">Username:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                    </div>
                                    <input type="text" name="Username" placeholder="Enter Username" class="form-control" autocomplete="off">
                                </div>    
                                <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Username_error"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-md-12">
                                <label for="exampleInputName">Password:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    </div>
                                    <input type="password" name="Password" value="" placeholder="Enter Password" class="form-control" autocomplete="off">
                                </div>    
                                <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Password_error"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-md-12" style="float: right;">
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
@endif