<div class="col-lg-3">
    <div class="card card-info card-outline">
        @if($guard == "web")
            <div class="p-1">
                <select class="form-control select2" id="employee" style="width: 100%;" onchange="redirectToLeaveRead(this)">
                    @foreach ($emplalls as $emp)
                        <option value="{{ $emp->id }}" {{ ($employee->id == $emp->id) ? 'selected' : '' }}>
                            {{ strtoupper($emp->fname) }} {{ strtoupper($emp->lname) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="card-body box-profile">
            <div class="text-center position-relative">
                @php
                    use Illuminate\Support\Facades\File;
                    $profileImagePath = 'Profile/Employee/' . $employee->profile;
                    $imagePath = File::exists(public_path($profileImagePath)) ? $profileImagePath : 'Profile/Employee/default.png';
                @endphp
                <div class="profile-image-container">
                    <img src="{{ asset($imagePath) }}" alt="User Image" class="profile-user-img img-fluid" id="changeProfilePicture">
                </div>
                <input type="file" id="profilePictureInput" style="display: none;" accept="image/*">
            </div>
            
            <h3 class="profile-username text-center">{{ ucwords(strtolower($employee->fname)) }} {{ ucwords(strtolower($employee->lname)) }}</h3>

            <p class="text-muted text-center">{{ $employee->position }}</p>
    
            <ul class="list-group list-group-unbordered custom-gap">
                @if($guard == "web")
                    <i class="fas fa-cog" data-toggle="modal" data-target="#modalSettingLeave" style="color: rgb(135, 129, 129); margin-left: 100%; margin-bottom: -8px; float: right;"></i>
                @endif
                <li class="list-group-item">
                    <b>Vacation Leave</b> <span class="float-right mt-1 badge badge-info" id="b-vl">{{ $employee->vl }}</span>
                </li>
                {{-- <li class="list-group-item">
                    <b>Mandatory Leave</b> <span class="float-right mt-1 badge badge-info" id="b-ml">{{ $employee->special_pl }}</span>
                </li> --}}
                <li class="list-group-item">
                    <b>Sick Leave</b> <span class="float-right mt-1 badge badge-info" id="b-sl">{{ $employee->sl }}</span>
                </li>
                <li class="list-group-item">
                    <b>Special Privilege Leave</b> <span class="float-right mt-1 badge badge-info" id="special-pl">{{ $employee->special_pl ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>Solo Parent Leave</b> <span class="float-right mt-1 badge badge-info" id="solo-pl">{{ $employee->solo_pl ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>Study Leave</b> <span class="float-right mt-1 badge badge-info" id="study-leave">{{ $employee->study_leave ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>10-Day VAWC Leave</b> <span class="float-right mt-1 badge badge-info" id="vawc-leave">{{ $employee->vawc_leave ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>Rehabilitation Privilege</b> <span class="float-right mt-1 badge badge-info" id="rehab-leave">{{ $employee->rehab_leave ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>Special Leave Benefits for Women</b> <span class="float-right mt-1 badge badge-info" id="benefits-leave">{{ $employee->benefits_leave ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>Special Emergency (Calamity) Leave</b> <span class="float-right mt-1 badge badge-info" id="calamity-leave">{{ $employee->calamity_leave ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>Adoption Leave</b> <span class="float-right mt-1 badge badge-info" id="adopt-leave">{{ $employee->adopt_leave ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>Vacation Service Credit</b> <span class="float-right mt-1 badge badge-info" id="servcred-leave">{{ $employee->servcred_leave ?? 0 }}</span>
                </li>
                <li class="list-group-item">
                    <b>Wellness Leave</b> <span class="float-right mt-1 badge badge-info" id="wellness-leave">{{ $employee->well_leave ?? 0 }}</span>
                </li>
            </ul>
        </div>
        <!-- /.card-body -->
    </div>
</div>