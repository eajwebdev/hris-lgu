{{--
    Side panel of the Personal Data Sheet on layouts/app: whose record this
    is, and the pages of the sheet with which of them are filled in.

    The replacement for emp/submenu-side, which the PDS pages still on
    layouts/master keep using. The two draw the same entries, from
    pds_sections() in app/Helpers/helpers.php.

    The top card is laid out as the employee's ID: the band and orange rule
    are the QR card's (emp/partials/qr-card), which the button in the band
    opens. The photo is changed from here, on whichever page shows the panel.
--}}
@php
    $photo = $employee->profile && file_exists(public_path('Profile/Employee/' . $employee->profile))
        ? asset('Profile/Employee/' . $employee->profile)
        : asset('Profile/Employee/default.png');

    $proper = fn ($name) => ucwords(strtolower(str_replace('Ñ', 'ñ', (string) $name)));

    $hired = $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired) : null;
    $service = $hired?->diff(now());

    $sections = collect(pds_sections($employee, $guard, $columnstatus ?? null))->groupBy('group');
    $filled = $sections['form']->where('done', true)->count();

    $iconButton = 'grid size-9 cursor-pointer place-items-center rounded-lg transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $entry = 'group flex items-center gap-3 rounded-lg px-3 py-2 text-ink/70 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500 aria-[current=page]:bg-forest-100 aria-[current=page]:font-medium aria-[current=page]:text-forest-800';
    $entryIcon = 'grid w-5 shrink-0 place-items-center text-[13px] text-ink/40 group-aria-[current=page]:text-forest-700';
