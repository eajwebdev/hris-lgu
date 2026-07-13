<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CPSU | Job Application Review</title>
    <!-- Bootstrap 5 CSS + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(145deg, #eef5f0 0%, #e0ece5 100%);
            min-height: 100vh;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .app-card {
            max-width: 880px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 2rem;
            overflow: hidden;
            box-shadow: 0 25px 45px -12px rgba(0, 32, 16, 0.25);
            transition: transform 0.2s;
            border: 1px solid rgba(24, 119, 68, 0.15);
        }

        .card-header-premium {
            background: linear-gradient(115deg, #146b3a 0%, #1e8b52 100%);
            padding: 1.25rem 2rem;
            position: relative;
        }

        .status-chip {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            border-radius: 100px;
            padding: 0.25rem 1rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            margin-bottom: 0.75rem;
        }

        .app-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        /* Top-right floating button */
        .fab-forward {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            background: white;
            color: #146b3a;
            border: none;
            padding: 0.6rem 1.4rem;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 3rem;
            box-shadow: 0 10px 20px rgba(20, 107, 58, 0.25);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            backdrop-filter: blur(2px);
        }

        .fab-forward:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 28px rgba(20, 107, 58, 0.3);
            background: #fefefe;
        }

        .fab-forward.disabled-forward, .fab-forward:disabled {
            background: #e9ecef;
            color: #6c757d;
            transform: none;
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.8;
        }

        .card-body-custom {
            padding: 1.8rem 2rem;
        }

        .alert-modern {
            background: #f0fdf4;
            border-left: 5px solid #146b3a;
            border-radius: 1rem;
            padding: 0.9rem 1.2rem;
            margin-bottom: 1.8rem;
            font-size: 0.9rem;
        }

        .section-badge-light {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #146b3a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-tile {
            background: #fbfdfc;
            border: 1px solid #e2ede8;
            border-radius: 1rem;
            padding: 0.65rem 1rem;
            height: 100%;
            transition: all 0.1s ease;
        }

        .info-label-sm {
            font-size: 0.65rem;
            font-weight: 600;
            color: #5b6e6c;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.2rem;
        }

        .info-value-md {
            font-weight: 700;
            font-size: 0.9rem;
            color: #1a2c2a;
            line-height: 1.3;
        }

        .app-number {
            color: #146b3a;
            font-weight: 800;
        }

        .attachment-modern {
            background: #fffbf0;
            border-radius: 1rem;
            border: 1px solid #ffe2b5;
            border-left: 5px solid #e67e22;
            padding: 0.9rem 1.2rem;
            margin-top: 1.5rem;
        }

        .pdf-link {
            color: #e67e22;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.2s;
        }

        .pdf-link:hover {
            color: #c95f0e;
            text-decoration: underline;
        }

        /* No text badges - just visual indicators removed */
        .signature-line {
            margin-top: 1.4rem;
            font-size: 0.8rem;
            border-top: 1px solid #ecfdf3;
            padding-top: 1rem;
        }

        .footer-card {
            background: #f9fdfb;
            border-top: 1px solid #e4ece8;
            font-size: 0.7rem;
            padding: 0.8rem;
            text-align: center;
            color: #5a736e;
        }

        .spinner-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        @media (max-width: 640px) {
            .fab-forward {
                top: 12px;
                right: 12px;
                padding: 0.45rem 1.1rem;
                font-size: 0.75rem;
            }
            .card-body-custom {
                padding: 1.2rem;
            }
            .card-header-premium {
                padding: 1rem 1.2rem;
            }
            .app-title {
                font-size: 1.2rem;
            }
            .info-value-md {
                font-size: 0.8rem;
            }
            .alert-modern {
                padding: 0.7rem 1rem;
                font-size: 0.8rem;
            }
        }

        @media (max-height: 750px) {
            .card-body-custom {
                padding: 1rem 1.5rem;
            }
            .info-tile {
                padding: 0.45rem 0.85rem;
            }
            .alert-modern {
                margin-bottom: 1rem;
            }
            .attachment-modern {
                margin-top: 1rem;
                padding: 0.7rem 1rem;
            }
            .signature-line {
                margin-top: 1rem;
            }
        }

        .info-tile:hover {
            background: #ffffff;
            border-color: #cde2d9;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
    </style>
</head>
<body>

<!-- Top-right floating button - changes to "Already Forwarded to HR" when clicked -->
<button class="fab-forward" id="forwardActionBtn" data-application-id="{{ $applications->id ?? 1 }}">
    <i class="bi bi-send-check-fill"></i> <span>Mark as Forwarded</span>
</button>

<div class="app-card">
    <div class="card-header-premium">
        <div class="status-chip text-white">
            <i class="bi bi-bell-fill"></i> New Application
        </div>
        <h1 class="text-white app-title">Job Application Received</h1>
        <p class="text-white-50 mb-0 small">CPSU Career Portal · ready for review</p>
    </div>

    <div class="card-body-custom">
        <div class="alert-modern">
            <i class="bi bi-envelope-paper-fill me-2" style="color:#146b3a;"></i>
            <strong>Dear Records Office Team,</strong><br>
            A new application has been submitted via the CPSU portal. Please verify the candidate details below.
        </div>

        <div class="section-badge-light">
            <i class="bi bi-person-vcard fs-6"></i> APPLICANT PROFILE
        </div>

        <div class="row g-3">
            <div class="col-sm-6">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-hash"></i> Application No.</div>
                    <div class="info-value-md app-number">{{ $applications->app_number ?? 'APP-2026-000123' }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-briefcase"></i> Position</div>
                    <div class="info-value-md">{{ $applications->title ?? 'Administrative Aide I' }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-person"></i> Full Name</div>
                    <div class="info-value-md">
                        {{ strtoupper(($applications->first_name ?? 'Juan') . ' ' . (!empty($applications->middle_name ?? '') ? substr($applications->middle_name, 0, 1).'. ' : '') . ($applications->last_name ?? 'Dela Cruz')) }}
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-envelope"></i> Email</div>
                    <div class="info-value-md">{{ $applications->email ?? 'juan.delacruz@email.com' }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-phone"></i> Mobile</div>
                    <div class="info-value-md">{{ $applications->mobile ?? '0912 345 6789' }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-gender-ambiguous"></i> Age / Sex</div>
                    <div class="info-value-md">{{ $applications->age ?? '24' }} / {{ strtoupper($applications->sex ?? 'Male') }}</div>
                </div>
            </div>
            <div class="col-12">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-geo-alt"></i> Address</div>
                    <div class="info-value-md">{{ $applications->address ?? 'Kabankalan City, Negros Occidental' }}</div>
                </div>
            </div>
            <div class="col-12">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-mortarboard"></i> Education</div>
                    <div class="info-value-md">{{ $applications->education ?? 'Bachelor of Science in Information Technology' }}</div>
                </div>
            </div>
            <div class="col-12">
                <div class="info-tile">
                    <div class="info-label-sm"><i class="bi bi-patch-check-fill"></i> Eligibility</div>
                    <div class="info-value-md">{{ $applications->eligibility ?? 'Civil Service Professional Eligibility' }}</div>
                </div>
            </div>
        </div>

        <!-- Attachment area - no status text badges -->
        <div class="attachment-modern" id="attachmentBox">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-paperclip fs-5" style="color:#e67e22;"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">📄 Intent Letter Attachment</div>
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . ($applications->intent ?? '')) }}" class="pdf-link" target="_blank" id="pdfLink">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Intent_Letter.pdf
                        </a>
                        @php
                            $fileSize = '';
                            if(isset($applications->intent) && Storage::disk('public')->exists($applications->intent)) {
                                $bytes = Storage::disk('public')->size($applications->intent);
                                if ($bytes >= 1048576) {
                                    $fileSize = round($bytes / 1048576, 2) . ' MB';
                                } elseif ($bytes >= 1024) {
                                    $fileSize = round($bytes / 1024, 2) . ' KB';
                                } else {
                                    $fileSize = $bytes . ' Bytes';
                                }
                            }
                        @endphp
                        @if($fileSize)
                            <span class="text-muted ms-2" style="font-size:0.7rem;">(PDF, {{ $fileSize }})</span>
                        @endif
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Click to open the applicant's signed intent letter.</small>
                </div>
            </div>
        </div>

        <div class="signature-line">
            <span>Best regards,</span><br>
            <strong class="text-dark">CPSU Online Career Portal</strong> <i class="bi bi-check2-circle text-success small"></i>
        </div>
    </div>

    <div class="footer-card">
        <i class="bi bi-c-circle"></i> 2026 Central Philippines State University | Human Resource Information System
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="forwardConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2 pb-2">
                <div class="mb-2">
                    <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 55px; height: 55px;">
                        <i class="bi bi-send-check fs-3 text-success"></i>
                    </div>
                </div>
                <h5 class="fw-bold">Forward application?</h5>
                <p class="text-secondary small">Mark this application as forwarded to HR. This action can't be undone.</p>
                <p class="fw-semibold text-success mb-0 small" id="modalAppNumber">{{ $applications->app_number ?? 'APP-2026-000123' }}</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="finalConfirmForward">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function() {
        const forwardBtn = document.getElementById('forwardActionBtn');
        const confirmModalEl = document.getElementById('forwardConfirmModal');
        const modal = new bootstrap.Modal(confirmModalEl);
        const confirmBtn = document.getElementById('finalConfirmForward');
        
        const applicationId = forwardBtn.getAttribute('data-application-id');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const isChecked = {{ $applications->checked ?? 0 }};
        
        // If already forwarded, change button text to "Already Forwarded to HR"
        if (isChecked == 1) {
            forwardBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Already Forwarded to HR';
            forwardBtn.classList.add('disabled-forward');
            forwardBtn.disabled = true;
        }

        // Open modal on click
        forwardBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!forwardBtn.disabled) {
                modal.show();
            }
        });

        // Confirm forwarding
        confirmBtn.addEventListener('click', function() {
            if (forwardBtn.disabled) return;

            const originalBtnText = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-sm me-1" role="status"></span> Processing...';

            const url = "{{ route('markForwarded', ['appid' => $applications->id ?? 1]) }}";
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    application_id: applicationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Change button to "Already Forwarded to HR" only - no other text badges anywhere
                    forwardBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Already Forwarded to HR';
                    forwardBtn.classList.add('disabled-forward');
                    forwardBtn.disabled = true;
                    
                    console.log('Application forwarded successfully');
                } else {
                    alert('Error: ' + (data.message || 'Could not forward application'));
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalBtnText;
                    return;
                }
                
                modal.hide();
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalBtnText;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while forwarding the application. Please try again.');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalBtnText;
            });
        });
    })();
</script>
</body>
</html>