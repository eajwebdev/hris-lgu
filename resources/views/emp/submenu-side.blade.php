<style>
    /* Employee QR card — a municipal ID card carrying the Mabinay seal.
       Rendered to PNG by html2canvas, so everything here is plain CSS with
       same-origin images: no external fonts, no remote assets. */
    .employee-card {
        width: 300px;
        border-radius: 16px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #E5E7EB;
        box-shadow: 0 18px 40px -12px rgba(15, 23, 42, .25);
        font-family: "Inter", Arial, sans-serif;
        text-align: center;
        margin: 0 auto;
    }

    .employee-card__header {
        background: linear-gradient(135deg, #1E7A45 0%, #10502C 100%);
        padding: 14px 12px 12px;
        color: #fff;
        position: relative;
    }
    .employee-card__header::after {
        content: "";
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 4px;
        background: linear-gradient(90deg, #EF9017, #FBBF24, #EF9017);
    }
    .employee-card__seal {
        width: 54px;
        height: 54px;
        object-fit: contain;
        border-radius: 50%;
        background: #fff;
        padding: 3px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, .25);
    }
    .employee-card__org {
        margin: 7px 0 0;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        line-height: 1.3;
    }
    .employee-card__sub {
        margin: 2px 0 0;
        font-size: 8.5px;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .78);
    }

    .employee-card__qr {
        padding: 16px 16px 10px;
        background: #fff;
    }
    .employee-card__qr .qr-code {
        display: inline-block;
        padding: 10px;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        background: #fff;
        line-height: 0;
    }
    .employee-card__qr .qr-code img,
    .employee-card__qr .qr-code canvas { display: block; }

    .employee-card__scan {
        margin: 8px 0 0;
        font-size: 8.5px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #94A3B8;
    }

    .employee-card__body {
        padding: 4px 16px 16px;
        background: #fff;
    }
    .employee-card__name {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0F172A;
        line-height: 1.25;
        letter-spacing: -.01em;
    }
    .employee-card__position {
        margin: 3px 0 10px;
        font-size: 11px;
        color: #64748B;
        line-height: 1.35;
    }
    .employee-card__id {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 999px;
        background: #FEF3E2;
        color: #B26205;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .06em;
    }

    .employee-card__footer {
        padding: 7px 12px;
        background: #F1F5F9;
        border-top: 1px solid #E5E7EB;
        font-size: 8px;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #94A3B8;
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
                {{-- Admin/HR see it on anyone's PDS; an employee sees it on their
                     own, where they can register their own face. The middleware on
                     the route is the real boundary; hiding the link just keeps it
                     out of everyone else's way. --}}
                @if(\App\Http\Middleware\EnsureFaceSelfOrRegistrar::allowsFor($employee))
                @php
                    $onFacePage = request()->is('pds/face-recognition') || request()->is('pds/face-recognition/*');
                    $faceRegistered = $employee->faceSummary()['registered'];
                @endphp
                <li class="nav-item">
                    <a href="{{ ($guard == 'web') ? route('faceRecognition', $employee->id) : route('faceRecognition') }}" class="nav-link">
                        <i class="{{ $onFacePage ? 'text-dark' : 'text-muted' }} pr-2 fas fa-user-shield" style="width: 20px;"></i>
                        <span class="{{ $onFacePage ? 'text-dark' : 'text-muted' }} text-bold">Face Recognition</span>
                        <i class="float-right fas {{ $faceRegistered ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 356px;">
        <div class="modal-content">
            <div class="modal-body text-center" style="padding: 18px;">
                <!-- Download the card as a PNG -->
                <a href="#" id="downloadBtn" title="Download card"
                    class="btn-icon btn-icon--brand"
                    style="position: absolute; top: 12px; right: 12px; z-index: 999;">
                    <i class="fas fa-download"></i>
                </a>

                <!-- Employee Card with QR Code -->
                <div class="employee-card-content">
                    <div class="employee-card" id="employeeCard">

                        <div class="employee-card__header">
                            <img src="{{ asset('logo.png') }}" alt="Municipality of Mabinay Official Seal" class="employee-card__seal">
                            <p class="employee-card__org">Municipality of Mabinay</p>
                            <p class="employee-card__sub">Human Resource Information System</p>
                        </div>

                        <div class="employee-card__qr">
                            <div class="qr-code" id="qrcode">
                                <!-- QR Code is rendered here -->
                            </div>
                            <p class="employee-card__scan">Scan to log attendance</p>
                        </div>

                        <div class="employee-card__body">
                            <h5 class="employee-card__name">
                                {{ strtoupper(str_replace('Ñ', 'ñ', $employee->fname)) }}
                                {{ strtoupper(str_replace('Ñ', 'ñ', $employee->lname)) }}
                                {{ strtoupper(str_replace('Ñ', 'ñ', $employee->suffix)) }}
                            </h5>
                            <p class="employee-card__position">
                                {{ ($employee->emp_status == 1 && $employee->position) ? $employee->position : 'Office Staff' }}
                            </p>
                            <span class="employee-card__id">{{ $employee->emp_ID }}</span>
                        </div>

                        <div class="employee-card__footer">
                            Property of LGU Mabinay &middot; Return if found
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
                    width: 196,
                    height: 196,
                    // The seal's green, so the code matches the card.
                    colorDark: "#10502C",
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
            useCORS: true,
            scale: 3            // print-quality PNG rather than a screen-sized one
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = '{{ $employee->emp_ID }}.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    });
</script>
