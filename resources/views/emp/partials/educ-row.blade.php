{{--
    One college degree or one graduate course on the Educational Background
    page (emp/educational-bg): a row of the list emp/partials/pds-autosave
    saves.

      $entry   the six answers, in the order of $keys; all empty for a new row
      $keys    what each is posted as: school, course, period, level, year
               graduated, honors
      $item    "College" or "Graduate studies", for the row's heading

    No commas in any of them: each column is one comma-separated string, and
    a comma inside an answer would shift every later row out of step.
--}}
@php
    $input = 'mt-1 block h-10 w-full min-w-0 rounded-xl border border-line bg-surface px-3 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15';
    $caption = 'block text-xs font-medium text-ink/60';

    // label, input type, placeholder, columns taken
    $answers = [
        ['Name of school (write in full)', 'text', 'N/A', '@md:col-span-2'],
        ['Degree / course (write in full)', 'text', 'N/A', '@md:col-span-2'],
        ['Period of attendance', 'text', '2021-2024', ''],
        ['Highest level / units earned', 'text', 'If not graduated', ''],
        ['Year graduated', 'number', 'N/A', ''],
        ['Scholarship / academic honors', 'text', 'N/A', ''],
    ];
@endphp
<li class="rounded-xl border border-line bg-paper/60 p-4" data-row>
    <div class="flex items-center justify-between gap-4">
        <p class="font-medium">{{ $item }} <span data-row-number></span></p>
        <button type="button" data-row-remove title="Remove this entry"
                class="-my-1 -mr-1 grid size-9 cursor-pointer place-items-center rounded-lg text-ink/45 transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-sun-500 dark:hover:bg-red-500/10 dark:hover:text-red-300">
            <i class="fas fa-trash"></i><span class="sr-only">Remove this entry</span>
        </button>
    </div>

    <div class="mt-3 grid gap-4 @md:grid-cols-2 @4xl:grid-cols-4">
        @foreach($answers as $position => [$wording, $inputType, $example, $width])
            <label class="{{ $width }}">
                <span class="{{ $caption }}">{{ $wording }}</span>
                <input type="{{ $inputType }}" value="{{ $entry[$position] }}" placeholder="{{ $example }}"
                       data-key="{{ $keys[$position] }}" data-strip="," class="{{ $input }}">
            </label>
        @endforeach
    </div>
</li>
