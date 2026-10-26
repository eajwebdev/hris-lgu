@extends('layouts.master')

@section('hero')
    <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Account Management</h1>
    <p class="mt-1 max-w-2xl text-white/85">Review employee usernames and manage access credentials.</p>
@endsection

@section('body')
@php
    $current_route=request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card legacy-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-address-card text-success mr-2"></i>Employee Accounts</h2>
                </div>
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
