{{--
    One labelled field of the Add Employee form (emp/empadd), drawn from a
    description passed as $spec:

      name          the employees column it fills; also the field's name
      label
      type          text (the default), date, number, email or select
      icon          Font Awesome name drawn in a cell at the field's left
      options       select: value => wording
      prompt        select: the first entry, worded "Select …"; it posts
                    nothing, so an optional field can be emptied again
      required
      readonly      shown, greyed, and filled in by the page
      placeholder
      span          columns of the twelve-column personal grid: 2 (a sixth,
                    the default) or 3 (a quarter); 1 for a field in a grid
                    of its own, such as an address
      status        id for an empty line under the field that the page
                    writes a remark into (the Employee ID check)
      attrs         any other attributes: name => value

    The value is whatever was typed if the form has just been refused.
    layouts/app-select draws every <select> as a button that takes the
    select's own classes, so the icon cell sits over it just as it does over
    an input.
--}}
@php
    $type = $spec['type'] ?? 'text';
    $fieldId = $spec['attrs']['id'] ?? 'add-' . $spec['name'];
    $current = old($spec['name']);
    $readonly = $spec['readonly'] ?? false;
    $icon = $spec['icon'] ?? null;
    $span = [1 => '', 2 => '@5xl:col-span-2', 3 => '@5xl:col-span-3'][$spec['span'] ?? 2];

    $control = 'block h-11 w-full rounded-lg border border-line text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15 disabled:bg-paper disabled:text-ink/45 '
        . ($readonly ? 'bg-paper text-ink/70 ' : 'bg-surface ')
        . ($icon ? 'pl-13 ' : 'pl-3.5 ')
        . ($type === 'select' ? 'pr-8' : 'pr-3');

    $attributes = collect($spec['attrs'] ?? [])
        ->except('id')
        ->when($readonly, fn ($all) => $all->put('readonly', ''))
        ->when($spec['required'] ?? false, fn ($all) => $all->put('required', ''))
        ->map(fn ($attribute, $key) => $attribute === '' ? $key : $key . '="' . e($attribute) . '"')
        ->implode(' ');
@endphp
<div class="{{ $span }}">
    <label for="{{ $fieldId }}" class="block text-[13px] font-semibold text-ink">
        {{ $spec['label'] }}@if($spec['required'] ?? false)<span class="text-red-600" title="Required"> *</span>@endif
    </label>

    <div class="relative mt-1.5">
        @if($icon)
            <span class="pointer-events-none absolute inset-y-1.5 left-0 z-10 grid w-10 place-items-center border-r border-line text-ink/55" aria-hidden="true">
                <i class="fas {{ $icon }} text-[13px]"></i>
            </span>
        @endif

        @if($type === 'select')
            <select id="{{ $fieldId }}" name="{{ $spec['name'] }}" class="{{ $control }}" {!! $attributes !!}>
                <option value="">{{ $spec['prompt'] ?? 'Select' }}</option>
                @foreach($spec['options'] as $optionValue => $wording)
                    <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $current)>{{ $wording }}</option>
                @endforeach
            </select>
        @else
            <input type="{{ $type }}" id="{{ $fieldId }}" name="{{ $spec['name'] }}" value="{{ $current }}"
                   placeholder="{{ $spec['placeholder'] ?? '' }}" autocomplete="off" class="{{ $control }}" {!! $attributes !!}>
        @endif
    </div>

    @isset($spec['status'])
        <p id="{{ $spec['status'] }}" class="mt-1 text-xs empty:hidden" aria-live="polite"></p>
    @endisset
</div>
