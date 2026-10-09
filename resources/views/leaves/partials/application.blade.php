{{--
    One leave application on its way through the four signatures: the
    employee, the HRMO, the immediate supervisor (or the office's OIC), then
    the Mayor or the Vice Mayor. Each stop shows who signs there, when they
    did, and whatever the person looking at the page may do at it.

      $entry   ['leave' => the application,
                'own'   => true in the list of the employee whose page this is,
                           false in the list an office head, OIC or approving
                           official sees of other people's applications]

    From the page: $guard, $setting, $oic, $leaveTypes, $leavePurposes.
    The buttons only describe what to send; leaves/partials/actions asks,
    sends and reloads.
--}}
@php
    $leave = $entry['leave'];
    $own = $entry['own'];

    $isStaff = $guard == 'web';
    $me = auth()->guard($guard)->user();
    $hasEsign = filled($me->esign ?? null);
    // HR approves at the HRMO stop only with the leave approval right, the
    // eighth of the user's access flags.
    $mayApproveAsHr = $isStaff && (explode(',', (string) $me->access)[7] ?? 0) == 1;
    $isApprover = !$isStaff && $setting->isApprovingOfficial($me->id);
    // In the list of other people's applications, the OIC of the office
    // signs in the supervisor's place.
    $oicSigns = !$own && filled($oic->oic_id ?? null);

    // "Santos, Maria Jr. C." from the four name columns under a prefix.
    $named = fn ($row, string $who) => trim(preg_replace('/\s+/', ' ',
        ($row->{$who . 'lname'} ?? '') . ', ' . ($row->{$who . 'fname'} ?? '') . ' ' . ($row->{$who . 'suffix'} ?? '') . ' '
        . (filled($row->{$who . 'mname'} ?? null) ? strtoupper(substr($row->{$who . 'mname'}, 0, 1)) . '.' : '')
    ), ' ,');
    $stamp = fn ($when) => filled($when) ? \Carbon\Carbon::parse($when)->format('M j, Y g:i A') : null;

    $type = $leaveTypes[$leave->leave_type] ?? 'Unknown';
    $dates = array_map('trim', explode(' to ', (string) $leave->date_range));
    try {
        $from = \Carbon\Carbon::parse($dates[0]);
        $to = isset($dates[1]) ? \Carbon\Carbon::parse($dates[1]) : null;
        $inclusive = $to && !$to->isSameDay($from)
            ? $from->format($from->year == $to->year ? 'M j' : 'M j, Y') . ' to ' . $to->format('M j, Y')
            : $from->format('M j, Y');
        // Wellness leave filed after it began was taken as an emergency.
        if ($leave->leave_type == 15 && $leave->date_filing && $from->lt(\Carbon\Carbon::parse($leave->date_filing))) {
            $type .= ' (Emergency)';
        }
    } catch (\Throwable $e) {
        $inclusive = $leave->date_range;
    }

    // HR first takes the holidays out of the days and says how many are
    // without pay (emp_esign 0 to 1); until then there is no split to show.
    $reviewed = $leave->emp_esign != 0;
    $applied = $reviewed ? $leave->days + $leave->holiday : $leave->days;
    $shown = fn ($number) => rtrim(rtrim(number_format((float) $number, 2, '.', ''), '0'), '.');

    $refusedAt = in_array((int) $leave->remarks_stat, [1, 2, 3]) ? (int) $leave->remarks_stat : null;
    // Whose turn it is: 0 the employee, 1 the HRMO, 2 the supervisor, 3 the
    // Mayor / Vice Mayor. Nobody's while HR is still checking the days.
    $turn = match (true) {
        $refusedAt !== null, !$reviewed, $leave->status >= 4 => null,
        $leave->emp_esign == 1 => 0,
        default => (int) $leave->status,
    };
    $forSigning = $leave->emp_esign == 2;

    // What a button sends: where, with what, after asking what.
    $send = fn (string $label, string $icon, string $look, string $url, array $params, array $ask, string $done) =>
        compact('label', 'icon', 'look', 'url', 'params', 'ask', 'done') + ['kind' => 'post'];
    $return = fn (int $to, string $detail) => $send('Return', 'fa-rotate-left', 'quiet', route('leaveReturn'), ['id' => $leave->id, 'to' => $to],
        ['Return this leave application?', $detail, 'Return it'], 'Leave application returned.');
    $disapprove = fn (int $by) => ['kind' => 'reason', 'label' => 'Disapprove', 'icon' => 'fa-ban', 'look' => 'danger', 'by' => $by];
    $needsEsign = ['kind' => 'esign'];

    $recommend = 'the leave credits are deducted and it goes to the Mayor / Vice Mayor for final approval.';

    // ---------------------------------------------------------- the employee
    $filing = [];
    if ($isStaff && !$reviewed) {
        $filing[] = ['kind' => 'days', 'label' => 'Check the days', 'icon' => 'fa-calendar-check', 'look' => 'primary'];
    }
    if (!$isStaff && $leave->employid == $me->id && $leave->emp_esign == 1) {
        $filing[] = $hasEsign
            ? $send('E-sign', 'fa-signature', 'primary', route('leaveApprove'), ['id' => $leave->id, 'by' => 0],
                ['Sign this leave application?', 'Your e-signature goes on the form and it is sent to the HRMO.', 'Sign it'], 'Leave application signed.')
            : $needsEsign;
    }

    // -------------------------------------------------------------- the HRMO
    $hrmo = [];
    if ($mayApproveAsHr && $leave->status == 1 && $leave->remarks_stat != 1 && $forSigning) {
        $hrmo[] = $return(1, 'It goes back to the employee to be signed again.');
        $hrmo[] = $send('Approve', 'fa-check', 'primary', route('leaveApprove'), ['id' => $leave->id, 'by' => 1],
            ['Approve this leave application?', 'It goes to the immediate supervisor next.', 'Approve'], 'Leave application approved.');
    }

    // -------------------------------------------- the supervisor, or the OIC
    $supervising = [];
    $signsHere = $oicSigns ? $oic->oic_id == $me->id : $leave->supervisor == $me->id;
    if (!$isStaff && $signsHere && $leave->status == 2 && $leave->remarks_stat != 2 && $forSigning) {
        $supervising = $hasEsign
            ? [
                $return(2, 'It goes back to the HRMO.'),
                $disapprove(2),
                $send('Approve', 'fa-check', 'primary', route('leaveApprove'), ['id' => $leave->id, 'by' => 2],
                    ['Sign this leave application?', 'Your e-signature goes on the form, ' . $recommend, 'Sign it'], 'Leave application signed.'),
            ]
            : [$needsEsign];
    }
    // HR signs here on the supervisor's behalf when the supervisor is also
    // the one who gives final approval.
    if ($isStaff && $leave->supervisor == $leave->approver && $leave->status == 2) {
        $supervising[] = $send('Approve', 'fa-check', 'primary', route('leaveApprove'), ['id' => $leave->id, 'by' => 2],
            ['Approve this for the supervisor?', 'It is recorded as recommended by the supervisor, ' . $recommend, 'Approve'], 'Leave application approved.');
    }

    // ------------------------------------------------ the Mayor / Vice Mayor
    $approving = [];
    if ($isApprover && $leave->status == 3 && $leave->remarks_stat != 3) {
        $approving = [
            $return(3, 'It goes back to the supervisor and the deducted leave credits are restored.'),
            $disapprove(3),
            $send('Approve', 'fa-check', 'primary', route('leaveApprovePres'), ['id' => $leave->id, 'by' => 3],
                ['Approve this leave application?', 'Your e-signature goes on the form. This is the final approval.', 'Approve'], 'Leave application approved.'),
        ];
    }

    $filer = $own ? ($isStaff ? null : 'Me') : $named($leave, 'employee_');
    $supervisorName = $oicSigns ? $named($oic, 'o') : $named($leave, 'supervisor_');

    // [role, who, signed, when, actions]
    $stops = [
        [$own && !$isStaff ? 'Filed by you' : 'Filed by the employee', $filer, $forSigning || $leave->status > 1, $stamp($leave->date_filing), $filing],
        ['Head, HRMO', $named($leave, 'hr_'), $leave->status >= 2, $stamp($leave->hr_sdate), $hrmo],
        [$oicSigns ? 'OIC' : 'Immediate Supervisor', $supervisorName, $leave->status >= 3, $stamp($leave->sup_sdate), $supervising],
        ['Mayor / Vice Mayor', $named($setting, 'mayor_'), $leave->status >= 4, $stamp($leave->approver_sdate), $approving],
    ];

    $waitingOn = ['the employee', 'the HRMO', $oicSigns ? 'the OIC' : 'the supervisor', 'the Mayor / Vice Mayor'];
    $yours = $turn !== null && count($stops[$turn][4]) > 0;

    // What the stops do not own: HR may send an application straight to the
    // Mayor, or take it out altogether.
    $overall = [];
    if ($isStaff && $leave->status != 3) {
        $overall[] = $send('Forward to Mayor', 'fa-forward', 'quiet', route('leaveApprove'), ['id' => $leave->id, 'by' => 2],
            ['Forward this to the Mayor / Vice Mayor?', 'It skips the signatures still missing: ' . $recommend, 'Forward it'], 'Leave application forwarded.');
    }
    if ($isStaff && $leave->sup_sign != 2) {
        $overall[] = $send('Cancel application', 'fa-xmark', 'danger', route('cancelLeave', $leave->id), [],
            ['Cancel this leave application?', 'It is deleted for good; the employee would have to file it again.', 'Cancel the application', true], 'Leave application cancelled.');
    }

    $button = 'inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg px-3.5 font-medium whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 disabled:cursor-wait disabled:opacity-60';
    $looks = [
        'primary' => 'bg-forest-900 text-cream hover:bg-forest-950 dark:ring-1 dark:ring-cream/15',
        'quiet' => 'border border-line hover:border-ink/30',
        'danger' => 'border border-red-700/25 text-red-700 hover:bg-red-50 dark:border-red-300/25 dark:text-red-300 dark:hover:bg-red-500/10',
    ];
    $chip = 'inline-flex h-7 items-center gap-1.5 rounded-full px-3 text-xs font-medium whitespace-nowrap';
@endphp

<article class="@container rounded-2xl border border-line bg-surface" data-leave="{{ $leave->id }}"
         data-leave-label="{{ $type }}, {{ $inclusive }}" data-leave-days="{{ $shown($leave->days) }}">
    <header class="flex flex-wrap items-start gap-x-4 gap-y-3 p-5">
        {{-- The days asked for, as the block the eye lands on. --}}
        <p class="grid size-14 shrink-0 place-items-center rounded-xl bg-forest-100 text-center">
            <span>
                <span class="block font-display text-xl leading-none font-semibold tracking-tight tabular-nums">{{ $shown($applied) }}</span>
                <span class="mt-0.5 block text-[11px] leading-none text-forest-800">{{ $applied == 1 ? 'day' : 'days' }}</span>
            </span>
        </p>

        <div class="min-w-0 flex-1 basis-56">
            @unless($own)
                <p class="truncate text-xs font-medium text-ink/60">{{ $filer }}</p>
            @endunless
            <h3 class="font-display text-lg leading-snug font-semibold tracking-tight">{{ $type }}</h3>
            <p class="mt-0.5 text-ink/75">{{ $inclusive }}</p>
        </div>

        <div class="flex items-center gap-2">
            @if($refusedAt !== null)
                <span class="{{ $chip }} bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">Disapproved</span>
            @elseif(!$reviewed)
                <span class="{{ $chip }} bg-sun-500 text-forest-950">New</span>
            @elseif($turn === null)
                <span class="{{ $chip }} bg-forest-100 text-forest-800">Approved</span>
            @elseif($yours)
                <span class="{{ $chip }} bg-sun-500 text-forest-950">Waiting on you</span>
            @else
                <span class="{{ $chip }} bg-sun-100 text-sun-700">Waiting on {{ $waitingOn[$turn] }}</span>
            @endif

            {{-- The form exists once HR has checked the days. --}}
            @if(!$own || in_array($leave->emp_esign, [1, 2]))
                <button type="button" title="Leave form (PDF)" data-leave-form="{{ route('previewLeave', $leave->id) }}"
                        class="grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors hover:bg-forest-100 hover:text-forest-700 focus-visible:outline-2 focus-visible:outline-sun-500">
                    <i class="fas fa-file-pdf"></i><span class="sr-only">Leave form, {{ $type }}, {{ $inclusive }}</span>
                </button>
            @endif
        </div>
    </header>

    <dl class="grid grid-cols-3 gap-x-6 gap-y-3 border-t border-line px-5 py-4 @2xl:grid-cols-[minmax(0,2fr)_minmax(0,1.6fr)_repeat(3,minmax(0,0.8fr))]">
        <div class="col-span-3 @2xl:col-span-1">
            <dt class="text-xs text-ink/55">Details of leave</dt>
            <dd class="mt-0.5">
                {{ $leavePurposes[$leave->leave_purpose] ?? '' }}
                @if(filled($leave->leave_detail))
                    {{ isset($leavePurposes[$leave->leave_purpose]) ? '(' . $leave->leave_detail . ')' : $leave->leave_detail }}
                @elseif(!isset($leavePurposes[$leave->leave_purpose]))
                    <span class="text-ink/45">None given</span>
                @endif
            </dd>
        </div>
        <div class="col-span-3 @2xl:col-span-1">
            <dt class="text-xs text-ink/55">Application</dt>
            <dd class="mt-0.5 break-words tabular-nums">#{{ $leave->transnum }}</dd>
        </div>
        <div>
            <dt class="text-xs text-ink/55">With pay</dt>
            <dd class="mt-0.5 tabular-nums">{!! $reviewed ? e($shown($leave->days - $leave->day_wpay)) : '<span class="text-ink/45">Not set yet</span>' !!}</dd>
        </div>
        <div>
            <dt class="text-xs text-ink/55">Without pay</dt>
            <dd class="mt-0.5 tabular-nums">{!! $reviewed ? e($shown($leave->day_wpay)) : '<span class="text-ink/45">Not set yet</span>' !!}</dd>
        </div>
        <div>
            <dt class="text-xs text-ink/55">Holidays</dt>
            <dd class="mt-0.5 tabular-nums">{{ $reviewed ? $shown($leave->holiday) : 0 }}</dd>
        </div>
    </dl>

    {{-- The four signatures, top to bottom. A signed stop is filled in, the
         one the form is waiting at is ringed in orange. --}}
    <ol class="border-t border-line px-5 pt-5 pb-1">
        @foreach($stops as $at => [$role, $signer, $signed, $when, $actions])
            @php
                $refused = $refusedAt === $at;
                $waiting = $turn === $at;
                // The Mayor's refusal is kept in a column of its own.
                $reason = $refused ? ($at === 3 ? ($leave->remarks_details1 ?: $leave->remarks_details) : $leave->remarks_details) : null;
            @endphp
            <li class="relative flex gap-4 pb-5">
                @unless($loop->last)
                    <span class="absolute top-7 bottom-0 left-[13px] w-px {{ $signed ? 'bg-forest-600' : 'bg-line' }}" aria-hidden="true"></span>
                @endunless
                <span class="relative grid size-7 shrink-0 place-items-center rounded-full text-[11px]
                    {{ $refused ? 'bg-red-700 text-white' : ($signed ? 'bg-forest-600 text-cream' : ($waiting ? 'border-2 border-sun-500 text-sun-700' : 'border border-ink/25 text-transparent')) }}">
                    <i class="fas {{ $refused ? 'fa-ban' : ($signed ? 'fa-check' : 'fa-pen') }}"></i>
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                        <p class="{{ $signed || $waiting || $refused ? 'font-medium' : 'text-ink/60' }}">
                            {{ $signer ?: ['Leave application', 'No HR Head chosen in Settings yet', 'No supervisor assigned yet', 'No Mayor chosen in Settings yet'][$at] }}
                        </p>
                        @if($when && ($at === 0 || $signed || $refused))
                            <p class="text-xs text-ink/55 tabular-nums">{{ $at === 0 ? 'Filed ' : '' }}{{ $when }}</p>
                        @endif
                    </div>
                    <p class="text-xs {{ $waiting ? 'font-medium text-sun-700' : 'text-ink/55' }}">
                        {{ $role }}<span class="sr-only">: {{ $refused ? 'disapproved' : ($signed ? 'signed' : ($waiting ? 'waiting on them' : 'not yet')) }}</span>
                        @if($at === 0 && !$reviewed)
                            <span class="font-normal text-ink/55">· HR checks the days before it is signed</span>
                        @elseif($at === 0 && $waiting)
                            <span class="font-normal text-ink/55">· to be e-signed</span>
                        @endif
                    </p>

                    @if($reason)
                        <p class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-red-800 dark:bg-red-500/10 dark:text-red-200">{{ $reason }}</p>
                    @endif

                    @if($actions)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($actions as $action)
                                @if($action['kind'] === 'esign')
                                    <a href="{{ url('pds/signature') }}" class="{{ $button }} {{ $looks['quiet'] }}">
                                        <i class="fas fa-signature text-xs"></i> Upload your e-signature to sign
                                    </a>
                                @elseif($action['kind'] === 'days')
                                    <button type="button" data-leave-days-open class="{{ $button }} {{ $looks[$action['look']] }}">
                                        <i class="fas {{ $action['icon'] }} text-xs"></i> {{ $action['label'] }}
                                    </button>
                                @elseif($action['kind'] === 'reason')
                                    <button type="button" data-leave-reason="{{ $action['by'] }}" class="{{ $button }} {{ $looks[$action['look']] }}">
                                        <i class="fas {{ $action['icon'] }} text-xs"></i> {{ $action['label'] }}
                                    </button>
                                @else
                                    <button type="button" class="{{ $button }} {{ $looks[$action['look']] }}"
                                            data-leave-post="{{ $action['url'] }}" data-params="{{ json_encode($action['params']) }}"
                                            data-ask="{{ $action['ask'][0] }}" data-ask-detail="{{ $action['ask'][1] }}" data-ask-button="{{ $action['ask'][2] }}"
                                            data-done="{{ $action['done'] }}">
                                        <i class="fas {{ $action['icon'] }} text-xs"></i> {{ $action['label'] }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    @if($overall)
        <footer class="flex flex-wrap justify-end gap-2 border-t border-line px-5 py-3">
            @foreach($overall as $action)
                <button type="button" class="{{ $button }} {{ $looks[$action['look']] }}"
                        data-leave-post="{{ $action['url'] }}" data-params="{{ json_encode($action['params']) }}"
                        data-ask="{{ $action['ask'][0] }}" data-ask-detail="{{ $action['ask'][1] }}" data-ask-button="{{ $action['ask'][2] }}"
                        @if($action['ask'][3] ?? false) data-ask-danger @endif
                        data-done="{{ $action['done'] }}">
                    <i class="fas {{ $action['icon'] }} text-xs"></i> {{ $action['label'] }}
                </button>
            @endforeach
        </footer>
    @endif
</article>
