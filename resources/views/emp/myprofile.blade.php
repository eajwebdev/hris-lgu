@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-success1">
                        <b>Personal Detail</b>
                    </h3>
                    <div class="card-header">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <div class="card-header">
                        <div class="card-tools">
                            <a href="{{ route('empAdd') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-user-plus"></i> Add Employee
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#modal-employee">
                                <i class="fas fa-user-plus"></i> Add Employee
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection