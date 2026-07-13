<style>
    .employee-card {
        background-color: #ffffff;
        background-image: url('{{ asset('images/qr-bg.png') }}');
        background-size: cover;
        background-position: center;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        width: 270px;
        height: 360px;
        padding: 20px;
        text-align: center;
        font-family: 'Arial', sans-serif;
        font-size: 14px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 2px solid #e0e0e0;
    }

    .qr-code {
        padding: 9px 5px 2px 4px; /* top right bottom left */
        margin-left: 8px;
    }

</style>

<div class="col-lg-3">
    <div class="card card-info card-outline">
        <div class="card-body box-profile">
            <a href="#" onclick="openQRModal()"><i class="fas fa-qrcode text-primary" data-toggle="modal" data-target="#qrModal" style="font-size: 25px;"></i></a>
            <div class="text-center position-relative">
                <div class="profile-image-container">
                    @php
                        $imageUrl = asset('Profile/Employee/' . $employee->profile);
                        $imagePath = public_path('Profile/Employee/' . $employee->profile);
                    @endphp
                    <img src="{{ file_exists($imagePath) ? $imageUrl : asset('Profile/Employee/default.png') }}" alt="User Image" class="profile-user-img img-fluid" id="changeProfilePicture">
                </div>
                <input type="file" id="profilePictureInput" style="display: none;" accept="image/*">
            </div>
            
            <h3 class="profile-username text-center">
            {{ ucwords(strtolower(str_replace('Ñ', 'ñ', $employee->fname))) }} {{ ucwords(strtolower(str_replace('Ñ', 'ñ', $employee->lname))) }}</h3>
            <p class="text-muted text-center">{{ $employee->position }}</p>
    
            <ul class="list-group list-group-unbordered custom-gap">
                @php
                    $hireDate = $employee->date_hired;
                    $currentDate = date('Y-m-d'); 

                    $startDate = new DateTime($hireDate);
                    $endDate = new DateTime($currentDate);

                    $interval = $startDate->diff($endDate);

                    $years = $interval->y;
                    $months = $interval->m;
                @endphp
                <li class="list-group-item">
                    <b>Employee ID. :</b> <span class="float-right text-muted">{{ $employee->emp_ID }}</span>
                </li>
                <li class="list-group-item">
                    <b>Item No. :</b> <span class="float-right text-muted">{{ $employee->item_no }}</span>
                </li>
                <li class="list-group-item">
                    <b>Service :</b> <span class="float-right text-muted">{{ $years.' years' .' '. $months. ' months' }}</span>
                </li>
            </ul>
            @if($employee->stat_1 == 1)
            <a href="#" class="btn btn-success btn-sm btn-block mt-2"><b>Active</b></a>
            @else
            <a href="#" class="btn btn-danger btn-sm btn-block mt-2"><b>Suspended</b></a>
            @endif
        </div>
        <!-- /.card-body -->
    </div>

    <div class="card card-info">
        <div class="card-header" style="padding: 6px !important;">
            <i class="fas fa-id-card"></i><b> PERSONAL DATA SHEET</b> 
        </div>
        <div class="card-footer p-0">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('PDS', $employee->id) : route('empPDS') }}" class="nav-link">
                        <i class="{{ request()->is('pds/personal-info/*') ||  request()->is('pds') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-user" style="width: 20px; margin-left: 3px;"></i> 
                        <span class="{{ request()->is('pds/personal-info/*') || request()->is('pds') ? 'text-dark' : 'text-muted' }} text-bold">Personal Information</span> 
                        <i class="float-right fas fa-check-circle text-success pt-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('familybg', $employee->id) : route('familybg') }}" class="nav-link">
                        <i class="{{ request()->is('pds/family-bg') || request()->is('pds/family-bg/*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-users" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/family-bg') || request()->is('pds/family-bg/*') ? 'text-dark' : 'text-muted' }} text-bold">Family Background</span>
                        <i class="float-right fas {{ (isset($columnstatus) && ($columnstatus['colfamstat'] == 1)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>                        
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('educbg', $employee->id) : route('educbg') }}" class="nav-link">
                        <i class="{{ request()->is('pds/educ-bg') || request()->is('pds/educ-bg/*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-graduation-cap" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/educ-bg') || request()->is('pds/educ-bg/*') ? 'text-dark' : 'text-muted' }} text-bold">Educational Background</span>
                        <i class="float-right fas {{ (isset($columnstatus) && ($columnstatus['coleducstat'] == 1)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('eligibility', $employee->id) : route('eligibility') }}" class="nav-link">
                        <i class="{{ request()->is('pds/eligibility') || request()->is('pds/eligibility/*') || isset($eligibilityedit) ? 'text-dark' : 'text-muted' }} pr-2 fas fas fa-certificate" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/eligibility') || request()->is('pds/eligibility/*') || isset($eligibilityedit) ? 'text-dark' : 'text-muted' }} text-bold">Eligibility</span>
                        <i class="float-right fas {{ (isset($columnstatus['eligibility']) && (count($columnstatus['eligibility']) > 0)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('work-experience', $employee->id) : route('work-experience') }}" class="nav-link">
                        <i class="{{ request()->is('pds/work-experience') || request()->is('pds/work-experience/*') || isset($workexperienceedit) ? 'text-dark' : 'text-muted' }} pr-2 fas fa-briefcase" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/work-experience') || request()->is('pds/work-experience/*') || isset($workexperienceedit) ? 'text-dark' : 'text-muted' }} text-bold">Work Experience</span>
                        <i class="float-right fas {{ (isset($columnstatus['workexperience']) && (count($columnstatus['workexperience']) > 0)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('voluntary-work', $employee->id) : route('voluntary-work') }}" class="nav-link">
                        <i class="{{ request()->is('pds/voluntary-work') || request()->is('pds/voluntary-work/*') || isset($voluntaryworksedit) ? 'text-dark' : 'text-muted' }} pr-2 fas fa-hand-holding-heart" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/voluntary-work') || request()->is('pds/voluntary-work/*') || isset($voluntaryworksedit) ? 'text-dark' : 'text-muted' }} text-bold">Voluntary Work</span>
                        <i class="float-right fas {{ (isset($columnstatus['voluntaryworks']) && (count($columnstatus['voluntaryworks']) > 0)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li> 
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('learning-dev', $employee->id) : route('learning-dev') }}" class="nav-link">
                        <i class="{{ request()->is('pds/learning-dev') || request()->is('pds/learning-dev/*') || isset($learningdevedit) ? 'text-dark' : 'text-muted' }} pr-2 fas fas fa-book" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/learning-dev') || request()->is('pds/learning-dev/*') || isset($learningdevedit) ? 'text-dark' : 'text-muted' }} text-bold">Learning and Development</span>
                        <i class="float-right fas {{ (isset($columnstatus['learningdev']) && (count($columnstatus['learningdev']) > 0)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('otherInfo', $employee->id) : route('otherInfo') }}" class="nav-link">
                        <i class="{{ request()->is('pds/other-info') || request()->is('pds/other-info/*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-info-circle" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/other-info') || request()->is('pds/other-info/*') ? 'text-dark' : 'text-muted' }} text-bold">Other Information</span>
                        <i class="float-right fas {{ (isset($columnstatus) && ($columnstatus['colotherinfo'] == 1)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>  
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('infoQuestion', $employee->id) : route('infoQuestion') }}" class="nav-link">
                        <i class="{{ request()->is('pds/info-question') || request()->is('pds/info-question/*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-question-circle" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/info-question') || request()->is('pds/info-question/*') ? 'text-dark' : 'text-muted' }} text-bold">Other Information Questions</span>
                        <i class="float-right fas {{ (isset($columnstatus) && ($columnstatus['colinfoquestion'] == 1)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('references', $employee->id) : route('references') }}" class="nav-link">
                        <i class="{{ request()->is('pds/references') || request()->is('pds/references/*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-address-book" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/references') || request()->is('pds/references/*') ? 'text-dark' : 'text-muted' }} text-bold">References</span>
                        <i class="float-right fas {{ (isset($columnstatus) && ($columnstatus['colreferences'] == 1)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('govids', $employee->id) : route('govids') }}" class="nav-link">
                        <i class="{{ request()->is('pds/government-id') || request()->is('pds/government-id/*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-id-card" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/government-id') || request()->is('pds/government-id/*') ? 'text-dark' : 'text-muted' }} text-bold">Government Issued ID</span>
                        <i class="float-right fas {{ (isset($columnstatus) && ($columnstatus['colgovids'] == 1)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>
                {{-- <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('signature', $employee->id) : route('signature') }}" class="nav-link">
                        <i class="{{ request()->is('pds/esign') || request()->is('pds/esign/*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-id-card" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/esign') || request()->is('pds/esign/*') ? 'text-dark' : 'text-muted' }} text-bold">Signature</span>
                        <i class="float-right fas {{ (isset($employee) && ($employee->signature !== null)) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li> --}}
                {{-- <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="text-muted pr-2 fas fa-coins" style="width: 20px;"></i>
                        <span class="text-muted text-bold">Income And Deductions</span>
                        <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                    </a>
                </li> --}}
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('generatepds', $employee->id) : route('generatepds') }}" target="_blank" class="nav-link">
                        <i class="text-muted pr-2 fas fa-eye" style="width: 20px;"></i>
                        <span class="text-muted text-bold">Preview Personal Data Sheet</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == "web") ? route('genpdsAtthachment', $employee->id) : route('genpdsAtthachment') }}" target="_blank" class="nav-link">
                        <i class="text-muted pr-2 fas fa-eye" style="width: 20px;"></i>
                        <span class="text-muted text-bold">Attachment to CS Form No. 212</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ ($guard == 'web') ? route('signature', $employee->id) : route('signature') }}" class="nav-link">
                        <i class="{{ request()->is('pds/signature') || request()->is('pds/signature/*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-signature" style="width: 20px;"></i>
                        <span class="{{ request()->is('pds/signature') || request()->is('pds/signature/*') ? 'text-dark' : 'text-muted' }} text-bold">E-Signature</span>
                    </a>
                </li>
            </ul> 
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <!-- Download Button -->
                <a href="#" id="downloadBtn"
                    class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center"
                    style="width: 30px; height: 30px; position: absolute; top: 10px; right: 10px; z-index: 999;">
                    <i class="fas fa-download"></i>
                </a>

                <!-- Employee Card with QR Code -->
                <div class="employee-card-content">
                    <div class="employee-card" id="employeeCard">
                        <div class="qr-code" id="qrcode">
                            <!-- QR Code will be generated here -->
                        </div>

                        <div class="details mt-3" style="text-align: center; border-radius: 5px; background-color: rgba(97, 91, 91, 0.342); color: #fff; padding-top: 5px; padding-bottom: 5px;">
                            <h5 style="margin: 0; font-weight: bold; font-size: 1.2rem;">
                                {{ ucwords(strtoupper(str_replace('Ñ', 'ñ', $employee->fname))) }}
                                {{ ucwords(strtoupper(str_replace('Ñ', 'ñ', $employee->lname))) }}
                                {{ ucwords(strtoupper(str_replace('Ñ', 'ñ', $employee->suffix))) }}
                            </h5>
                            <p style="margin: 4px 0; font-style: italic;">{{ ($employee->emp_status == 1) ? $employee->position : 'OFFICE STAFF'  }}</p>
                            {{-- <p style="margin: 0; font-weight: bold;">MAIN CAMPUS</p> --}}
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@php
    $shortEncrypted = shortEncrypt($employee->emp_ID);
@endphp
<script>
    function openQRModal() {
        const qrElements = ['qrcode', 'qrcode1'];
        const token = "{{ $shortEncrypted }}";
        
        qrElements.forEach(elementId => {
            const qrElement = document.getElementById(elementId);
            if (qrElement) {
                qrElement.innerHTML = "";
                new QRCode(qrElement, {
                    text: token,
                    width: 205,
                    height: 205,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });
    }
</script>

<script>
    document.getElementById('downloadBtn').addEventListener('click', function() {
        const target = document.querySelector('.employee-card-content');
        html2canvas(target, {
            backgroundColor: null,
            useCORS: true
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = '{{ $employee->emp_ID }}.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    });
</script>
