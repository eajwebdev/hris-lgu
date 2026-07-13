@extends('layouts.master')

@section('body')
@php
    $current_route=request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row" style="padding-top: 100px;">
        <div class="col-lg-12">
            <div class="card card-success card-outline">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                        <li class="breadcrumb-item">Account</li>
                    </ol> 
                </nav>
                <div class="card-body">
                    <div class="row">
                        @if($guard == 'web')
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="example1">
                                    <thead>
                                        <tr>
                                            <th>NO.</th>
                                            <th>Full Name</th>
                                            <th>Username</th> 
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $no = 1; @endphp
                                        @foreach ($employees as $employee)
                                            <tr>
                                                <td class="text-center" width="30">{{ $no++ }}</td>
                                                <td>{{ strtoupper($employee->fname) }} {{ strtoupper($employee->lname) }}</td>
                                                <td>{{ $employee->username }}</td>
                                                <td width="50">
                                                    <div class='d-flex align-items-center'>
                                                        <button class='btn btn-info btn-xs employee_edit mr-1'  value="{{ $employee->id }}">
                                                            <i class='fas fa-exclamation-circle'></i>
                                                        </button>
                                                    </div> 
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->
@include('account.modals')
<!-- /End Modal -->
@endsection