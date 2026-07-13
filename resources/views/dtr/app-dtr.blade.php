<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Head includes -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f5f6f8;
            font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem 1rem;
        }
        .form-container {
            width: 100%;
            max-width: 480px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .form-header {
            background: #04401F;
            color: white;
            padding: 1.25rem 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.25rem;
        }
        .form-body {
            padding: 1.25rem 1.25rem;
        }
        .employee-name {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-word;
        }
        /* Improved responsive inputs */
        .form-label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 1rem;
        }
        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.65rem 0.9rem;
            font-size: 1.05rem;
            line-height: 1.5;
            height: auto;
            min-height: 44px;
            transition: border-color 0.15s;
        }
        /* Remove Bootstrap 5 green validation check icon */
        .form-control.is-valid,
        .form-select.is-valid,
        .form-control:valid,
        .form-select:valid,
        .was-validated .form-control:valid,
        .was-validated .form-select:valid {
            background-image: none !important;
            box-shadow: none !important;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #cbd5e1;
            box-shadow: none;
            outline: none;
        }
        .overtime-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
            padding: 0.5rem 0;
        }
        .overtime-toggle .toggle-icon {
            font-size: 1.6rem;
            color: #cbd5e1;
            transition: color 0.2s, transform 0.15s;
        }
        .overtime-toggle.active .toggle-icon {
            color: #04401F;
        }
        .overtime-toggle:active .toggle-icon {
            transform: scale(0.9);
        }
        .overtime-toggle .toggle-label {
            font-size: 1rem;
            color: #64748b;
            font-weight: 400;
            transition: color 0.2s;
        }
        .overtime-toggle.active .toggle-label {
            color: #04401F;
            font-weight: 500;
        }
        .btn-primary-company {
            background-color: #04401F;
            border: none;
            padding: 0.65rem;
            font-size: 1.05rem;
            font-weight: 500;
            border-radius: 8px;
            min-height: 50px;
            transition: background 0.2s;
        }
        .btn-primary-company,
        .btn-primary-company:hover,
        .btn-primary-company:focus,
        .btn-primary-company:active {
            background-color: #04401F !important;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
            transform: none !important;
        }
        .btn-primary-company {
            -webkit-tap-highlight-color: transparent;
        }
        .pdf-section {
            padding: 1.25rem 1.25rem;
            border-top: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .pdf-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.15s;
            overflow: hidden;
        }
        .pdf-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .pdf-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            color: #1e293b;
            text-decoration: none;
            min-width: 0;
            overflow: hidden;
        }
        .pdf-card:active {
            background: #f8f9fa;
        }
        .pdf-icon {
            font-size: 2.4rem; /* Slightly reduced for better fit on narrow screens */
            color: #e53e3e;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .pdf-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0; /* Allow text truncation */
            overflow: hidden;
        }
        .pdf-info strong {
            color: #04401F;
            font-size: 0.95rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        .pdf-info small {
            color: #64748b;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        /* Flex wrapper to prevent overflow and enable ellipsis */
        .pdf-left-wrapper {
            display: flex;
            align-items: center;
            flex-grow: 1;
            min-width: 0; /* Critical: allows shrinking and ellipsis */
            overflow: hidden;
        }
        /* Extra mobile breathing room */
        @media (max-width: 576px) {
            .form-body,
            .pdf-section {
                padding: 1.25rem 1rem;
            }
            .form-control,
            .form-select {
                font-size: 1.05rem;
                padding: 0.6rem 0.85rem;
            }
            .btn-primary-company {
                font-size: 1.05rem;
                padding: 0.65rem;
            }
            .pdf-icon {
                font-size: 2.2rem;
            }
        }
        @media (max-width: 480px) {
            .form-container {
                max-width: 100%;
                border-radius: 0;
                min-height: 100vh;
            }
            .page-wrapper {
                padding: 0;
            }
            .pdf-section {
                padding: 1rem 0.75rem;
            }
            .pdf-link {
                padding: 0.85rem 0.75rem;
            }
            .pdf-icon {
                font-size: 1.8rem;
                margin-right: 0.75rem;
            }
            .pdf-info strong {
                font-size: 0.85rem;
            }
            .pdf-info small {
                font-size: 0.8rem;
            }
        }
        @media (max-width: 360px) {
            .pdf-section {
                padding: 0.75rem 0.5rem;
            }
            .pdf-link {
                padding: 0.75rem 0.5rem;
            }
            .pdf-icon {
                font-size: 1.5rem;
                margin-right: 0.5rem;
            }
        }
        /* Skeleton loading */
        .skeleton {
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.2s ease-in-out infinite;
            border-radius: 4px;
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .pdf-info {
            position: relative;
        }
        .skeleton-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }
        .skeleton-filename {
            height: 1rem;
            width: 75%;
            margin-bottom: 0.5rem;
        }
        .skeleton-subtitle {
            height: 0.75rem;
            width: 45%;
        }
        .pdf-card.loading .skeleton-wrapper {
            display: flex;
        }
        .pdf-card.loaded .skeleton-wrapper {
            display: none;
        }
        .pdf-card.loading .pdf-link > i {
            opacity: 0;
        }
        .pdf-card.loaded .pdf-link > i {
            opacity: 1;
            transition: opacity 0.2s;
        }
        .pdf-card.bg-light .skeleton-wrapper {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="form-container">
            <div class="form-body">
                @if(isset($empdata))
                    <div class="text-center mb-4">
                        <h5 class="mb-1 fw-regular employee-name">
                            {{ ucwords(strtolower($empdata->fname)) }}
                            {{ ucwords(strtolower($empdata->lname)) }}
                        </h5>
                        <!-- <small class="text-muted">Employee ID: {{ $empdata->emp_ID }}</small> -->
                    </div>
                    <form action="{{ route('app-dtr-search') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="emp_id" value="{{ $empdata->emp_ID }}">
                        <div class="mb-4">
                            <label class="form-label" for="period">
                                <i class="fas fa-calendar-alt me-2 text-muted"></i>Period
                            </label>
                            <select name="period" id="period" class="form-select" required>
                                <option value="1" {{ old('period', $period ?? 1) == 1 ? 'selected' : '' }}>1st Half (1–15)</option>
                                <option value="2" {{ old('period', $period ?? 1) == 2 ? 'selected' : '' }}>2nd Half (16–end)</option>
                                <option value="3" {{ old('period', $period ?? 1) == 3 ? 'selected' : '' }}>Whole Month</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="date">
                                <i class="fas fa-calendar me-2 text-muted"></i>Month
                            </label>
                            <input type="month" name="date" id="date" class="form-control"
                                value="{{ old('date', $date ?? now()->format('Y-m')) }}" required>
                            <div class="invalid-feedback">
                                This field is required.
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-3">
                            <div style="flex: 0 0 33%;">
                                <input type="hidden" name="overtime" id="overtime" value="{{ old('overtime', $overtime ?? false) ? '1' : '0' }}">
                                <div class="overtime-toggle {{ old('overtime', $overtime ?? false) ? 'active' : '' }}" id="overtimeToggle">
                                    <i class="fas fa-clock toggle-icon"></i>
                                    <span class="toggle-label">Overtime</span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary-company text-light" style="flex: 1;">
                                <i class="fas fa-file-pdf me-2"></i> Generate
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-info text-center mb-0">
                        Please select an employee first.
                    </div>
                @endif
            </div>
            @if(isset($dtrFilename))
                <div class="pdf-section">
                    <div class="d-grid gap-3">
                        <!-- Summary DTR -->
                        <div class="pdf-card loading" id="pdfCard1">
                            <a href="{{ route('app-dtr-pdf', [
                                'empid'    => $empdata->emp_ID,
                                'period'   => $period,
                                'date'     => $date,
                                'overtime' => $overtime ? 1 : 0,
                                'filename' => $dtrFilename
                            ]) }}" class="pdf-link text-decoration-none">
                                <div class="pdf-left-wrapper">
                                    <div class="pdf-info">
                                        <div class="skeleton-wrapper">
                                            <div class="skeleton skeleton-filename"></div>
                                            <div class="skeleton skeleton-subtitle"></div>
                                        </div>
                                        <strong class="filename-ellipsis" data-fullname="{{ $dtrFilename }}">{{ $dtrFilename }}</strong>
                                        <small>Daily Time Record</small>
                                    </div>
                                </div>
                                <i class="fas fa-circle-down text-success fs-4 ms-2"></i>
                            </a>
                        </div>
                        <!-- Detailed Logs (disabled until implemented) -->
                        <!-- <div class="pdf-card bg-light opacity-75 loading" id="pdfCard2">
                            <div class="pdf-link text-muted">
                                <div class="pdf-left-wrapper">
                                    <div class="pdf-info">
                                        <div class="skeleton-wrapper">
                                            <div class="skeleton skeleton-filename"></div>
                                            <div class="skeleton skeleton-subtitle"></div>
                                        </div>
                                        {{-- <strong class="filename-ellipsis" data-fullname="{{ $dtrLogsFilename }}">{{ $dtrLogsFilename }}</strong> --}}
                                        <small>Logs – Not available yet</small>
                                    </div>
                                </div>
                                <i class="fas fa-clock text-muted fs-4 ms-2"></i>
                            </div>
                        </div> -->
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Toastr -->
    <script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
    <script>
        function middleEllipsis(str, maxLen) {
            if (str.length <= maxLen) return str;
            const ext = str.lastIndexOf('.') > -1 ? str.slice(str.lastIndexOf('.')) : '';
            const name = str.slice(0, str.length - ext.length);
            const charsToShow = maxLen - ext.length - 3; // 3 for '...'
            if (charsToShow < 4) return str.slice(0, maxLen - 3) + '...';
            const frontChars = Math.ceil(charsToShow / 2);
            const backChars = Math.floor(charsToShow / 2);
            return name.slice(0, frontChars) + '...' + name.slice(-backChars) + ext;
        }
        function applyEllipsis() {
            const width = window.innerWidth;
            let maxLen = 48;
            if (width <= 360) maxLen = 30;
            else if (width <= 480) maxLen = 36;
            else if (width <= 576) maxLen = 42;
            document.querySelectorAll('.filename-ellipsis').forEach(el => {
                const fullName = el.dataset.fullname;
                el.textContent = middleEllipsis(fullName, maxLen);
            });
        }
        applyEllipsis();
        window.addEventListener('resize', applyEllipsis);
        // Show skeleton briefly then reveal content
        setTimeout(function() {
            document.querySelectorAll('.pdf-card.loading').forEach(card => {
                card.classList.remove('loading');
                card.classList.add('loaded');
            });
        }, 500);
        // Overtime toggle
        const overtimeToggle = document.getElementById('overtimeToggle');
        const overtimeInput = document.getElementById('overtime');
        if (overtimeToggle && overtimeInput) {
            overtimeToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                overtimeInput.value = this.classList.contains('active') ? '1' : '0';
            });
        }
    </script>
    @if(isset($dtrFilename))
        <script>
            toastr.options = {
                "progressBar": false,
                "positionClass": "toast-bottom-center",
                "timeOut": "2000",
            };
            toastr.success("Generated successfully!");
        </script>
    @endif
    <script>
    (function () {
        'use strict';
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>