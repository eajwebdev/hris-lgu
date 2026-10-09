{{--
    One row of the Other Information page (emp/other-info): a skill, a
    distinction and a membership, and a button to take the row away. $entry
    is the three answers, all empty for a new row. A row of the list
    emp/partials/pds-autosave saves.

    No commas: each column is one comma-separated string.
--}}
@php
    $input = 'block h-10 w-full min-w-0 rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $answers = [
        ['skills_hob', 'special skill or hobby', 'Skill or hobby'],
        ['recognition', 'non-academic distinction or recognition', 'Distinction or recognition'],
        ['mem_org', 'membership in an association or organization', 'Association or organization'],
    ];
@endphp
<li class="grid grid-cols-[minmax(0,1fr)_auto] gap-2 @2xl:grid-cols-[repeat(3,minmax(0,1fr))_auto] @2xl:gap-4" data-row>
    @foreach($answers as $position => [$key, $spoken, $example])
        <input type="text" value="{{ $entry[$position] }}" placeholder="{{ $example }}" data-key="{{ $key }}" data-label="{{ $spoken }}" data-strip=","
               class="{{ $input }} {{ $position < 2 ? 'col-span-2 @2xl:col-span-1' : '' }}">
    @endforeach
    <button type="button" data-row-remove title="Remove this row"
            class="grid size-10 cursor-pointer place-items-center rounded-xl text-ink/45 transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-sun-500 dark:hover:bg-red-500/10 dark:hover:text-red-300">
        <i class="fas fa-trash"></i><span class="sr-only">Remove this row</span>
    </button>
</li>
