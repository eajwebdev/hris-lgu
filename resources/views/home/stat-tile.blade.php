{{--
    A dashboard figure with its recent history underneath: label, number, one
    line of context, and a strip of columns ending on the current period.

    The dashboard's own, compact tile. The larger card with line charts and a
    change badge is partials/stat-tile (Face Attendance); the two are kept
    apart on purpose, so restyling one page's cards does not move the other's.

      $label, $value, $icon   the figure
      $note                   one line under the number
      $series                 [['label' => 'Mon, Oct 5', 'value' => 12], ...] oldest first
      $caption                what the columns are ("Filed, last 14 days")
      $unit                   noun for the hover readout ("present", "filed")
      $href                   optional; makes the whole tile a link
      $waiting                optional; tints the icon when the tile needs attention

    The last column is the period the number counts, so it alone takes the
    full colour; the ones before it are the same green held back. Hovering a
    column reads it out (the shell script, layouts/app-scripts), and the same
    values are written out for screen readers, so nothing depends on the hover.
--}}
@php
    $href = $href ?? null;
    $waiting = $waiting ?? false;
    $tag = $href ? 'a' : 'div';
    $peak = max(1, collect($series)->max('value'));
    $empty = collect($series)->sum('value') == 0;
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif
    class="relative flex flex-col rounded-2xl border border-line bg-surface p-4 sm:p-5 {{ $href ? 'transition-colors hover:border-forest-600/40 focus-visible:outline-2 focus-visible:outline-sun-500' : '' }}">
    <div class="flex items-start justify-between gap-3">
        <p class="text-ink/60">{{ $label }}</p>
        <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $waiting ? 'bg-sun-100 text-sun-700' : 'bg-forest-100 text-forest-700' }}">
            <i class="fas {{ $icon }}"></i>
        </span>
    </div>

    <p class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ number_format($value) }}</p>
    <p class="mt-1 text-xs text-ink/55">{{ $note }}</p>

    {{-- Caption above, columns last: the baselines then line up along the
         bottom of a row of tiles however the text above them wraps. --}}
    <figure class="mt-auto pt-4">
        <figcaption class="mb-1.5 text-[11px] text-ink/45">{{ $caption }}</figcaption>

        {{-- Each column sits in a full-height slot, so the pointer only has to
             be above or below it, not on it. --}}
        <div class="relative flex h-10 items-end gap-0.5 border-b border-line" aria-hidden="true">
            @foreach($series as $point)
                <div class="group/col flex h-full flex-1 items-end justify-center"
                     data-spark="{{ number_format($point['value']) }} {{ $unit }}" data-spark-label="{{ $point['label'] }}">
                    @if($point['value'] > 0)
                        <div class="w-full max-w-6 rounded-t-[3px] transition-colors group-hover/col:bg-forest-800 {{ $loop->last ? 'bg-forest-600 dark:bg-forest-500' : 'bg-forest-600/25 dark:bg-forest-500/35' }}"
                             style="height: {{ max(8, round($point['value'] / $peak * 100)) }}%"></div>
                    @endif
                </div>
            @endforeach

            @if($empty)
                <p class="pointer-events-none absolute inset-x-0 bottom-1.5 text-[11px] text-ink/35">None in this period</p>
            @endif
        </div>

        {{-- The columns in words, for screen readers. Text rather than a
             table: a hidden table still takes its full width, and widened the
             page on a phone. --}}
        <p class="sr-only">
            {{ $label }}, {{ $caption }}:
            @foreach($series as $point)
                {{ $point['label'] }}, {{ number_format($point['value']) }} {{ $unit }}@if(!$loop->last);@endif
            @endforeach
        </p>
    </figure>
</{{ $tag }}>
