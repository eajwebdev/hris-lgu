{{--
    One child on the Family Background page (emp/family-bg): a name, a date
    of birth and a button to take the row away. $child is [name, date], both
    empty for a new row. A row of the list emp/partials/pds-autosave saves.
--}}
@php
    $input = 'block h-10 w-full min-w-0 rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
@endphp
<li class="grid grid-cols-[minmax(0,1fr)_auto] gap-2 @md:grid-cols-[minmax(0,1fr)_11rem_auto] @md:gap-4" data-row>
    {{-- No commas: the names are kept as one comma-separated list, so a
         comma inside a name would split it in two and pair every later child
         with the wrong birthday. --}}
    <input type="text" value="{{ $child[0] }}" placeholder="Full name" data-key="name_child" data-label="full name" data-strip=","
           class="{{ $input }} col-span-2 @md:col-span-1">
    <input type="date" value="{{ $child[1] }}" data-key="date_birth" data-label="date of birth" class="{{ $input }}">
    <button type="button" data-row-remove title="Remove this child"
            class="grid size-10 cursor-pointer place-items-center rounded-xl text-ink/45 transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-sun-500 dark:hover:bg-red-500/10 dark:hover:text-red-300">
        <i class="fas fa-trash"></i><span class="sr-only">Remove this child</span>
    </button>
</li>
