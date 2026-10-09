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
            {{-- The entries come from pds_sections() in app/Helpers/helpers.php,
                 which the Tailwind side panel (emp/partials/pds-side) draws
                 from too. --}}
            <ul class="nav flex-column">
                @foreach(pds_sections($employee, $guard, $columnstatus ?? null) as $section)
                    <li class="nav-item">
                        <a href="{{ $section['url'] }}" class="nav-link" @if($section['newTab']) target="_blank" @endif>
                            <i class="{{ $section['active'] ? 'text-dark' : 'text-muted' }} pr-2 {{ $section['icon'] }}" style="width: 20px;"></i>
                            <span class="{{ $section['active'] ? 'text-dark' : 'text-muted' }} text-bold">{{ $section['label'] }}</span>
                            @if($section['done'] !== null)
                                <i class="float-right fas {{ $section['done'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} pt-1"></i>
                            @endif
                        </a>
                    </li>
                @endforeach
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

                {{-- The card itself is shared with the Tailwind shell. --}}
                @include('emp.partials.qr-card')
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
