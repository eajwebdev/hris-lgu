@extends('layouts.master')

@section('body')
@php
    $current_route=request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus"></i> {{ $current_route == "officeList" ? "Add" : "Edit" }}
                    </h3>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ $current_route == "officeList" ? route('officeCreate') : route('officeUpdate') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label for="exampleInputName">Office Name:</label>
                                    <input type="hidden" class="form-control form-control-sm" name="oid" value="{{ $current_route == 'officeEdit' ? $offEdit->id : '' }}">
                                    <input type="text" class="form-control form-control-sm" name="OfficeName" value="{{ $current_route == 'officeEdit' ? $offEdit->office_name : '' }}" oninput="var words = this.value.split(' '); for(var i = 0; i < words.length; i++){ words[i] = words[i].substr(0,1).toUpperCase() + words[i].substr(1); } this.value = words.join(' ');" class="form-control"  autocomplete="off">  
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label for="exampleInputName">Office Abbreviation:</label>
                                    <input type="text" class="form-control form-control-sm" name="OfficeAbbreviation" value="{{ $current_route == 'officeEdit' ? $offEdit->office_abbr : '' }}" oninput="var words = this.value.split(' '); for(var i = 0; i < words.length; i++){ words[i] = words[i].substr(0,1).toUpperCase() + words[i].substr(1); } this.value = words.join(' ');" class="form-control"  autocomplete="off" >  
                                </div>
                            </div>
                        </div>

                        {{-- <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label for="exampleInputName">Group By:</label>
                                    <select class="form-control select2" name="GroupBy" style="width: 100%;" required>
                                        <option value="0"> None </option>
                                        @foreach($office as $off)
                                            @if($off->office_name !== 'UNKNOWN')
                                                <option value="{{ $off->id }}" {{ $current_route ==  'officeEdit' ? $off->id == $offEdit->group_by ? 'selected' : '' : ''}}>{{ $off->office_name }}</option>
                                            @endif
                                        @endforeach
                                    </select>   
                                </div>
                            </div>
                        </div>  --}}

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label for="exampleInputName">Office Head</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="far fa-user"></i>
                                            </span>
                                        </div>
                                        <select class="form-control select2" name="office_head_id">
                                            <option value=""> --- Select Employee --- </option>
                                            @foreach($employee as $emp)
                                                <option value="{{ $emp->id }}" @if($current_route == 'officeEdit') @if($emp->id == $offEdit->office_head_id) selected @endif @endif>{{ $emp->emp_ID }} - {{ $emp->lname }} {{ $emp->fname }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div> 

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label for="exampleInputName">OIC</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="far fa-user"></i>
                                            </span>
                                        </div>
                                        <select class="form-control select2" name="oic_id">
                                            <option value=""> --- Select Employee --- </option>
                                            @foreach($employee as $emp)
                                                <option value="{{ $emp->id }}" @if($current_route == 'officeEdit') @if($emp->id == $offEdit->oic_id) selected @endif @endif>{{ $emp->emp_ID }} - {{ $emp->lname }} {{ $emp->fname }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div> 

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                @if($current_route == "officeEdit")
                <div class="card-header">
                    <div class="col-md-12">
                        <ol class="breadcrumb float-md-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('officeList') }}">Office</a></li>
                            <li class="breadcrumb-item">Edit</li>
                        </ol>                            
                    </div>
                </div>
                @endif
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Office</th>
                                    <th>Abbreviation</th>
                                    <th>Office Head</th>
                                    <th>OIC</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                @php $no = 1; @endphp
                                @foreach($office as $office)
                                    @php
                                        $groupOffice = $office->group_by ? \App\Models\Office::find($office->group_by) : null;
                                    @endphp
                                    <tr id="tr-{{ $office->id}}">
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $office->office_name }}</td>
                                        <td>{{ $office->office_abbr }}</td>
                                        <td>{{ $office->efname }} {{ $office->elname }}</td>
                                        <td>{{ $office->ofname }} {{ $office->olname }}</td>
                                        <td>
                                            <a href="{{ route('officeEdit', $office->id) }}" class="btn btn-info btn-xs">
                                                <i class="fas fa-exclamation-circle"></i>
                                            </a>
                                            <button value="{{ $office->id }}" class="btn btn-danger btn-xs office-delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach


                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection