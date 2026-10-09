{{--
    One labelled field of a Personal Data Sheet form on layouts/app, drawn
    from a description passed as $spec:

      name          the column it fills; also the field's name
      label
      type          text (the default), date, number, email, select, textarea,
                    file, or lines: `count` short answers under one label,
                    posted as name[0], name[1], … and read back from a
                    string separated by `separator`
      options       select: value => wording
      prompt        select: the first entry, shown while nothing is chosen;
                    it cannot be picked
      value         defaults to that column of $record, the row the page is
                    editing, passed beside $spec; without one, of $employee
      save          false for a field that is shown but not saved
      slot          the field is one position of a separated column, saved
                    as that (see emp/partials/pds-autosave)
      readonly      shown, greyed, and not saved
      required
      placeholder   defaults to N/A, as the form asks of an empty answer
      hint          a line under the field
      span          2 to take two columns, 'full' for the whole row
      attrs         any other attributes: name => value
      data          data-* attributes: name => value

    Two kinds of form use it. On the pages with no Save button a field is
    marked data-save and saved on its own when it changes
    (emp/partials/pds-autosave). With $plain passed as true beside $spec it
    is an ordinary field of a form that is submitted: nothing is marked, the
    value is whatever was typed if the form has just been refused, and
    otherwise that column of $record, which may be null for a new entry.

    The grid it sits in is a container: columns follow the width of the form,
    not of the window, since the sidebar and the side panel both take from it.

    The description comes in as one array because an included view also sees
    every variable of the view including it; loose names like $value or $type
    could be answered by one of those.
--}}
@php
    $type = $spec['type'] ?? 'text';
    $plain = $plain ?? false;
    $readonly = $spec['readonly'] ?? false;
    $saved = !$plain && ($spec['save'] ?? true) && !$readonly && !isset($spec['slot']);

    if (array_key_exists('value', $spec)) {
        $current = $spec['value'];
    } elseif ($plain) {
        $current = old($spec['name'], optional($record ?? null)->{$spec['name']});
    } else {
        $current = ($record ?? $employee)->{$spec['name']};
    }

    $fieldId = 'pds-' . $spec['name'];
    $span = ['2' => '@md:col-span-2', 'full' => 'col-span-full'][(string) ($spec['span'] ?? '')] ?? '';

    $control = 'mt-1 block w-full rounded-xl border border-line outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15 '
        . ($readonly ? 'bg-transparent text-ink/60' : 'bg-paper text-ink focus:bg-surface');

    // Everything the three kinds of control have in common after the class.
    $attributes = collect($spec['attrs'] ?? [])
        ->merge(collect($spec['data'] ?? [])->mapWithKeys(fn ($datum, $key) => ['data-' . $key => $datum]))
        ->when($saved, fn ($all) => $all->put('data-save', ''))
        ->when(isset($spec['slot']), fn ($all) => $all->put('data-save-slot', $spec['slot']))
        ->when($readonly, fn ($all) => $all->put('readonly', ''))
        ->when($spec['required'] ?? false, fn ($all) => $all->put('required', ''))
        ->map(fn ($attribute, $key) => $attribute === '' ? $key : $key . '="' . e($attribute) . '"')
        ->implode(' ');
@endphp
<div class="{{ $span }}">
    <label for="{{ $fieldId }}" class="block text-xs font-medium text-ink/60">
        {{ $spec['label'] }}@if($spec['required'] ?? false)<span class="text-sun-700" title="Required"> *</span>@endif
    </label>

    @if($type === 'select')
        <select id="{{ $fieldId }}" name="{{ $spec['name'] }}" class="{{ $control }} h-10 pr-8 pl-3" {!! $attributes !!}>
            @isset($spec['prompt'])
                <option value="" disabled selected>{{ $spec['prompt'] }}</option>
            @endisset
            @foreach($spec['options'] as $optionValue => $wording)
                <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $current)>{{ $wording }}</option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $spec['name'] }}" rows="{{ $spec['rows'] ?? 5 }}" placeholder="{{ $spec['placeholder'] ?? 'N/A' }}"
                  class="{{ $control }} px-3 py-2 leading-relaxed" {!! $attributes !!}>{{ $current }}</textarea>
    @elseif($type === 'lines')
        @php
            $lines = is_array($current) ? $current : explode($spec['separator'], (string) $current);
        @endphp
        <div class="mt-1 space-y-1.5">
            @for($line = 0; $line < $spec['count']; $line++)
                <input type="text" @if($line === 0) id="{{ $fieldId }}" @endif name="{{ $spec['name'] }}[{{ $line }}]" value="{{ trim($lines[$line] ?? '') }}"
                       aria-label="{{ $spec['label'] }}, line {{ $line + 1 }}" data-strip="{{ $spec['separator'] }}"
                       class="{{ str_replace('mt-1 ', '', $control) }} h-10 px-3">
            @endfor
        </div>
    @elseif($type === 'file')
        <input type="file" id="{{ $fieldId }}" name="{{ $spec['name'] }}"
               class="{{ $control }} h-10 cursor-pointer text-ink/70 overflow-hidden pr-3 file:mr-3 file:h-full file:cursor-pointer file:border-0 file:border-r file:border-line file:bg-surface file:px-3 file:font-medium file:text-ink" {!! $attributes !!}>
    @else
        <input type="{{ $type }}" id="{{ $fieldId }}" name="{{ $spec['name'] }}" value="{{ $current }}"
               placeholder="{{ $spec['placeholder'] ?? 'N/A' }}" class="{{ $control }} h-10 px-3" {!! $attributes !!}>
    @endif

    @isset($spec['hint'])
        <p class="mt-1 text-xs text-ink/55">{{ $spec['hint'] }}</p>
    @endisset
</div>
