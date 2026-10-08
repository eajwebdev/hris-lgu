{{--
    Whose leave this is, and what they have left. Tailwind twin of
    leaves/side-menu (still used by Status and History).

    The balance figures keep their ids (b-vl, b-sl, special-pl, ...): the
    scripts on this screen write new values into them after a change.

      $otherBalances   column => [label, id of the figure], from the page
--}}
@php
    $photo = $employee->profile && file_exists(public_path('Profile/Employee/' . $employee->profile))
        ? asset('Profile/Employee/' . $employee->profile)
        : asset('Profile/Employee/default.png');

    $panelAction = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/60 transition-colors hover:bg-forest-100 hover:text-forest-700 focus-visible:outline-2 focus-visible:outline-sun-500';
@endphp
<aside class="self-start rounded-2xl border border-line bg-surface">
    @if($guard == 'web')
        <div class="border-b border-line p-3">
            <label for="leaveEmployee" class="sr-only">Employee</label>
            <select id="leaveEmployee" data-leave-url="{{ route('leavesRead', ':id') }}"
                    class="block h-10 w-full rounded-xl border border-line bg-paper pr-8 pl-3 text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15">
                {{-- Surname first and in order, so typing one in the open list jumps to it. --}}
                @foreach($emplalls->sortBy(fn ($emp) => strtolower($emp->lname . ' ' . $emp->fname)) as $emp)
                    <option value="{{ $emp->id }}" {{ $employee->id == $emp->id ? 'selected' : '' }}>{{ $emp->lname }}, {{ $emp->fname }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="p-5">
        <div class="flex items-center gap-4">
            <img src="{{ $photo }}" alt="" class="size-16 shrink-0 rounded-full object-cover">
            <div class="min-w-0">
                <p class="truncate font-display text-lg font-semibold tracking-tight">{{ ucwords(strtolower($employee->fname)) }} {{ ucwords(strtolower($employee->lname)) }}</p>
                <p class="truncate text-ink/60">{{ $employee->position ?: 'No position set' }}</p>
            </div>
        </div>

        <div class="mt-5 flex items-center justify-between gap-3">
            <h2 class="text-xs font-medium text-ink/55">Leave credits</h2>
            {{-- Credit actions live here, in the panel, so they stay in reach
                 whichever leave screen is showing. --}}
            @if($guard == 'web')
                <div class="-mr-2 flex gap-0.5">
                    <button type="button" title="Add leave credits" data-dialog-open="creditAddDialog" class="{{ $panelAction }}"><i class="fas fa-plus"></i><span class="sr-only">Add leave credits</span></button>
                    <button type="button" title="Deduct leave credits" data-dialog-open="creditDeductDialog" class="{{ $panelAction }}"><i class="fas fa-minus"></i><span class="sr-only">Deduct leave credits</span></button>
                    <button type="button" title="Set other leave balances" data-dialog-open="balancesDialog" class="{{ $panelAction }}"><i class="fas fa-sliders"></i><span class="sr-only">Set other leave balances</span></button>
                </div>
            @endif
        </div>

        <dl class="mt-2 grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-forest-100 p-3">
                <dt class="text-xs text-forest-800">Vacation Leave</dt>
                <dd class="mt-0.5 font-display text-2xl font-semibold tracking-tight" id="b-vl">{{ $employee->vl }}</dd>
            </div>
            <div class="rounded-xl bg-forest-100 p-3">
                <dt class="text-xs text-forest-800">Sick Leave</dt>
                <dd class="mt-0.5 font-display text-2xl font-semibold tracking-tight" id="b-sl">{{ $employee->sl }}</dd>
            </div>
        </dl>

        <dl class="mt-3 divide-y divide-line">
            @foreach($otherBalances as $column => [$balanceLabel, $balanceId])
                <div class="flex items-baseline justify-between gap-4 py-2">
                    <dt class="text-ink/75">{{ $balanceLabel }}</dt>
                    <dd class="font-semibold tabular-nums" id="{{ $balanceId }}">{{ $employee->{$column} ?? 0 }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</aside>

@if($guard == 'web')
    @push('scripts')
    <script>
        // Choosing another employee opens their leave credits.
        document.getElementById('leaveEmployee').addEventListener('change', function () {
            if (this.value) { window.location.href = this.dataset.leaveUrl.replace(':id', this.value); }
        });
    </script>
    @endpush
@endif
