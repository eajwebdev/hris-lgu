{{--
    Finished leave applications as a searchable list: approved, disapproved
    or cancelled, with the reason beside a refusal.

      $table   ['id'       => for the list's own parts,
                'rows'     => the applications,
                'withFiler'=> show whose each one is (employee_* columns),
                'empty'    => what to say when there are none]

    From the page: $guard, $leaveTypes. The buttons are acted on by
    leaves/partials/actions.
--}}
@php
    $isStaff = $guard == 'web';
    $withFiler = $table['withFiler'] ?? false;

    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $rowAction = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $chip = 'inline-flex h-7 items-center gap-1.5 rounded-full px-3 text-xs font-medium whitespace-nowrap';
    $sorter = '-mx-1.5 inline-flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 transition-colors hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500';
    $shown = fn ($number) => rtrim(rtrim(number_format((float) $number, 2, '.', ''), '0'), '.');
    $day = fn ($when) => filled($when) ? \Carbon\Carbon::parse($when)->format('M j, Y') : null;
@endphp

<section class="rounded-2xl border border-line bg-surface" id="{{ $table['id'] }}" data-list>
    @if(count($table['rows']))
        <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
            <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
                <span class="sr-only">Search</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
                <input type="search" data-list-search placeholder="{{ $withFiler ? 'Search a name, a leave type' : 'Search a leave type, a month' }}" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
            </label>
            <p class="ml-auto text-ink/55" data-list-count aria-live="polite"></p>
        </div>

        {{-- relative: keeps the visually hidden labels in the cells inside
             this scroller instead of widening the page on a phone. --}}
        <div class="relative overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-line text-xs text-ink/55">
                    <tr>
                        <th scope="col" class="px-4 py-3 pl-5 font-medium">
                            <button type="button" data-list-sort="{{ $withFiler ? 'filer' : 'type' }}" class="{{ $sorter }}">{{ $withFiler ? 'Employee' : 'Leave' }} <i class="fas fa-sort text-[10px] opacity-50"></i></button>
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            <button type="button" data-list-sort="from" class="{{ $sorter }}">Inclusive dates <i class="fas fa-sort text-[10px] opacity-50"></i></button>
                        </th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Days</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium max-sm:hidden">Without pay</th>
                        <th scope="col" class="px-4 py-3 font-medium max-md:hidden">
                            <button type="button" data-list-sort="filed" class="{{ $sorter }}">Filed <i class="fas fa-sort text-[10px] opacity-50"></i></button>
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Outcome</th>
                        <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($table['rows'] as $leave)
                        @php
                            $type = $leaveTypes[$leave->leave_type] ?? 'Unknown';
                            $dates = array_map('trim', explode(' to ', (string) $leave->date_range));
                            try {
                                $from = \Carbon\Carbon::parse($dates[0]);
                                $to = isset($dates[1]) ? \Carbon\Carbon::parse($dates[1]) : null;
                                $inclusive = $to && !$to->isSameDay($from)
                                    ? $from->format($from->year == $to->year ? 'M j' : 'M j, Y') . ' to ' . $to->format('M j, Y')
                                    : $from->format('M j, Y');
                                $fromKey = $from->format('Y-m-d');
                            } catch (\Throwable $e) {
                                $inclusive = $leave->date_range;
                                $fromKey = '';
                            }

                            $filer = $withFiler ? trim(preg_replace('/\s+/', ' ', $leave->employee_lname . ', ' . $leave->employee_fname . ' ' . $leave->employee_suffix), ' ,') : null;
                            $filed = $day($leave->date_filing);

                            // 0 went through; 4 was approved and then cancelled
                            // by HR; anything else was refused along the way.
                            $outcome = $leave->remarks_stat == 0 ? 'Approved' : ($leave->remarks_stat == 4 ? 'Cancelled' : 'Disapproved');
                            $reason = match (true) {
                                $leave->remarks_stat == 0 => null,
                                $leave->remarks_stat == 4 => $leave->remarks_details2,
                                // The Mayor's refusal is kept in a column of its own.
                                default => $leave->remarks_details ?: $leave->remarks_details1,
                            };
                        @endphp
                        <tr data-row data-leave="{{ $leave->id }}" data-leave-label="{{ ($filer ? $filer . ', ' : '') . $type . ', ' . $inclusive }}"
                            data-search="{{ strtolower($filer . ' ' . $type . ' ' . $inclusive . ' ' . $filed . ' ' . $outcome . ' ' . $reason) }}"
                            data-type="{{ strtolower($type) }}" data-filer="{{ strtolower((string) $filer) }}" data-from="{{ $fromKey }}"
                            data-filed="{{ filled($leave->date_filing) ? \Carbon\Carbon::parse($leave->date_filing)->format('Y-m-d H:i:s') : '' }}"
                            class="transition-colors hover:bg-paper/70">
                            <td class="px-4 py-3 pl-5 align-top">
                                @if($withFiler)
                                    <p class="font-semibold">{{ $filer ?: 'No longer on record' }}</p>
                                    <p class="mt-0.5 text-xs text-ink/55">{{ $type }}</p>
                                @else
                                    <p class="font-semibold">{{ $type }}</p>
                                    <p class="mt-0.5 text-xs text-ink/55 tabular-nums">#{{ $leave->transnum }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top whitespace-nowrap">{{ $inclusive }}</td>
                            <td class="px-4 py-3 text-right align-top tabular-nums">{{ $shown($leave->days) }}</td>
                            <td class="px-4 py-3 text-right align-top tabular-nums max-sm:hidden">{!! $leave->day_wpay ? e($shown($leave->day_wpay)) : '<span class="text-ink/35">0</span>' !!}</td>
                            <td class="px-4 py-3 align-top whitespace-nowrap max-md:hidden">{{ $filed }}</td>
                            <td class="px-4 py-3 align-top">
                                <span class="{{ $chip }} {{ $outcome === 'Approved' ? 'bg-forest-100 text-forest-800' : ($outcome === 'Cancelled' ? 'bg-ink/8 text-ink/70' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300') }}">{{ $outcome }}</span>
                                @if(filled($reason))
                                    <p class="mt-1.5 max-w-64 text-xs text-ink/65">{{ $reason }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 pr-5 align-top">
                                <div class="flex justify-end gap-1">
                                    {{-- HR may take back a leave that went through. --}}
                                    @if($isStaff && $leave->remarks_stat == 0)
                                        <button type="button" title="Cancel this leave" data-leave-reason="4" class="{{ $rowAction }} hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300">
                                            <i class="fas fa-xmark"></i><span class="sr-only">Cancel this leave</span>
                                        </button>
                                    @endif
                                    <button type="button" title="Leave form (PDF)" data-leave-form="{{ route('previewLeave', $leave->id) }}" class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700">
                                        <i class="fas fa-file-pdf"></i><span class="sr-only">Leave form</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>Nothing here matches that.</p>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
            <label class="flex items-center gap-2 text-ink/55">
                Rows
                <select data-list-size class="h-9 rounded-xl border border-line bg-paper pr-7 pl-3 text-ink outline-none focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="0">All</option>
                </select>
            </label>
            <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
        </div>
    @else
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-forest-100 text-xl text-forest-700"><i class="fas fa-clock-rotate-left"></i></span>
            <p class="mt-4 font-medium">No leave on record yet.</p>
            <p class="mx-auto mt-1 max-w-sm text-ink/55">{{ $table['empty'] }}</p>
        </div>
    @endif
</section>
