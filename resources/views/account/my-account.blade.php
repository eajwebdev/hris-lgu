@extends('layouts.master')

@section('body')
<style>
    .mtop {
        margin-top: -15px;
    }
    .bg-form{
        background-color:  #e9ecef;
    }
    .form-control:disabled, .form-control[readonly] {
        background-color: #ffffff;
        opacity: 1;
    }
</style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>My Account</b>
                    </h2>
                </div>
                <div class="card-body bg-form">
                    <form class="form-horizontal add-form" action="{{ route('updateAccount') }}" method="POST">
                        @csrf
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label class="badge badge-secondary lbel">Gmail</label><br>
                                    <input type="email" name="username" class="form-control form-control-sm" value="{{ auth()->guard($guard)->user()->username }}" autocomplete="off" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="badge badge-secondary lbel">Current Password</label><br>
                                    <input type="password" name="current_password" class="form-control form-control-sm" placeholder="N/A" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="badge badge-secondary lbel">New Password</label><br>
                                    <input type="password" name="new_password" class="form-control form-control-sm" placeholder="N/A" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="badge badge-secondary lbel">Repeat New Password</label><br>
                                    <input type="password" name="new_password_confirmation" id="repeat-pass" class="form-control form-control-sm" placeholder="N/A" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button type="submit" name="btn-submit btn-sm" class="btn btn-success">
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

@endsection