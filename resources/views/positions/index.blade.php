@extends('layouts.app')

@php
    // Position Descriptions (DBM-CSC Form No. 1), one per plantilla item, and
    // the vacancy last advertised from each. Create and edit are
    // positions/form.

    $bannerButton = 'inline-flex h-10 items-center gap-2 rounded-xl border px-4 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $rowAction = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $pill = 'inline-block rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap';
    $pageLink = 'inline-flex h-9 items-center rounded-lg px-3 transition-colors hover:bg-paper focus-visible:outline-2 focus-visible:outline-sun-500';
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Positions &amp; Vacancies</h1>
            <p class="mt-1 max-w-2xl text-cream/70">
                DBM-CSC Form No. 1 (Revised 2017). One standing description per plantilla item,
                reused by every posting of that item.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('psbMembers') }}" class="{{ $bannerButton }} border-cream/25 hover:border-cream hover:bg-cream hover:text-forest-900">
                <i class="fas fa-user-tie"></i> Selection Board
            </a>
            <a href="{{ route('positionDescriptionCreate') }}" class="{{ $bannerButton }} border-cream bg-cream text-forest-900 hover:bg-white">
                <i class="fas fa-plus"></i> New position description
            </a>
        </div>
    </div>
@endsection

@section('body')
<section class="rounded-2xl border border-line bg-surface">
    <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
        {{-- Searched on the server: the list is paged, fifteen to a page. --}}
        <form method="GET" class="relative w-full sm:max-w-sm sm:flex-1">
            <label for="positionSearch" class="sr-only">Search position descriptions</label>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" id="positionSearch" name="q" value="{{ $search }}" placeholder="Search title, item no. or office, then Enter" autocomplete="off"
                   class="h-10 w-full rounded-xl border border-line bg-paper pr-3 pl-9 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15">
        </form>

        @if($search !== '')
            <a href="{{ route('positionDescriptionList') }}" class="font-medium text-forest-700 underline-offset-2 hover:underline">Clear search</a>
        @endif

        <p class="ml-auto text-ink/55">{{ number_format($descriptions->total()) }} on file</p>
    </div>

    @if($descriptions->isEmpty())
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-sun-100 text-xl text-sun-700"><i class="far fa-file-alt"></i></span>
            @if($search !== '')
                <p class="mt-4 font-medium">No position description matches &ldquo;{{ $search }}&rdquo;.</p>
            @else
                <p class="mt-4 font-medium">No position descriptions yet.</p>
                <p class="mt-1 text-ink/55">Create one to describe a plantilla item; vacancies are advertised from it.</p>
            @endif
        </div>
    @else
        <div class="relative overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-line text-xs text-ink/55">
                    <tr>
                        <th scope="col" class="px-4 py-3 pl-5 font-medium">Position title</th>
                        <th scope="col" class="px-4 py-3 font-medium max-md:hidden">Item no.</th>
                        <th scope="col" class="px-4 py-3 font-medium max-lg:hidden">SG</th>
                        <th scope="col" class="px-4 py-3 font-medium max-lg:hidden">Office</th>
                        <th scope="col" class="px-4 py-3 font-medium max-lg:hidden">Duties</th>
                        <th scope="col" class="px-4 py-3 font-medium">Vacancy</th>
                        <th scope="col" class="px-4 py-3 font-medium max-sm:hidden">Status</th>
                        <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($descriptions as $d)
                        <tr class="transition-colors hover:bg-paper/70">
                            <td class="px-4 py-3 pl-5">
                                <a href="{{ route('positionDescriptionEdit', $d->id) }}" class="font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-sun-500">{{ $d->full_title }}</a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap max-md:hidden">{{ $d->item_number ?: '—' }}</td>
                            <td class="px-4 py-3 tabular-nums max-lg:hidden">{{ $d->salary_grade ?: '—' }}</td>
                            <td class="px-4 py-3 max-lg:hidden">{{ $d->bureau_office ?: '—' }}</td>
                            <td class="px-4 py-3 tabular-nums max-lg:hidden">{{ $d->duties_count }}</td>
                            <td class="px-4 py-3">
                                @if($d->latestPosting)
                                    @php
                                        $p = $d->latestPosting;
                                        $expired = $p->expiration_at && $p->expiration_at < now()->toDateString();
                                        $live = $p->status === 'Open' && ! $expired;
                                    @endphp
                                    <span class="{{ $pill }} {{ $live ? 'bg-forest-100 text-forest-800' : 'bg-line/60 text-ink/65' }}">{{ $live ? 'Open' : ($expired ? 'Expired' : 'Closed') }}</span>
                                    <a href="{{ route('psbAssessment', $p->id) }}" title="Comparative Assessment" class="ml-1.5 text-forest-700 underline-offset-2 hover:underline">
                                        {{ $p->applications_count }} applicant{{ $p->applications_count == 1 ? '' : 's' }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-ink/55">
                                        closes {{ \Carbon\Carbon::parse($p->expiration_at)->format('M d, Y') }}
                                        @if($d->postings_count > 1)
                                            &middot; {{ $d->postings_count }} rounds
                                        @endif
                                    </p>
                                @else
                                    <span class="text-ink/45">Not advertised</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 max-sm:hidden">
                                <span class="{{ $pill }} {{ $d->status === 'active' ? 'bg-forest-100 text-forest-800' : 'bg-line/60 text-ink/65' }}">{{ ucfirst($d->status) }}</span>
                            </td>
                            <td class="px-4 py-3 pr-5">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('positionDescriptionPrint', $d->id) }}" target="_blank" title="Print DBM-CSC Form No. 1" class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700">
                                        <i class="fas fa-print"></i><span class="sr-only">Print</span>
                                    </a>
                                    <a href="{{ route('positionDescriptionEdit', $d->id) }}" title="Edit" class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700">
                                        <i class="fas fa-pen"></i><span class="sr-only">Edit</span>
                                    </a>
                                    {{-- One that a posting uses is archived by the server, not deleted. --}}
                                    <form method="POST" action="{{ route('positionDescriptionDelete', $d->id) }}" data-confirm-danger
                                          @if($d->postings_count > 0)
                                              data-confirm="Archive this position description?"
                                              data-confirm-detail="{{ $d->full_title }} is used by {{ $d->postings_count }} posting(s), so it will be archived rather than deleted."
                                              data-confirm-button="Yes, archive it"
                                          @else
                                              data-confirm="Delete this position description?"
                                              data-confirm-detail="{{ $d->full_title }}"
                                              data-confirm-button="Yes, delete it"
                                          @endif>
                                        @csrf
                                        <button type="submit" title="Delete" class="{{ $rowAction }} hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300">
                                            <i class="fas fa-trash"></i><span class="sr-only">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($descriptions->hasPages())
            <nav aria-label="Pages" class="flex items-center justify-between gap-3 border-t border-line px-4 py-3">
                <p class="text-ink/55">Page {{ $descriptions->currentPage() }} of {{ $descriptions->lastPage() }}</p>
                <div class="flex gap-1">
                    @if($descriptions->onFirstPage())
                        <span class="{{ $pageLink }} cursor-default opacity-35">Previous</span>
                    @else
                        <a href="{{ $descriptions->previousPageUrl() }}" class="{{ $pageLink }}">Previous</a>
                    @endif
                    @if($descriptions->hasMorePages())
                        <a href="{{ $descriptions->nextPageUrl() }}" class="{{ $pageLink }}">Next</a>
                    @else
                        <span class="{{ $pageLink }} cursor-default opacity-35">Next</span>
                    @endif
                </div>
            </nav>
        @endif
    @endif
</section>
@endsection
