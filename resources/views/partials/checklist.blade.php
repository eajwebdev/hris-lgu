{{--
    Pick any number from a long list: a search box over a scrolling list of
    tick boxes, with a running count and the chosen names spelled out. Used
    where the old pages had a "multiple" select2. The behaviour is in
    layouts/app-scripts ([data-checklist]).

      $label      what is being picked ("Interview panel")
      $name       the posted field, with its brackets ("panels[]")
      $options    [[value, text], ...]
      $selected   values already chosen (optional)
      $hint       a line under the list (optional)
      $required   message shown if the form is sent with none chosen (optional)
      $form       id of the form the boxes belong to, when they sit outside it (optional)
      $noun       what the search box searches ("employees" by default)

    Those already chosen are listed first, so they are not lost among the rest.
--}}
@php
    $picked = collect($selected ?? [])->map(fn ($value) => (string) $value)->all();
    $ordered = collect($options)->sortBy(fn ($option) => in_array((string) $option[0], $picked, true) ? 0 : 1)->values();
@endphp
<fieldset data-checklist @if(!empty($required)) data-checklist-required @endif>
    <div class="flex items-baseline justify-between gap-3">
        <legend class="block text-xs font-medium text-ink/60">{{ $label }}</legend>
        <p class="text-xs text-ink/55" data-checklist-count></p>
    </div>

    <div class="mt-1 overflow-hidden rounded-xl border border-line">
        <div class="border-b border-line p-2">
            <input type="search" data-checklist-search placeholder="Search {{ $noun ?? 'employees' }}" autocomplete="off" aria-label="Search {{ $noun ?? 'employees' }}"
                   class="block h-9 w-full rounded-lg border border-line bg-paper px-3 text-ink outline-none placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface">
        </div>
        <div class="max-h-56 overflow-y-auto p-1.5 [scrollbar-width:thin]">
            @foreach($ordered as [$value, $text])
                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-paper has-checked:bg-forest-100 has-checked:text-forest-800" data-checklist-row>
                    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @if(!empty($form)) form="{{ $form }}" @endif
                           class="size-4 shrink-0 accent-forest-600" @checked(in_array((string) $value, $picked, true))>
                    <span>{{ $text }}</span>
                </label>
            @endforeach
            <p class="px-3 py-4 text-center text-ink/50" data-checklist-none hidden>No match.</p>
        </div>
    </div>

    <p class="mt-1.5 text-xs text-ink/70" data-checklist-summary hidden></p>
    @if(!empty($hint))
        <p class="mt-1 text-xs leading-relaxed text-ink/55">{!! $hint !!}</p>
    @endif
    @if(!empty($required))
        <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-red-800 dark:bg-red-500/10 dark:text-red-300" data-checklist-error hidden>{{ $required }}</p>
    @endif
</fieldset>