@endphp
<aside id="pdsSide" class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-line bg-surface" aria-label="Employee">
        <div class="relative flex h-20 items-start justify-between bg-forest-900 py-3 pr-3 pl-5 text-cream">
            <p class="flex items-center gap-2 pt-1 text-xs text-cream/75">
                <img src="{{ asset('Uploads/logo.png') }}" alt="" class="size-6 rounded-full bg-white p-px">
                Municipality of Mabinay
            </p>
            <button type="button" data-dialog-open="qrDialog" title="Attendance QR card" class="{{ $iconButton }} text-cream/80 hover:bg-cream/12 hover:text-cream">
                <i class="fas fa-qrcode"></i><span class="sr-only">Attendance QR card</span>
            </button>
            <div class="absolute inset-x-0 bottom-0 h-1 bg-sun-500" aria-hidden="true"></div>
        </div>

        <div class="px-5 pb-5">
            <div class="flex items-end justify-between gap-3">
                <div class="relative -mt-10 shrink-0">
                    <img src="{{ $photo }}" alt="" data-photo class="size-24 rounded-full bg-paper object-cover ring-4 ring-surface">
                    <button type="button" data-photo-change title="Change photo"
                            class="absolute right-0 bottom-0 grid size-8 cursor-pointer place-items-center rounded-full border border-line bg-surface text-xs text-ink/65 shadow-sm transition-colors hover:text-forest-700 focus-visible:outline-2 focus-visible:outline-sun-500 disabled:cursor-wait disabled:opacity-60">
                        <i class="fas fa-camera"></i><span class="sr-only">Change photo</span>
                    </button>
                    <input type="file" data-photo-input hidden accept="image/png,image/jpeg,image/gif,image/svg+xml">
                </div>

                @if($employee->stat_1 == 1)
                    <span class="mb-1 rounded-full bg-forest-100 px-3 py-1 text-xs font-medium text-forest-800">Active</span>
                @else
                    <span class="mb-1 rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300">Suspended</span>
                @endif
            </div>

            <h2 class="mt-3 font-display text-xl leading-tight font-semibold tracking-tight">{{ $proper($employee->fname) }} {{ $proper($employee->lname) }}</h2>
            <p class="mt-0.5 text-ink/60">{{ $employee->position ?: 'No position set' }}</p>

            <dl class="mt-4 divide-y divide-line border-t border-line">
                @foreach([
                    'Employee ID' => $employee->emp_ID,
                    'Item no.' => $employee->item_no,
                    'Service' => $service ? $service->y . ' ' . ($service->y == 1 ? 'year' : 'years') . ' ' . $service->m . ' ' . ($service->m == 1 ? 'month' : 'months') : null,
                ] as $fact => $answer)
                    <div class="flex items-baseline justify-between gap-4 py-2">
                        <dt class="text-ink/60">{{ $fact }}</dt>
                        <dd class="text-right font-medium tabular-nums {{ filled($answer) ? '' : 'font-normal text-ink/40' }}">{{ filled($answer) ? $answer : 'Not set' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- Open on a desktop, where it is the page's menu. On a phone it starts
         folded (the script below), or the form would be a screen and a half
         down. --}}
    <nav aria-label="Personal Data Sheet" class="rounded-2xl border border-line bg-surface">
        <details open data-pds-sections>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 pt-4 pb-3 lg:cursor-default [&::-webkit-details-marker]:hidden">
                <span>
                    <span class="block font-display text-lg font-semibold tracking-tight">Sections</span>
                    <span class="block text-xs text-ink/60">{{ $filled }} of {{ $sections['form']->count() }} filled in</span>
                </span>
                <span class="text-ink/45 lg:hidden"><i class="fas fa-angle-down"></i></span>
            </summary>

            <div class="mx-5 h-1.5 overflow-hidden rounded-full bg-line" aria-hidden="true">
                <div class="h-full rounded-full bg-forest-600" style="width: {{ round($filled / $sections['form']->count() * 100) }}%"></div>
            </div>

            <ul class="p-2 pt-3">
                @foreach($sections['form'] as $section)
                    <li>
                        <a href="{{ $section['url'] }}" class="{{ $entry }}" @if($section['active']) aria-current="page" @endif>
                            <span class="{{ $entryIcon }}"><i class="{{ $section['icon'] }}"></i></span>
                            <span class="min-w-0 flex-1 leading-snug">{{ $section['label'] }}</span>
                            @if($section['done'])
                                <span class="text-forest-600 dark:text-forest-500" title="Filled in"><i class="fas fa-circle-check"></i><span class="sr-only">Filled in</span></span>
                            @else
                                <span class="text-ink/25" title="Nothing entered yet"><i class="far fa-circle"></i><span class="sr-only">Nothing entered yet</span></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <p class="mx-5 border-t border-line pt-3 text-xs font-medium text-ink/50">Print</p>
            <ul class="p-2 pt-1">
                @foreach($sections['print'] as $section)
                    <li>
                        <a href="{{ $section['url'] }}" target="_blank" class="{{ $entry }}">
                            <span class="{{ $entryIcon }}"><i class="fas fa-file-pdf"></i></span>
                            <span class="min-w-0 flex-1 leading-snug">{{ $section['label'] }}</span>
                            <span class="text-[11px] text-ink/30" title="Opens in a new tab"><i class="fas fa-arrow-up-right-from-square"></i><span class="sr-only">Opens in a new tab</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <p class="mx-5 border-t border-line pt-3 text-xs font-medium text-ink/50">Identity</p>
            <ul class="p-2 pt-1">
                @foreach($sections['identity'] as $section)
                    <li>
                        <a href="{{ $section['url'] }}" class="{{ $entry }}" @if($section['active']) aria-current="page" @endif>
                            <span class="{{ $entryIcon }}"><i class="{{ $section['icon'] }}"></i></span>
                            <span class="min-w-0 flex-1 leading-snug">{{ $section['label'] }}</span>
                            @if($section['done'])
                                <span class="text-forest-600 dark:text-forest-500" title="Registered"><i class="fas fa-circle-check"></i><span class="sr-only">Registered</span></span>
                            @elseif($section['done'] === false)
                                <span class="text-ink/25" title="Not registered"><i class="far fa-circle"></i><span class="sr-only">Not registered</span></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </details>
    </nav>
</aside>

