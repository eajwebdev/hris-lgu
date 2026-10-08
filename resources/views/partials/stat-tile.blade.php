{{--
    A figure with its recent history: icon, the number, a line of context,
    how it moved since the period before, and a small chart.

    The larger card, used on Face Attendance. The dashboard has its own
    compact tile, home/stat-tile; the two are kept apart on purpose.

      $label, $value, $icon   the figure
      $note                   one line under the number
      $series                 [['label' => 'Mon, Oct 5', 'value' => 12], ...] oldest first
      $caption                what the chart is ("Last 14 days")
      $unit                   noun for the hover readout ("present", "filed")

    Optional:
      $chart     'area' for a level followed over time (the default is 'columns',
                 which suits counts of separate things)
      $current   false when the series is not a run of periods ending on now
                 (hours of one day, say); the last point is then not picked out
      $trend     'up-good', 'down-good' or 'neutral': show the change from the
                 point before the last one, coloured by whether that is welcome
      $versus    what that point is ("vs previous day")
      $href      makes the whole tile a link
      $press     makes it a button instead; a data attribute, e.g. data-show-place="far"
      $waiting   tints the icon when the tile needs attention

    The last point is the period the number counts, so it alone takes the full
    colour. Hovering the chart reads each point out (layouts/app-scripts), and
    the same values are written out for screen readers, so nothing depends
    on the hover.
--}}
@php
    $href = $href ?? null;
    $press = $press ?? null;
    $waiting = $waiting ?? false;
    $current = $current ?? true;
    $chart = $chart ?? 'columns';
    $trend = $trend ?? null;
    $versus = $versus ?? 'vs the period before';

    $tag = $href ? 'a' : ($press ? 'button' : 'div');
    $points = array_values($series);
    $count = count($points);
    $peak = max(1, collect($points)->max('value'));
    $empty = collect($points)->sum('value') == 0;

    // Change from the point before the last, in the series' own units.
    $change = ($trend && $count > 1) ? $points[$count - 1]['value'] - $points[$count - 2]['value'] : null;
    $welcome = $change === null || $change == 0 || $trend === 'neutral'
        ? null
        : (($change > 0) === ($trend === 'up-good'));
    $changeLook = $welcome === null
        ? 'bg-line/60 text-ink/70'
        : ($welcome ? 'bg-forest-100 text-forest-800' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300');

    // Area chart geometry, in a 100 x 40 box stretched to the tile. Each point
    // sits over the middle of its hover slot.
    $x = fn ($i) => round(($i + 0.5) / max(1, $count) * 100, 2);
    $y = fn ($value) => round(37 - ($value / $peak) * 31, 2);
    $line = collect($points)->map(fn ($point, $i) => ($i ? 'L' : 'M') . $x($i) . ' ' . $y($point['value']))->implode(' ');
    $gradient = 'tileArea' . substr(md5($label . $caption . $count), 0, 8);
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif @if($press) type="button" {!! $press !!} @endif
    class="relative flex flex-col rounded-2xl border border-line bg-surface p-5 text-left {{ ($href || $press) ? 'cursor-pointer transition-colors hover:border-forest-600/40 focus-visible:outline-2 focus-visible:outline-sun-500' : '' }}">
    <span class="grid size-11 shrink-0 place-items-center rounded-xl text-lg {{ $waiting ? 'bg-sun-100 text-sun-700' : 'bg-forest-100 text-forest-700' }}">
        <i class="fas {{ $icon }}"></i>
    </span>

    <span class="mt-4 block text-ink/60">{{ $label }}</span>
    <span class="block font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ number_format($value) }}</span>
    <span class="mt-1 block text-xs text-ink/55">{{ $note }}</span>

    {{-- On its own line so it can wrap: some of these tiles are narrow. --}}
    @if($change !== null)
        <span class="mt-2.5 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs">
            <span class="rounded-full px-2 py-0.5 font-medium whitespace-nowrap {{ $changeLook }}">
                {{ $change == 0 ? 'No change' : ($change > 0 ? '+' : '−') . number_format(abs($change)) }}
            </span>
            <span class="text-ink/55">{{ $versus }}</span>
        </span>
    @endif

    {{-- Caption above, chart last: the baselines then line up along the
         bottom of a row of tiles however the text above them wraps. --}}
    <span class="mt-auto block pt-5">
        <span class="mb-2 block text-[11px] text-ink/45">{{ $caption }}</span>

        <span class="relative block h-14 border-b border-line" aria-hidden="true">
            @if($chart === 'area')
                <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="absolute inset-0 size-full text-forest-600 dark:text-forest-500">
                    <defs>
                        <linearGradient id="{{ $gradient }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="currentColor" stop-opacity=".2"/>
                            <stop offset="1" stop-color="currentColor" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    @if($count)
                        <path d="{{ $line }} L{{ $x($count - 1) }} 40 L{{ $x(0) }} 40 Z" fill="url(#{{ $gradient }})"/>
                        <path d="{{ $line }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                    @endif
                </svg>
                {{-- The end of the line is the figure above. A round dot cannot
                     be drawn in the stretched SVG, so it is placed over it. --}}
                @if($count && $current)
                    <span class="absolute size-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-forest-600 ring-2 ring-surface dark:bg-forest-500"
                          style="left: {{ $x($count - 1) }}%; top: {{ $y($points[$count - 1]['value']) / 40 * 100 }}%"></span>
                @endif
            @endif

            {{-- One full-height slot per point: the pointer only has to be above
                 or below a point, not on it. Columns are drawn in the slots. --}}
            <span class="absolute inset-0 flex items-end gap-0.5">
                @foreach($points as $point)
                    <span class="group/col flex h-full flex-1 items-end justify-center"
                          data-spark="{{ number_format($point['value']) }} {{ $unit }}" data-spark-label="{{ $point['label'] }}">
                        @if($chart === 'area')
                            <span class="h-full w-px bg-ink/25 opacity-0 group-hover/col:opacity-100"></span>
                        @elseif($point['value'] > 0)
                            <span class="w-full max-w-6 rounded-t-[3px] transition-colors group-hover/col:bg-forest-800 {{ (!$current || $loop->last) ? 'bg-forest-600 dark:bg-forest-500' : 'bg-forest-600/25 dark:bg-forest-500/35' }}"
                                  style="height: {{ max(8, round($point['value'] / $peak * 100)) }}%"></span>
                        @endif
                    </span>
                @endforeach
            </span>

            @if($empty && $chart !== 'area')
                <span class="pointer-events-none absolute inset-x-0 bottom-1.5 text-[11px] text-ink/35">None in this period</span>
            @endif
        </span>

        {{-- The chart's values in words, for screen readers. Plain text, not a
             table: the tile may be a link or a button, which cannot hold one,
             and a hidden table still takes its full width and widens the page. --}}
        <span class="sr-only">
            {{ $label }}, {{ $caption }}:
            @foreach($points as $point)
                {{ $point['label'] }}, {{ number_format($point['value']) }} {{ $unit }}@if(!$loop->last);@endif
            @endforeach
        </span>
    </span>
</{{ $tag }}>
