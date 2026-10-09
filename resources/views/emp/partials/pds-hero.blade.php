{{--
    Banner heading of a Personal Data Sheet page on layouts/app, for the
    page's `hero` section.

      $about       which page of the sheet this is: "Family background"
      $autosaves   true where answers are saved one at a time, with no Save
                   button (emp/partials/pds-autosave); the banner then says
                   so and carries the indicator that script keeps up to date

    HR is reminded whose sheet it is; an employee only ever sees their own.
--}}
<div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
    <div>
        <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Personal Data Sheet</h1>
        <p class="mt-1 text-cream/70">
            @if($guard == 'web')
                {{ trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) }}
                <span class="mx-1.5 text-cream/30">|</span>
            @endif
            {{ $about }}@if($autosaves ?? false)<span class="max-sm:hidden">. Each answer is saved as soon as you change it.</span>@endif
        </p>
    </div>

    {{-- In the banner because the banner is the one part of the page always
         in view. --}}
    @if($autosaves ?? false)
        <p id="pdsSaveState" role="status" data-state="idle"
           class="inline-flex h-10 items-center gap-2 rounded-xl border border-cream/25 px-4 text-cream/80 data-[state=error]:border-sun-500 data-[state=error]:text-sun-500">
            <i class="fas fa-cloud-arrow-up"></i> <span>Saves automatically</span>
        </p>
    @endif
</div>