<dialog id="qrDialog" aria-labelledby="qrTitle"
        class="m-auto w-[min(24rem,calc(100vw-2rem))] rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60">
    <div class="flex items-start justify-between gap-4 px-6 pt-5">
        <div>
            <h2 class="font-display text-xl font-semibold tracking-tight" id="qrTitle">Attendance QR card</h2>
            <p class="mt-0.5 text-ink/60">Scanned to log attendance.</p>
        </div>
        <button type="button" data-dialog-close aria-label="Close" class="{{ $iconButton }} -mt-1 -mr-2 shrink-0 text-ink/50 hover:bg-paper hover:text-ink">
            <i class="fas fa-xmark"></i>
        </button>
    </div>

    <div class="px-6 py-5">
        @include('emp.partials.qr-card')
    </div>

    <div class="flex justify-end gap-2 px-6 pb-5">
        <button type="button" data-dialog-close class="h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Close</button>
        <button type="button" id="qrDownload" class="h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 disabled:cursor-wait disabled:opacity-60 dark:bg-forest-600 dark:hover:bg-forest-500">
            <i class="fas fa-download mr-1"></i> Download PNG
        </button>
    </div>
</dialog>

@push('scripts')
<script src="{{ asset('template/dist/js/qrcode.min.js') }}"></script>
<script src="{{ asset('template/dist/js/html2canvas.min.js') }}"></script>
<script>
(function () {
    var side = document.getElementById('pdsSide');
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var desktop = window.matchMedia('(min-width: 64rem)');

    /* ------------------------------------------------------------ sections */
    // Folded on a phone; on a desktop the heading is not a switch at all.
    var sections = side.querySelector('[data-pds-sections]');
    if (!desktop.matches) { sections.open = false; }

    sections.querySelector('summary').addEventListener('click', function (event) {
        if (desktop.matches) { event.preventDefault(); }
    });

    /* --------------------------------------------------------------- photo */
    // Sent as soon as it is chosen. The picture on the card changes once the
    // server has taken it, so what shows is always what is stored.
    var photo = side.querySelector('[data-photo]');
    var photoButton = side.querySelector('[data-photo-change]');
    var photoInput = side.querySelector('[data-photo-input]');

    photoButton.addEventListener('click', function () { photoInput.click(); });

    photoInput.addEventListener('change', function () {
        var file = photoInput.files[0];
        photoInput.value = '';
        if (!file) return;

        // The same ceiling EmployeeController::updateProfilePicture enforces.
        if (file.size > 2048 * 1024) {
            hrisToast('error', 'That photo is larger than 2 MB. Choose a smaller one.');
            return;
        }

        var body = new FormData();
        body.append('profileImage', file);
        photoButton.disabled = true;

        fetch("{{ route('updateProfilePicture', $employee->id) }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (data) {
                    if (!response.ok || !data.profile) { return Promise.reject(data); }
                    return data;
                });
            })
            .then(function (data) {
                photo.src = data.profile;
                hrisToast('success', 'The photo has been updated.');
            })
            .catch(function (data) {
                var reason = data && data.errors && data.errors.profileImage ? data.errors.profileImage[0] : 'Please try again.';
                hrisToast('error', 'The photo could not be changed. ' + reason);
            })
            .finally(function () { photoButton.disabled = false; });
    });

    /* ------------------------------------------------------------- QR card */
    // Drawn the first time the card is opened, in the seal's green so the
    // code matches the card around it.
    var qr = document.getElementById('qrcode');

    side.querySelector('[data-dialog-open="qrDialog"]').addEventListener('click', function () {
        if (qr.childElementCount) return;

        new QRCode(qr, {
            text: @json(shortEncrypt($employee->emp_ID)),
            width: 196,
            height: 196,
            colorDark: '#10502C',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    });

    var download = document.getElementById('qrDownload');

    download.addEventListener('click', function () {
        download.disabled = true;

        // html2canvas finds a font's baseline by measuring a one-pixel image
        // it adds to the end of the body. Tailwind's reset makes images
        // blocks, which sinks every line of text on the PNG; this keeps that
        // one image inline for as long as the card is being drawn.
        var probe = document.createElement('style');
        probe.textContent = 'body > div:last-child > img { display: inline-block; }';
        document.head.appendChild(probe);

        html2canvas(document.querySelector('.employee-card-content'), {
            backgroundColor: null,
            useCORS: true,
            scale: 3            // print-quality PNG rather than a screen-sized one
        })
            .then(function (canvas) {
                var link = document.createElement('a');
                link.download = @json($employee->emp_ID . '.png');
                link.href = canvas.toDataURL();
                link.click();
            })
            .catch(function () { hrisToast('error', 'The card could not be saved as an image.'); })
            .finally(function () {
                probe.remove();
                download.disabled = false;
            });
    });
})();
</script>
@endpush
