@php
    $href = $href ?? null;
    $tag = $href ? 'a' : 'div';
    $values = collect($series)->pluck('value')->map(fn ($value) => (float) $value)->values();
    $peak = max(1, (float) $values->max());
    $denominator = max(1, $values->count() - 1);
    $points = $values->map(fn ($value, $index) => round($index / $denominator * 66, 1).','.round(34 - ($value / $peak * 26), 1))->implode(' ');
    $tone = match (true) {
        str_contains(strtolower($label), 'present'), str_contains(strtolower($label), 'experience') => ['bg-blue-100 text-blue-600', '#3995ff'],
        str_contains(strtolower($label), 'absent') => ['bg-red-100 text-red-600', '#ff5158'],
        str_contains(strtolower($label), 'leave') => ['bg-amber-100 text-amber-600', '#f5ae22'],
        str_contains(strtolower($label), 'eligib') => ['bg-purple-100 text-purple-600', '#bd76f4'],
        str_contains(strtolower($label), 'voluntary') => ['bg-orange-100 text-orange-600', '#ff8739'],
        default => ['bg-emerald-100 text-emerald-600', '#31b96b'],
    };
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif
    class="group relative flex min-h-29 items-center gap-4 rounded-2xl border border-line bg-surface p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-forest-600/25 hover:shadow-md focus-visible:outline-2 focus-visible:outline-sun-500">
    <span class="grid size-14 shrink-0 place-items-center rounded-xl text-xl {{ $tone[0] }}">
        <i class="fas {{ $icon }}"></i>
    </span>
    <div class="min-w-0 flex-1">
        <p class="truncate text-[13px] text-ink/60">{{ $label }}</p>
        <p class="mt-0.5 font-display text-3xl font-bold leading-none tracking-tight tabular-nums">{{ number_format($value) }}</p>
        <p class="mt-1 truncate text-xs text-ink/50">{{ $note }}</p>
    </div>
    <svg viewBox="0 0 66 38" class="mt-8 hidden h-10 w-16 shrink-0 overflow-visible 2xl:block" role="img" aria-label="{{ $caption }}">
        <polyline points="{{ $points }}" fill="none" stroke="{{ $tone[1] }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    @if($href)
        <span class="absolute top-3 right-3 grid size-7 place-items-center rounded-full bg-forest-100 text-[10px] text-forest-800 transition-transform group-hover:translate-x-0.5"><i class="fas fa-angle-right"></i></span>
    @endif
</{{ $tag }}>
