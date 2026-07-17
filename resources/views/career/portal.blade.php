<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Careers — LGU Mabinay</title>
    <link rel="shortcut icon" href="{{ asset('Uploads/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}">
    <style>
        :root {
            --green: #187744;
            --green-600: #136038;
            --green-050: #f0fdf4;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --bg: #f6f8f7;
            --amber: #b45309;
            --red: #b91c1c;
            --radius: 14px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--green); }

        /* ------------------------------------------------------------ header */
        .topbar {
            position: sticky; top: 0; z-index: 40;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--line);
        }
        .topbar__inner {
            max-width: 1080px; margin: 0 auto; padding: 10px 20px;
            display: flex; align-items: center; gap: 12px;
        }
        .topbar__seal { width: 42px; height: 42px; object-fit: contain; }
        .topbar__name { line-height: 1.15; }
        .topbar__name strong { display: block; font-size: .95rem; }
        .topbar__name small { color: var(--muted); font-size: .72rem; letter-spacing: .04em; text-transform: uppercase; }
        .topbar__actions { margin-left: auto; display: flex; gap: 8px; }

        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            border: 1px solid transparent; border-radius: 10px; cursor: pointer;
            font: 600 .84rem 'Inter', sans-serif; padding: 9px 16px;
            text-decoration: none; transition: background .15s, color .15s, border-color .15s;
            white-space: nowrap;
        }
        .btn--solid  { background: var(--green); color: #fff; }
        .btn--solid:hover { background: var(--green-600); }
        .btn--ghost  { background: #fff; color: var(--ink); border-color: var(--line); }
        .btn--ghost:hover { border-color: var(--green); color: var(--green); }
        .btn--lg { padding: 12px 22px; font-size: .92rem; border-radius: 12px; }
        .btn[disabled] { opacity: .55; cursor: not-allowed; }

        /* -------------------------------------------------------------- hero */
        .hero {
            background:
                radial-gradient(900px 380px at 85% -10%, rgba(24,119,68,.14), transparent 60%),
                radial-gradient(700px 300px at 8% 110%, rgba(24,119,68,.10), transparent 55%),
                #fff;
            border-bottom: 1px solid var(--line);
        }
        .hero__inner { max-width: 1080px; margin: 0 auto; padding: 54px 20px 44px; }
        .hero__badge {
            display: inline-flex; align-items: center; gap: 7px;
            background: var(--green-050); color: var(--green);
            border: 1px solid #bbe7cd; border-radius: 999px;
            font-size: .74rem; font-weight: 700; letter-spacing: .05em;
            padding: 5px 13px; text-transform: uppercase;
        }
        .hero h1 { font-size: clamp(1.7rem, 4vw, 2.5rem); font-weight: 800; letter-spacing: -.02em; margin: 14px 0 8px; }
        .hero h1 em { color: var(--green); font-style: normal; }
        .hero p  { color: var(--muted); max-width: 560px; }
        .hero__search { margin-top: 24px; display: flex; gap: 10px; max-width: 560px; }
        .hero__search input {
            flex: 1; border: 1px solid var(--line); border-radius: 12px;
            font: 500 .92rem 'Inter', sans-serif; padding: 12px 16px; outline: none;
            background: #fff;
        }
        .hero__search input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(24,119,68,.15); }

        /* -------------------------------------------------------------- jobs */
        .jobs { max-width: 1080px; margin: 0 auto; padding: 34px 20px 70px; }
        .jobs__head { display: flex; align-items: baseline; gap: 10px; margin-bottom: 18px; }
        .jobs__head h2 { font-size: 1.15rem; font-weight: 700; }
        .jobs__count { color: var(--muted); font-size: .85rem; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }

        .card {
            background: #fff; border: 1px solid var(--line); border-radius: var(--radius);
            padding: 20px; display: flex; flex-direction: column; gap: 10px;
            transition: box-shadow .15s, border-color .15s, transform .15s;
        }
        .card:hover { border-color: #bbe7cd; box-shadow: 0 10px 28px rgba(16,64,40,.09); transform: translateY(-2px); }
        .card__type {
            align-self: flex-start; background: var(--green-050); color: var(--green);
            border-radius: 7px; font-size: .7rem; font-weight: 700; letter-spacing: .05em;
            padding: 3px 9px; text-transform: uppercase;
        }
        .card__title { font-size: 1.02rem; font-weight: 700; letter-spacing: -.01em; }
        .card__meta { color: var(--muted); font-size: .8rem; display: grid; gap: 4px; }
        .card__meta i { width: 15px; color: var(--green); margin-right: 5px; }
        .card__salary { font-weight: 700; color: var(--ink); font-size: .95rem; }
        .card__foot { margin-top: auto; padding-top: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px dashed var(--line); }
        .card__due { font-size: .74rem; color: var(--muted); }
        .card__due.is-soon { color: var(--amber); font-weight: 600; }

        .empty {
            border: 1px dashed #cbd5d1; border-radius: var(--radius); background: #fff;
            padding: 60px 24px; text-align: center; color: var(--muted);
        }
        .empty i { font-size: 2rem; color: #9ca3af; margin-bottom: 10px; }

        /* ------------------------------------------------------------ footer */
        .foot { border-top: 1px solid var(--line); background: #fff; }
        .foot__inner { max-width: 1080px; margin: 0 auto; padding: 22px 20px; display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: .78rem; flex-wrap: wrap; }
        .foot__inner img { width: 26px; height: 26px; object-fit: contain; }
        .foot__inner a { margin-left: auto; }

        /* ------------------------------------------------------------- modal */
        .modal {
            position: fixed; inset: 0; z-index: 60; display: none;
            align-items: flex-start; justify-content: center;
            background: rgba(15,23,42,.55); padding: 4vh 14px;
            overflow-y: auto;
        }
        .modal.is-open { display: flex; }
        .sheet {
            background: #fff; border-radius: 16px; width: 100%; max-width: 680px;
            box-shadow: 0 30px 80px rgba(2,20,10,.35); overflow: hidden;
            margin-bottom: 6vh;
        }
        .sheet--wide { max-width: 760px; }
        .sheet__head {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 20px 24px 14px; border-bottom: 1px solid var(--line);
        }
        .sheet__head h3 { font-size: 1.08rem; font-weight: 700; letter-spacing: -.01em; }
        .sheet__head p  { color: var(--muted); font-size: .8rem; margin-top: 2px; }
        .sheet__x {
            margin-left: auto; border: none; background: #f3f4f6; color: var(--muted);
            border-radius: 9px; width: 32px; height: 32px; cursor: pointer; font-size: .9rem; flex: none;
        }
        .sheet__x:hover { background: #e5e7eb; color: var(--ink); }
        .sheet__body { padding: 20px 24px 26px; }

        /* job detail rows */
        .spec { display: grid; gap: 0; }
        .spec__row { display: grid; grid-template-columns: 160px 1fr; gap: 14px; padding: 10px 0; border-bottom: 1px dashed var(--line); }
        .spec__row:last-child { border-bottom: none; }
        .spec__key { color: var(--muted); font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; padding-top: 2px; }
        .spec__val { font-size: .9rem; white-space: pre-line; }
        @media (max-width: 560px) { .spec__row { grid-template-columns: 1fr; gap: 2px; } }

        /* form */
        .f-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 14px; }
        .f-grid .f--full { grid-column: 1 / -1; }
        @media (max-width: 560px) { .f-grid { grid-template-columns: 1fr; } }
        .f label { display: block; font-size: .74rem; font-weight: 700; color: #374151; margin-bottom: 5px; letter-spacing: .02em; }
        .f label small { color: var(--muted); font-weight: 500; }
        .f input, .f select, .f textarea {
            width: 100%; border: 1px solid var(--line); border-radius: 10px;
            font: 500 .88rem 'Inter', sans-serif; padding: 10px 12px; outline: none; background: #fff;
        }
        .f input:focus, .f select:focus, .f textarea:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(24,119,68,.13); }
        .f-section { margin: 22px 0 10px; display: flex; align-items: center; gap: 10px; }
        .f-section h4 { font-size: .82rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--green); }
        .f-section::after { content: ''; flex: 1; height: 1px; background: var(--line); }
        .f-section .mini {
            border: 1px solid var(--line); background: #fff; color: var(--green);
            border-radius: 8px; font: 700 .72rem 'Inter', sans-serif; padding: 4px 10px; cursor: pointer;
        }
        .f-section .mini:hover { border-color: var(--green); }
        .row-line { display: grid; grid-template-columns: 1fr 150px 92px 34px; gap: 8px; margin-bottom: 8px; }
        .row-line--elig { grid-template-columns: 1fr 34px; }
        .row-line .rm {
            border: none; background: #fef2f2; color: var(--red); border-radius: 8px; cursor: pointer;
        }
        .row-line .rm:hover { background: #fee2e2; }

        .file-tile { position: relative; border: 1.5px dashed #cfd8d3; border-radius: 10px; padding: 10px 12px; background: #fbfdfc; }
        .file-tile input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .file-tile .ft-name { font-size: .78rem; color: var(--muted); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-tile.has-file { border-color: var(--green); background: var(--green-050); }
        .file-tile.has-file .ft-name { color: var(--green); font-weight: 600; }

        .alert {
            border-radius: 10px; padding: 11px 14px; font-size: .84rem; margin-bottom: 14px; display: none;
        }
        .alert--err { background: #fef2f2; color: var(--red); border: 1px solid #fecaca; }
        .alert--ok  { background: var(--green-050); color: var(--green); border: 1px solid #bbe7cd; }

        /* success + tracking */
        .done { text-align: center; padding: 18px 6px 8px; }
        .done i { font-size: 2.4rem; color: var(--green); }
        .done h4 { margin: 12px 0 4px; font-size: 1.05rem; }
        .done p { color: var(--muted); font-size: .86rem; }
        .done .appno {
            margin: 16px auto 6px; display: inline-block; background: var(--green-050);
            border: 1px solid #bbe7cd; color: var(--green); border-radius: 12px;
            font: 800 1.3rem 'Inter', sans-serif; letter-spacing: .04em; padding: 12px 26px;
        }

        .track-form { display: flex; gap: 10px; margin-bottom: 6px; }
        .track-form input {
            flex: 1; border: 1px solid var(--line); border-radius: 10px;
            font: 600 .95rem 'Inter', sans-serif; padding: 11px 14px; outline: none;
            text-transform: uppercase;
        }
        .track-form input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(24,119,68,.13); }

        .t-result { margin-top: 18px; display: none; }
        .t-card { border: 1px solid var(--line); border-radius: 12px; padding: 16px 18px; margin-bottom: 14px; }
        .t-card h4 { font-size: .98rem; }
        .t-card .t-sub { color: var(--muted); font-size: .8rem; margin-top: 2px; }
        .t-status {
            display: inline-block; margin-top: 10px; border-radius: 999px; padding: 5px 14px;
            font-size: .78rem; font-weight: 700;
        }
        .t-status--ok   { background: var(--green-050); color: var(--green); border: 1px solid #bbe7cd; }
        .t-status--warn { background: #fffbeb; color: var(--amber); border: 1px solid #fde68a; }
        .t-status--bad  { background: #fef2f2; color: var(--red); border: 1px solid #fecaca; }
        .t-note { background: #f9fafb; border: 1px solid var(--line); border-radius: 10px; padding: 11px 14px; font-size: .82rem; margin-top: 10px; }
        .t-note i { color: var(--green); margin-right: 6px; }
    </style>
</head>
<body>

    <header class="topbar">
        <div class="topbar__inner">
            <img class="topbar__seal" src="{{ asset('Uploads/logo.png') }}" alt="LGU Mabinay Official Seal">
            <div class="topbar__name">
                <strong>Municipality of Mabinay</strong>
                <small>Human Resource Information System</small>
            </div>
            <div class="topbar__actions">
                <button type="button" class="btn btn--ghost" data-open="#trackModal"><i class="fas fa-magnifying-glass-location"></i> Track application</button>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="hero__inner">
            <span class="hero__badge"><i class="fas fa-briefcase"></i> Careers Portal</span>
            <h1>Serve the people of <em>Mabinay</em>.</h1>
            <p>Browse current job openings at the Municipal Government, submit your application online, and track its progress — no account needed.</p>
            <div class="hero__search">
                <input type="search" id="jobSearch" placeholder="Search positions — e.g. nurse, engineer, clerk…" autocomplete="off">
            </div>
        </div>
    </section>

    <main class="jobs">
        <div class="jobs__head">
            <h2>Open positions</h2>
            <span class="jobs__count" id="jobCount">{{ $jobs->count() }} {{ Str::plural('opening', $jobs->count()) }}</span>
        </div>

        @if($jobs->isEmpty())
            <div class="empty">
                <i class="fas fa-folder-open"></i>
                <p><strong>No openings right now.</strong></p>
                <p>Please check back — new positions are posted here as soon as they open.</p>
            </div>
        @else
            <div class="grid" id="jobGrid">
                @foreach($jobs as $job)
                    @php
                        $daysLeft = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($job->expiration_at)->startOfDay(), false);
                        $jobPayload = [
                            'id'          => $job->id,
                            'type'        => $job->type,
                            'title'       => $job->title,
                            'item'        => $job->plantilla_item_no,
                            'salary'      => number_format((float) $job->salary, 2),
                            'assignment'  => $job->assignment,
                            'education'   => $job->education,
                            'eligibility' => $job->eligibility,
                            'training'    => $job->training,
                            'experience'  => $job->experience,
                            'competency'  => $job->competency,
                            'deadline'    => \Carbon\Carbon::parse($job->expiration_at)->format('F d, Y'),
                        ];
                    @endphp
                    <article class="card" data-haystack="{{ Str::lower($job->title . ' ' . $job->type . ' ' . $job->assignment . ' ' . $job->plantilla_item_no) }}">
                        <span class="card__type">{{ $job->type }}</span>
                        <h3 class="card__title">{{ $job->title }}</h3>
                        <div class="card__meta">
                            <span class="card__salary"><i class="fas fa-peso-sign"></i>{{ number_format((float) $job->salary, 2) }} <small style="color:var(--muted); font-weight:500;">/ month</small></span>
                            @if($job->assignment)
                                <span><i class="fas fa-location-dot"></i>{{ $job->assignment }}</span>
                            @endif
                            @if($job->plantilla_item_no)
                                <span><i class="fas fa-hashtag"></i>Item No. {{ $job->plantilla_item_no }}</span>
                            @endif
                        </div>
                        <div class="card__foot">
                            <span class="card__due {{ $daysLeft <= 7 ? 'is-soon' : '' }}">
                                <i class="far fa-clock"></i>
                                Apply before {{ \Carbon\Carbon::parse($job->expiration_at)->format('M d, Y') }}
                                @if($daysLeft <= 7) · {{ $daysLeft <= 0 ? 'last day' : $daysLeft . ' day' . ($daysLeft == 1 ? '' : 's') . ' left' }} @endif
                            </span>
                            <button type="button" class="btn btn--solid view-job" data-job='@json($jobPayload)'>View details</button>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="empty" id="noResults" style="display:none;">
                <i class="fas fa-magnifying-glass"></i>
                <p><strong>Nothing matches that search.</strong></p>
            </div>
        @endif
    </main>

    <footer class="foot">
        <div class="foot__inner">
            <img src="{{ asset('Uploads/logo.png') }}" alt="Seal">
            <span>© {{ date('Y') }} Municipality of Mabinay · Human Resource Management Office</span>
        </div>
    </footer>

    {{-- ------------------------------------------------ job detail modal --}}
    <div class="modal" id="jobModal">
        <div class="sheet">
            <div class="sheet__head">
                <div>
                    <h3 id="jm-title">Position</h3>
                    <p id="jm-sub">—</p>
                </div>
                <button type="button" class="sheet__x" data-close><i class="fas fa-xmark"></i></button>
            </div>
            <div class="sheet__body">
                <div class="spec" id="jm-spec"></div>
                <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn--ghost" data-close>Close</button>
                    <button type="button" class="btn btn--solid btn--lg" id="jm-apply"><i class="fas fa-paper-plane"></i> Apply for this position</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------ application modal --}}
    <div class="modal" id="applyModal">
        <div class="sheet sheet--wide">
            <div class="sheet__head">
                <div>
                    <h3>Application — <span id="ap-title" style="color:var(--green);">Position</span></h3>
                    <p>All documents must be PDF files, 20&nbsp;MB max each. Fields marked * are required.</p>
                </div>
                <button type="button" class="sheet__x" data-close><i class="fas fa-xmark"></i></button>
            </div>
            <div class="sheet__body">
                <div class="alert alert--err" id="ap-error"></div>

                <form id="applyForm" novalidate>
                    <input type="hidden" name="jid" id="ap-jid">

                    <div class="f-section"><h4>Personal information</h4></div>
                    <div class="f-grid">
                        <div class="f"><label>First name *</label><input name="first_name" required></div>
                        <div class="f"><label>Last name *</label><input name="last_name" required></div>
                        <div class="f"><label>Middle name <small>(optional)</small></label><input name="middle_name"></div>
                        <div class="f"><label>Age *</label><input name="age" type="number" min="18" max="65" required></div>
                        <div class="f"><label>Sex *</label>
                            <select name="sex" required>
                                <option value="" disabled selected>Select…</option>
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </div>
                        <div class="f"><label>Mobile number *</label><input name="mobile" type="tel" placeholder="09XX XXX XXXX" required></div>
                        <div class="f"><label>Email address *</label><input name="email" type="email" required></div>
                        <div class="f f--full"><label>Complete address *</label><input name="address" required></div>
                    </div>

                    <div class="f-section"><h4>Education *</h4><button type="button" class="mini" id="addEdu"><i class="fas fa-plus"></i> Add</button></div>
                    <div id="eduRows"></div>

                    <div class="f-section"><h4>Eligibility <small style="text-transform:none; letter-spacing:0; color:var(--muted); font-weight:500;">(if any)</small></h4><button type="button" class="mini" id="addElig"><i class="fas fa-plus"></i> Add</button></div>
                    <div id="eligRows"></div>

                    <div class="f-section"><h4>Documents</h4></div>
                    <div class="f-grid">
                        <div class="f"><label>Personal Data Sheet (PDS) *</label>
                            <div class="file-tile"><input type="file" name="pds" accept="application/pdf" required><span class="ft-name">Choose PDF…</span></div>
                        </div>
                        <div class="f"><label>Work Experience Sheet *</label>
                            <div class="file-tile"><input type="file" name="wes" accept="application/pdf" required><span class="ft-name">Choose PDF…</span></div>
                        </div>
                        <div class="f"><label>Letter of Intent *</label>
                            <div class="file-tile"><input type="file" name="intent" accept="application/pdf" required><span class="ft-name">Choose PDF…</span></div>
                        </div>
                        <div class="f"><label>Resume / CV *</label>
                            <div class="file-tile"><input type="file" name="resume" accept="application/pdf" required><span class="ft-name">Choose PDF…</span></div>
                        </div>
                        <div class="f"><label>Transcript of Records *</label>
                            <div class="file-tile"><input type="file" name="tor" accept="application/pdf" required><span class="ft-name">Choose PDF…</span></div>
                        </div>
                        <div class="f"><label>Certificate of Employment <small>(optional)</small></label>
                            <div class="file-tile"><input type="file" name="coe" accept="application/pdf"><span class="ft-name">Choose PDF…</span></div>
                        </div>
                        <div class="f f--full"><label>Training certificates <small>(optional, multiple allowed)</small></label>
                            <div class="file-tile"><input type="file" name="cert_training[]" accept="application/pdf" multiple><span class="ft-name">Choose PDF file(s)…</span></div>
                        </div>
                    </div>

                    <div style="margin-top:24px; display:flex; gap:10px; justify-content:flex-end; align-items:center;">
                        <button type="button" class="btn btn--ghost" data-close>Cancel</button>
                        <button type="submit" class="btn btn--solid btn--lg" id="ap-submit"><i class="fas fa-paper-plane"></i> Submit application</button>
                    </div>
                </form>

                <div class="done" id="ap-done" style="display:none;">
                    <i class="fas fa-circle-check"></i>
                    <h4>Application submitted!</h4>
                    <p>Save your application number — you will need it to track your status.<br>A confirmation was also sent to your email.</p>
                    <div class="appno" id="ap-appno">APP-0000-0000X</div>
                    <div style="margin-top:18px;">
                        <button type="button" class="btn btn--solid" data-close>Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- --------------------------------------------------- tracking modal --}}
    <div class="modal" id="trackModal">
        <div class="sheet">
            <div class="sheet__head">
                <div>
                    <h3>Track your application</h3>
                    <p>Enter the application number from your confirmation (e.g. APP-{{ date('Y') }}-1234A).</p>
                </div>
                <button type="button" class="sheet__x" data-close><i class="fas fa-xmark"></i></button>
            </div>
            <div class="sheet__body">
                <div class="alert alert--err" id="tr-error"></div>
                <form class="track-form" id="trackForm">
                    <input id="tr-input" placeholder="APP-{{ date('Y') }}-0000A" autocomplete="off" required>
                    <button type="submit" class="btn btn--solid" id="tr-btn"><i class="fas fa-magnifying-glass"></i> Track</button>
                </form>

                <div class="t-result" id="tr-result">
                    <div class="t-card">
                        <h4 id="tr-name">—</h4>
                        <div class="t-sub" id="tr-pos">—</div>
                        <div class="t-sub" id="tr-date">—</div>
                        <span class="t-status" id="tr-status">—</span>
                        <div class="t-note" id="tr-note" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
(function () {
    'use strict';

    var API = {
        store:  "{{ url('/api/application/store') }}",
        status: "{{ url('/api/application/status') }}",
    };

    // ------------------------------------------------------------- search
    var search = document.getElementById('jobSearch');
    var grid   = document.getElementById('jobGrid');
    var count  = document.getElementById('jobCount');
    var none   = document.getElementById('noResults');

    if (search && grid) {
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            var shown = 0;

            grid.querySelectorAll('.card').forEach(function (card) {
                var hit = !q || card.dataset.haystack.indexOf(q) !== -1;
                card.style.display = hit ? '' : 'none';
                if (hit) shown++;
            });

            count.textContent = shown + (shown === 1 ? ' opening' : ' openings');
            if (none) none.style.display = shown ? 'none' : '';
        });
    }

    // ------------------------------------------------------------- modals
    function openModal(sel)  { document.querySelector(sel).classList.add('is-open'); document.body.style.overflow = 'hidden'; }
    function closeModal(el)  { el.classList.remove('is-open'); document.body.style.overflow = ''; }

    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-open]');
        if (opener) return openModal(opener.dataset.open);

        if (e.target.closest('[data-close]')) {
            var m = e.target.closest('.modal');
            if (m) closeModal(m);
            return;
        }

        // click on the dim backdrop
        if (e.target.classList && e.target.classList.contains('modal')) closeModal(e.target);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.modal.is-open').forEach(closeModal);
    });

    // -------------------------------------------------------- job details
    var current = null;

    document.querySelectorAll('.view-job').forEach(function (btn) {
        btn.addEventListener('click', function () {
            current = JSON.parse(btn.dataset.job);

            document.getElementById('jm-title').textContent = current.title;
            document.getElementById('jm-sub').textContent =
                current.type + (current.item ? ' · Item No. ' + current.item : '') + ' · closes ' + current.deadline;

            var rows = [
                ['Monthly salary', '₱ ' + current.salary],
                ['Place of assignment', current.assignment],
                ['Education', current.education],
                ['Eligibility', current.eligibility],
                ['Training', current.training],
                ['Experience', current.experience],
                ['Competency', current.competency],
            ];

            document.getElementById('jm-spec').innerHTML = rows
                .filter(function (r) { return r[1]; })
                .map(function (r) {
                    return '<div class="spec__row"><div class="spec__key">' + r[0] +
                           '</div><div class="spec__val"></div></div>';
                }).join('');

            // Set values as text, never HTML — job fields are typed by HR but
            // this page is public and takes no chances.
            var vals = rows.filter(function (r) { return r[1]; });
            document.querySelectorAll('#jm-spec .spec__val').forEach(function (el, i) {
                el.textContent = vals[i][1];
            });

            openModal('#jobModal');
        });
    });

    document.getElementById('jm-apply').addEventListener('click', function () {
        if (!current) return;
        closeModal(document.getElementById('jobModal'));
        startApplication(current);
    });

    // -------------------------------------------------- application form
    var eduRows  = document.getElementById('eduRows');
    var eligRows = document.getElementById('eligRows');

    function eduRow() {
        var div = document.createElement('div');
        div.className = 'row-line';
        div.innerHTML =
            '<div class="f"><input name="education[]" placeholder="School / degree — e.g. BS Civil Engineering, XYZ University" required></div>' +
            '<div class="f"><select name="elevel[]" required>' +
                '<option value="" disabled selected>Level…</option>' +
                '<option>Elementary</option><option>Secondary</option><option>Vocational</option>' +
                '<option>College</option><option>Graduate Studies</option>' +
            '</select></div>' +
            '<div class="f"><input name="eyear[]" placeholder="Year" maxlength="9" required></div>' +
            '<button type="button" class="rm" title="Remove"><i class="fas fa-xmark"></i></button>';
        div.querySelector('.rm').addEventListener('click', function () {
            if (eduRows.children.length > 1) div.remove();
        });
        return div;
    }

    function eligRow() {
        var div = document.createElement('div');
        div.className = 'row-line row-line--elig';
        div.innerHTML =
            '<div class="f"><input name="eligibility[]" placeholder="e.g. CSC Professional (2nd Level), RA 1080 — Licensure"></div>' +
            '<button type="button" class="rm" title="Remove"><i class="fas fa-xmark"></i></button>';
        div.querySelector('.rm').addEventListener('click', function () { div.remove(); });
        return div;
    }

    document.getElementById('addEdu').addEventListener('click', function () { eduRows.appendChild(eduRow()); });
    document.getElementById('addElig').addEventListener('click', function () { eligRows.appendChild(eligRow()); });

    // file tiles show the chosen name
    document.querySelectorAll('.file-tile input[type=file]').forEach(function (input) {
        input.addEventListener('change', function () {
            var tile = input.closest('.file-tile');
            var name = tile.querySelector('.ft-name');
            if (input.files.length === 1)      name.textContent = input.files[0].name;
            else if (input.files.length > 1)   name.textContent = input.files.length + ' files selected';
            else                               name.textContent = 'Choose PDF…';
            tile.classList.toggle('has-file', input.files.length > 0);
        });
    });

    var form    = document.getElementById('applyForm');
    var apError = document.getElementById('ap-error');
    var apDone  = document.getElementById('ap-done');
    var submit  = document.getElementById('ap-submit');

    function startApplication(job) {
        form.reset();
        form.style.display = '';
        apDone.style.display = 'none';
        apError.style.display = 'none';

        document.querySelectorAll('.file-tile').forEach(function (t) {
            t.classList.remove('has-file');
            t.querySelector('.ft-name').textContent = 'Choose PDF…';
        });

        eduRows.innerHTML = '';
        eduRows.appendChild(eduRow());
        eligRows.innerHTML = '';

        document.getElementById('ap-jid').value  = job.id;
        document.getElementById('ap-title').textContent = job.title;

        openModal('#applyModal');
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        apError.style.display = 'none';

        if (!form.reportValidity()) return;

        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Submitting…';

        try {
            var response = await fetch(API.store, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(form),
            });

            var body = await response.json().catch(function () { return {}; });

            if (response.status === 201) {
                document.getElementById('ap-appno').textContent = body.data && body.data.app_number ? body.data.app_number : '—';
                form.style.display = 'none';
                apDone.style.display = '';
                document.getElementById('applyModal').scrollTop = 0;
                return;
            }

            var message = body.message || 'Something went wrong. Please try again.';

            if (response.status === 422 && body.errors) {
                message = Object.keys(body.errors).map(function (k) { return body.errors[k][0]; }).join(' ');
            }

            apError.textContent = message;
            apError.style.display = 'block';
            apError.scrollIntoView({ block: 'center', behavior: 'smooth' });
        } catch (err) {
            apError.textContent = 'Could not reach the server. Check your connection and try again.';
            apError.style.display = 'block';
        } finally {
            submit.disabled = false;
            submit.innerHTML = '<i class="fas fa-paper-plane"></i> Submit application';
        }
    });

    // ------------------------------------------------------------ tracking
    var STATUS = {
        0: { label: 'Application Submitted',                        tone: 'ok'   },
        1: { label: 'Under Review',                                 tone: 'ok'   },
        2: { label: 'Qualified — For Interview',                    tone: 'ok'   },
        3: { label: 'Disqualified',                                 tone: 'bad'  },
        4: { label: 'Qualified, not selected',                      tone: 'warn' },
        5: { label: 'Top 5 — Psychological / Pre-Employment Test',  tone: 'ok'   },
        6: { label: 'Not Hired',                                    tone: 'bad'  },
        7: { label: 'Hired — Congratulations!',                     tone: 'ok'   },
    };

    var trackForm = document.getElementById('trackForm');
    var trError   = document.getElementById('tr-error');
    var trResult  = document.getElementById('tr-result');
    var trBtn     = document.getElementById('tr-btn');

    trackForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        var number = document.getElementById('tr-input').value.trim().toUpperCase();
        if (!number) return;

        trError.style.display = 'none';
        trResult.style.display = 'none';
        trBtn.disabled = true;

        try {
            var response = await fetch(API.status + '/' + encodeURIComponent(number), {
                headers: { 'Accept': 'application/json' },
            });

            var body = await response.json().catch(function () { return {}; });

            if (!response.ok || !body.data) {
                trError.textContent = 'No application was found with that number. Double-check it and try again.';
                trError.style.display = 'block';
                return;
            }

            var app  = body.data;
            var info = STATUS[app.status] || STATUS[0];

            document.getElementById('tr-name').textContent = (app.first_name + ' ' + app.last_name).trim();
            document.getElementById('tr-pos').textContent  = 'Position: ' + (app.position || '—');
            document.getElementById('tr-date').textContent = 'Applied: ' + new Date(app.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });

            var badge = document.getElementById('tr-status');
            badge.textContent = info.label;
            badge.className = 't-status t-status--' + info.tone;

            var note = document.getElementById('tr-note');
            note.style.display = 'none';

            if (Number(app.status) === 2 && app.interview_datetime) {
                note.innerHTML = '<i class="fas fa-calendar-check"></i>';
                note.appendChild(document.createTextNode(
                    'Interview: ' + new Date(app.interview_datetime).toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' })
                    + (app.venue ? ' · ' + app.venue : '')));
                note.style.display = '';
            } else if (Number(app.status) === 3 && app.dq_reason) {
                note.innerHTML = '<i class="fas fa-circle-info"></i>';
                note.appendChild(document.createTextNode('Reason: ' + app.dq_reason));
                note.style.display = '';
            }

            trResult.style.display = 'block';
        } catch (err) {
            trError.textContent = 'Could not reach the server. Check your connection and try again.';
            trError.style.display = 'block';
        } finally {
            trBtn.disabled = false;
        }
    });
})();
</script>
</body>
</html>
