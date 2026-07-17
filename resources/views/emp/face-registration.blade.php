{{--
    Face Recognition Registration — Phase 1 (enrolment only).

    Rendered for Admin/HR on the web guard, and for an employee viewing their
    own record. This @if is cosmetic: the routes behind every control here are
    gated by the face.self / face.registrar middleware, so a user who reaches
    the URLs another way is refused regardless of what this view decided to draw.
--}}
@if(\App\Http\Middleware\EnsureFaceSelfOrRegistrar::allowsFor($employee))
@php
    $face = $employee->faceSummary();
    $steps = [
        'front'    => ['label' => 'Front Face',       'icon' => 'fa-user',        'hint' => 'Look directly at the camera'],
        'left'     => ['label' => 'Left Angle',       'icon' => 'fa-arrow-left',  'hint' => 'Turn your head slightly to the left'],
        'right'    => ['label' => 'Right Angle',      'icon' => 'fa-arrow-right', 'hint' => 'Turn your head slightly to the right'],
        // Head movement only: the 5-point landmarks the SCRFD detector gives
        // carry no eye contour, so a blink cannot be seen anymore.
        'movement' => ['label' => 'Natural Movement', 'icon' => 'fa-arrows-alt',  'hint' => 'Slightly move your head'],
    ];
@endphp

