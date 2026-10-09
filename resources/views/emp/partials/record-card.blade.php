{{--
    One entry on a Personal Data Sheet page that keeps a list of them
    (eligibility, work experience, voluntary work, learning and development),
    drawn by emp/partials/pds-records from a description passed as $entry:

      id, title, subtitle
      facts        label => answer; an empty answer shows as N/A
      status       '1' reviewed, '2' canceled, anything else waiting for HR
      remarks      HR's reason, shown when canceled
      attachment   the PDF's address, or null
      edit         address of the edit page, or null where not allowed
      approve, delete   addresses the script posts to, or null
      cancel       whether HR may cancel it with remarks

    The buttons only carry addresses; emp/partials/pds-records acts on them.
--}}
@php
    $status = (string) $entry['status'] === '1' ? '1' : ((string) $entry['status'] === '0' || $entry['status'] === null ? '0' : '2');
    $action = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $calm = $action . ' hover:bg-forest-100 hover:text-forest-700';
    $grave = $action . ' hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300';
@endphp
<li class="rounded-2xl border border-line bg-surface p-5 sm:p-6" data-record="{{ $entry['id'] }}" data-record-title="{{ $entry['title'] }}"
    data-search="{{ strtolower($entry['title'] . ' ' . $entry['subtitle'] . ' ' . implode(' ', $entry['facts'])) }}">
    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
        <div class="min-w-0">
            <h3 class="font-display text-lg leading-snug font-semibold tracking-tight">{{ $entry['title'] }}</h3>
            @if(filled($entry['subtitle']))
                <p class="mt-0.5 text-ink/60">{{ $entry['subtitle'] }}</p>
            @endif
        </div>

        <div class="flex items-center gap-1">
            <span data-record-status data-status="{{ $status }}"
                  class="mr-1 rounded-full bg-sun-100 px-3 py-1 text-xs font-medium whitespace-nowrap text-sun-700 data-[status='1']:bg-forest-100 data-[status='1']:text-forest-800 data-[status='2']:bg-red-50 data-[status='2']:text-red-700 dark:data-[status='2']:bg-red-500/10 dark:data-[status='2']:text-red-300">{{ ['To be reviewed', 'Reviewed', 'Canceled'][$status] }}</span>

            @if($entry['edit'])
                <a href="{{ $entry['edit'] }}" title="Edit" class="{{ $calm }}"><i class="fas fa-pen"></i><span class="sr-only">Edit</span></a>
            @endif
            @if($entry['approve'])
                <button type="button" title="Approve" data-record-approve="{{ $entry['approve'] }}" class="{{ $calm }}"><i class="fas fa-check"></i><span class="sr-only">Approve</span></button>
            @endif
            @if($entry['cancel'])
                <button type="button" title="Cancel, with remarks" data-record-cancel class="{{ $grave }}"><i class="fas fa-ban"></i><span class="sr-only">Cancel, with remarks</span></button>
            @endif
            @if($entry['delete'])
                <button type="button" title="Delete" data-record-delete="{{ $entry['delete'] }}" class="{{ $grave }}"><i class="fas fa-trash"></i><span class="sr-only">Delete</span></button>
            @endif
        </div>
    </div>

    <dl class="mt-4 grid gap-x-6 gap-y-3 border-t border-line pt-4 @md:grid-cols-2 @4xl:grid-cols-3">
        @foreach($entry['facts'] as $fact => $answer)
            <div class="min-w-0">
                <dt class="text-xs text-ink/55">{{ $fact }}</dt>
                <dd class="mt-0.5 break-words {{ filled($answer) ? '' : 'text-ink/40' }}">{{ filled($answer) ? $answer : 'N/A' }}</dd>
            </div>
        @endforeach
    </dl>

    @if($status === '2' && filled($entry['remarks']))
        <p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-red-800 dark:bg-red-500/10 dark:text-red-300" data-record-remarks>
            <strong class="font-semibold">Remarks:</strong> {{ $entry['remarks'] }}
        </p>
    @endif

    @if($entry['attachment'])
        <button type="button" data-pdf-open="{{ $entry['attachment'] }}"
                class="mt-4 inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-line px-3 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            <i class="fas fa-file-pdf text-xs text-ink/50"></i> View attachment
        </button>
    @endif
</li>
