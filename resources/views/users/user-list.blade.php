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
                        <i class="fas fa-plus"></i> {{ $current_route == "ulist" ? "Add" : "Edit" }}
                    </h3>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ $current_route == "ulist" ? route('uCreate') : route('uUpdate') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="hidden" name="uid" value="{{ $current_route == 'uEdit' ? $uEdit->id : '' }}">
                                        <input type="text" name="lname" value="{{ $current_route == 'uEdit' ? $uEdit->lname : '' }}" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Last Name" class="form-control form-control-sm" autocomplete="off" required="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" name="fname" value="{{ $current_route == 'uEdit' ? $uEdit->fname : '' }}" placeholder="Enter First Name" class="form-control form-control-sm" autocomplete="off" required="">
                                    </div>    
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" name="mname" value="{{ $current_route == 'uEdit' ? $uEdit->mname : '' }}" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Middle Name" class="form-control form-control-sm" autocomplete="off" required="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-venus-mars"></i>
                                            </span>
                                        </div>
                                        <select name="gender" class="form-control form-control-sm" autocomplete="off" required="">
                                            <option value="">--- Select Gender ---</option>
                                            <option value="Male" @if($current_route == 'uEdit' && $uEdit->gender == 'Male') selected @endif>Male</option>
                                            <option value="Female" @if($current_route == 'uEdit' && $uEdit->gender == 'Female') selected @endif>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </div>
                                        <select class="form-control form-control-sm select_camp" name="role" id="roleSelect" onchange="updateCheckboxes()" autocomplete="off">
                                            <option value=""> --- Select Role --- </option>
                                            <option value="Administrator" @if($current_route == 'uEdit' && $uEdit->role == 'Administrator') selected @endif>Administrator</option>
                                            <option value="HR Administrator" @if($current_route == 'uEdit' && $uEdit->role == 'HR Administrator') selected @endif>HR Administrator</option>
                                            <option value="Payroll Administrator" @if($current_route == 'uEdit' && $uEdit->role == 'Payroll Administrator') selected @endif>Payroll Administrator</option>
                                        </select>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Role_error"></span>
                                </div>
                            </div>
                        </div> 
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="email" name="username"  value="{{ $current_route == 'uEdit' ? $uEdit->username : '' }}" placeholder="Enter Username" class="form-control form-control-sm" autocomplete="off">
                                    </div>    
                                </div>
                            </div>
                        </div>

                        {{-- Password. There was no field here at all, which is why
                             a user created on this page could never sign in: the
                             controller hashed request('password') and then left it
                             out of the insert, so the account was stored with none.

                             On EDIT it is optional — leaving it blank keeps the
                             existing password, so an admin fixing a typo in
                             somebody's surname does not have to reissue their
                             credentials to do it. --}}
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        </div>
                                        <input type="password" name="password" id="userPassword"
                                               placeholder="{{ $current_route == 'uEdit' ? 'Leave blank to keep current password' : 'Enter Password (min 8 characters)' }}"
                                               class="form-control form-control-sm" autocomplete="new-password"
                                               {{ $current_route == 'uEdit' ? '' : 'required' }}>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleUserPassword"
                                                    tabindex="-1" aria-label="Show password">
                                                <i class="fas fa-eye" id="toggleUserPasswordIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @error('password')
                                        <span style="color: #FF0000; font-size: 10pt;" class="form-text text-left">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @php
                            $accessArray = isset($uEdit) ? explode(',', $uEdit->access) : [];
                        @endphp

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label>Access Permissions:</label>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="access[0]" value="1" id="access0" class="form-check-input" @if(isset($accessArray[0]) && $accessArray[0] == '1') checked @endif>
                                        <label for="access0" class="form-check-label">EMPLOYEES</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[1]" value="1" id="access1" class="form-check-input" @if(isset($accessArray[1]) && $accessArray[1] == '1') checked @endif>
                                        <label for="access1" class="form-check-label">OFFICES</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[2]" value="1" id="access2" class="form-check-input" @if(isset($accessArray[2]) && $accessArray[2] == '1') checked @endif>
                                        <label for="access2" class="form-check-label">PAYSLIP</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[3]" value="1" id="access3" class="form-check-input" @if(isset($accessArray[3]) && $accessArray[3] == '1') checked @endif>
                                        <label for="access3" class="form-check-label">EVENTS</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[7]" value="1" id="access7" class="form-check-input" @if(isset($accessArray[7]) && $accessArray[7] == '1') checked @endif>
                                        <label for="access7" class="form-check-label">LEAVE</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="access[4]" value="1" id="access4" class="form-check-input" @if(isset($accessArray[4]) && $accessArray[4] == '1') checked @endif>
                                        <label for="access4" class="form-check-label">DTR</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[6]" value="1" id="access6" class="form-check-input" @if(isset($accessArray[6]) && $accessArray[6] == '1') checked @endif>
                                        <label for="access6" class="form-check-label">SETTINGS</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[8]" value="1" id="access7" class="form-check-input" @if(isset($accessArray[8]) && $accessArray[8] == '1') checked @endif>
                                        <label for="access7" class="form-check-label">KIOSK</label>
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
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Access</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                @php $no = 1; @endphp
                                @foreach($users as $user)
                                <tr id="tr-{{ $user->uid }}">
                                    <td>{{ $no++ }}</td>
                                    <td>{{ strtoupper($user->lname).' '.strtoupper($user->fname).' '.strtoupper($user->mname) }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->role }}</td>
                                    <td width="150">
                                        @php 
                                            $access = explode(",", $user->access);
                                        @endphp
                                        @if (isset($access[0]) && $access[0] == 1)
                                            @if($access[0] == 1) <span class="badge badge-secondary">EMPLOYEES</span> @endif
                                        @endif
                                        @if (isset($access[1]) && $access[1] == 1)
                                            @if($access[1] == 1) <span class="badge badge-secondary">OFFICES</span> @endif
                                        @endif
                                        @if (isset($access[2]) && $access[2] == 1)
                                            @if($access[2] == 1) <span class="badge badge-secondary">PAYSLIP</span> @endif
                                        @endif
                                        @if (isset($access[3]) && $access[3] == 1)
                                            @if($access[3] == 1) <span class="badge badge-secondary">EVENTS</span> @endif
                                        @endif
                                        @if (isset($access[4]) && $access[4] == 1)
                                            @if($access[4] == 1) <span class="badge badge-secondary">DTR</span> @endif
                                        @endif
                                        @if (isset($access[6]) && $access[6] == 1)
                                            @if($access[6] == 1) <span class="badge badge-secondary">SETTINGS</span> @endif
                                        @endif
                                        @if (isset($access[7]) && $access[7] == 1)
                                            @if($access[7] == 1) <span class="badge badge-secondary">LEAVE</span> @endif
                                        @endif
                                        @if (isset($access[8]) && $access[8] == 1)
                                            @if($access[8] == 1) <span class="badge badge-secondary">KIOSK</span> @endif
                                        @endif
                                    </td>                                                                        
                                    <td class="text-center">
                                        <div class="btn-actions justify-content-center">
                                            <a href="{{ route('uEdit', $user->uid) }}" title="Edit user" class="btn-icon btn-icon--info">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button value="{{ $user->uid }}" title="Delete user" class="btn-icon btn-icon--danger users-delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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

<script>
    // Reveal toggle for the password field. An admin typing a password FOR
    // someone else cannot rely on muscle memory to catch a typo, and the person
    // it is issued to has no way to discover the mistake except by failing to
    // sign in — so being able to read it back before saving matters more here
    // than on a normal login form.
    (function () {
        var field = document.getElementById('userPassword');
        var btn   = document.getElementById('toggleUserPassword');
        var icon  = document.getElementById('toggleUserPasswordIcon');

        if (!field || !btn) return;

        btn.addEventListener('click', function () {
            var shown = field.type === 'text';

            field.type = shown ? 'password' : 'text';
            btn.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');

            if (icon) {
                icon.classList.toggle('fa-eye', shown);
                icon.classList.toggle('fa-eye-slash', !shown);
            }
        });
    })();
</script>
@endsection