<div class="card card-info card-outline" id="face-section">
    <div class="card-header">
        <h2 class="card-title text-success1">
            <b>FACE RECOGNITION REGISTRATION</b>
        </h2>
    </div>

    <div class="card-body bg-form">

        {{-- Status. Swapped in place by the script after a register/remove. --}}
        <div id="face-status-registered" class="{{ $face['registered'] ? '' : 'd-none' }}">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div class="mb-2 mr-3">
                    <h5 class="mb-2">
                        <span class="badge badge-success px-2 py-1">
                            <i class="fas fa-check-circle"></i> Face Registered
                        </span>
                    </h5>
                    <div class="text-muted small">
                        <div>
                            <i class="fas fa-camera fa-fw"></i>
                            <span id="face-capture-count">{{ $face['capture_count'] }}</span> registered capture(s)
                            <span id="face-legacy-note" class="{{ $face['legacy'] ? '' : 'd-none' }}">
                                &mdash; <em>enrolled on the previous device, before the four-pose capture set</em>
                            </span>
                        </div>
                        <div>
                            <i class="fas fa-calendar-alt fa-fw"></i>
                            Registered <span id="face-registered-at">{{ $face['registered_at'] ?? 'date not recorded' }}</span>
                        </div>
                        <div>
                            <i class="fas fa-user-shield fa-fw"></i>
                            By <span id="face-registered-by">{{ $face['registered_by'] ?? 'not recorded' }}</span>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <button type="button" class="btn btn-warning btn-sm" id="face-reregister-btn">
                        <i class="fas fa-sync-alt"></i> Re-register Face
                    </button>
                    {{-- Erasing a biometric stays with Admin/HR; self-service
                         covers registering and re-registering only. --}}
                    @if(\App\Http\Middleware\EnsureFaceRegistrar::allows())
                    <button type="button" class="btn btn-danger btn-sm" id="face-remove-btn">
                        <i class="fas fa-trash"></i> Remove Face Data
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div id="face-status-unregistered" class="{{ $face['registered'] ? 'd-none' : '' }}">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div class="mb-2 mr-3">
                    <h5 class="mb-2">
                        <span class="badge badge-danger px-2 py-1">
                            <i class="fas fa-times-circle"></i> Face Not Registered
                        </span>
                    </h5>
                    <p class="text-muted small mb-0">
                        Four captures are taken from the webcam. Only the resulting face
                        signature is stored &mdash; no photograph is saved at any point.
                    </p>
                </div>

                <div class="mb-2">
                    <button type="button" class="btn btn-success btn-sm" id="face-register-btn">
                        <i class="fas fa-camera"></i> Register Face
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{--
    Registration modal. Bootstrap 4 (data-toggle / data-dismiss) to match the
    AdminLTE build this project ships — not BS5's data-bs-* attributes.
--}}
<div class="modal fade" id="face-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
     aria-labelledby="face-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header py-2">
                <h5 class="modal-title text-success1" id="face-modal-title">
                    <b>Register Face &mdash; {{ trim("{$employee->fname} {$employee->lname}") }}</b>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="face-modal-close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="row">

                    {{-- Camera --}}
                    <div class="col-lg-7 face-col-camera">
                        <div class="face-stage">
                            {{-- Mirrored for the subject's benefit only. The face engine
                                 reads the raw video frame, so the CSS transform never
                                 touches the geometry the checks are computed from. --}}
                            <video id="face-video" autoplay muted playsinline></video>
                            <canvas id="face-overlay"></canvas>

                            <div class="face-stage__veil" id="face-veil">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                    <div id="face-veil-text">Loading face recognition models&hellip;</div>
                                </div>
                            </div>
                        </div>

                        {{-- The single line of guidance the operator reads. --}}
                        <div class="face-feedback mt-2" id="face-feedback">
                            <i class="fas fa-circle-notch fa-spin fa-fw"></i>
                            <span id="face-feedback-text">Starting camera&hellip;</span>
                        </div>

                        <div class="mt-2">
                            <button type="button" class="btn btn-success btn-block" id="face-capture-btn" disabled>
                                <i class="fas fa-camera"></i>
                                <span id="face-capture-btn-text">Capture Front Face</span>
                            </button>
                        </div>
                    </div>

                    {{-- Progress --}}
                    <div class="col-lg-5 face-col-progress">
                        <h6 class="mb-2 face-progress-title"><b>Face Registration Progress</b></h6>

                        <ul class="list-group list-group-flush" id="face-steps">
                            @foreach($steps as $type => $step)
                                <li class="list-group-item face-step px-2 py-2" data-step="{{ $type }}">
                                    <div class="d-flex align-items-center">
                                        <span class="face-step__mark mr-2" aria-hidden="true">
                                            <i class="far fa-circle"></i>
                                        </span>
                                        <div class="flex-grow-1">
                                            <div class="face-step__label">
                                                <i class="fas {{ $step['icon'] }} fa-fw text-muted"></i>
                                                {{ $step['label'] }}
                                            </div>
                                            <small class="text-muted">{{ $step['hint'] }}</small>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Completed</span>
                                <span><b id="face-progress-count">0</b>/4</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" id="face-progress-bar"
                                     role="progressbar" style="width: 0%"
                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="4"></div>
                            </div>
                        </div>

                        <p class="text-muted small mt-3 mb-0 face-privacy-note">
                            <i class="fas fa-lock fa-fw"></i>
                            Only a mathematical face signature is transmitted. No image
                            leaves this browser.
                        </p>
                    </div>

                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" id="face-restart-btn">
                    <i class="fas fa-undo"></i> Start Over
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">
                    Cancel
                </button>
                {{-- Enabled only once all four captures have passed validation. --}}
                <button type="button" class="btn btn-success btn-sm" id="face-finish-btn" disabled>
                    <i class="fas fa-save"></i> Finish Registration
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Everything the script needs, so no threshold or URL is duplicated in JS. --}}
@php
    $faceConfig = [
        'employeeId'   => $employee->id,
        'employeeName' => trim("{$employee->fname} {$employee->lname}"),
        'registered'   => $face['registered'],
        'modelsUrl'    => asset('models/arcface'),
        'ortPath'      => asset('js/onnx') . '/',
        'urls'         => [
            'store'  => route('faceRegister', $employee->id),
            'remove' => route('faceRemove', $employee->id),
        ],
        'steps'      => $steps,
        'thresholds' => config('face.client'),
    ];
@endphp
<script id="face-config" type="application/json">@json($faceConfig)</script>
@endif
