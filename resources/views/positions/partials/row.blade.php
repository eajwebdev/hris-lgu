{{--
    One row of a repeating section of the Position Description form
    (positions/form): a supervised position, a competency, or a duty.

      $kind   'supervised', 'competency' or 'duty'
      $list   the name the rows are posted under: supervised,
              core_competencies, leadership_competencies or duties
      $at     the row's index; the page's <template> passes __i__, which its
              script swaps for a number when it adds a row
      $row    the row's values, empty for a new one
      $levels the competency levels to choose from

    The whole list is posted with the form and replaces what was stored, and
    rows left blank are dropped by the controller.
--}}
@php
    $input = 'block h-10 w-full min-w-0 rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $name = fn ($field) => $list . '[' . $at . '][' . $field . ']';

    $shape = [
        'supervised' => '@2xl:grid-cols-[minmax(0,1fr)_14rem_auto]',
        'competency' => '@2xl:grid-cols-[minmax(0,1fr)_14rem_auto]',
        'duty' => '@2xl:grid-cols-[7rem_minmax(0,1fr)_12rem_auto]',
    ][$kind];
@endphp
<li class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-2 @2xl:gap-3 {{ $shape }}" data-repeat-row>
    @if($kind === 'supervised')
        <input type="text" name="{{ $name('position_title') }}" value="{{ $row['position_title'] ?? '' }}" placeholder="Position title" aria-label="Position title" maxlength="255" class="{{ $input }} col-span-2 @2xl:col-span-1">
        <input type="text" name="{{ $name('item_number') }}" value="{{ $row['item_number'] ?? '' }}" placeholder="Item number" aria-label="Item number" maxlength="100" class="{{ $input }}">
    @elseif($kind === 'competency')
        <input type="text" name="{{ $name('name') }}" value="{{ $row['name'] ?? '' }}" placeholder="Competency" aria-label="Competency" maxlength="255" class="{{ $input }} col-span-2 @2xl:col-span-1">
        <select name="{{ $name('level') }}" aria-label="Competency level" class="{{ $input }} pr-8">
            <option value="">No level</option>
            @foreach($levels as $level)
                <option value="{{ $level }}" @selected(($row['level'] ?? '') === $level)>{{ $level }}</option>
            @endforeach
        </select>
    @else
        <div class="relative">
            <input type="number" name="{{ $name('percentage') }}" value="{{ $row['percentage'] ?? '' }}" step="0.01" min="0" max="100" inputmode="decimal"
                   aria-label="Percent of working time" data-duty-percent class="{{ $input }} pr-8 tabular-nums">
            <span class="pointer-events-none absolute inset-y-0 right-3 grid place-items-center text-ink/45">%</span>
        </div>
        <span class="@2xl:hidden"></span>
        <textarea name="{{ $name('duty') }}" rows="2" placeholder="Duty or responsibility" aria-label="Duty or responsibility"
                  class="{{ str_replace('h-10 ', '', $input) }} col-span-2 min-h-16 py-2 leading-relaxed @2xl:col-span-1">{{ $row['duty'] ?? '' }}</textarea>
        <select name="{{ $name('competency_level') }}" aria-label="Competency level" class="{{ $input }} pr-8">
            <option value="">No level</option>
            @foreach($levels as $level)
                <option value="{{ $level }}" @selected(($row['competency_level'] ?? '') === $level)>{{ $level }}</option>
            @endforeach
        </select>
    @endif

    <button type="button" data-repeat-remove title="Remove this row"
            class="grid size-10 cursor-pointer place-items-center rounded-xl text-ink/45 transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-sun-500 dark:hover:bg-red-500/10 dark:hover:text-red-300">
        <i class="fas fa-xmark"></i><span class="sr-only">Remove this row</span>
    </button>
</li>
