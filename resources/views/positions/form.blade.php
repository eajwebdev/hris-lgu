@extends('layouts.app')

@php
    // Create or edit a Position Description: DBM-CSC Form No. 1 (Revised
    // 2017), the standing description of one plantilla item. The sections
    // follow the printed form and carry its numbers.
    //
    // One form, one save (PositionDescriptionController::store / update). The
    // four repeating sections are posted whole and replace what was stored.
    // The last card advertises the position: filled in, it writes the vacancy
    // the careers portal shows, with the description copied onto it.
    //
    // A save that is refused comes back with what was typed, rows included.

    $isNew = !$description->exists;
    $action = $isNew ? route('positionDescriptionStore') : route('positionDescriptionUpdate', $description->id);
    $levels = ['Basic', 'Intermediate', 'Advanced', 'Superior'];

    $contacts = old('contacts', $description->contacts ?? []);
    $conditions = old('working_conditions', $description->working_conditions ?? []);

    // The repeating sections, each always with a row to type into.
    $orBlank = fn ($rows) => array_values($rows) ?: [[]];
    $supervised = $orBlank(old('supervised', $description->supervised->map->only('position_title', 'item_number')->all()));
    $core = $orBlank(old('core_competencies', $description->core_competencies ?? []));
    $lead = $orBlank(old('leadership_competencies', $description->leadership_competencies ?? []));
    $duties = $orBlank(old('duties', $description->duties->map->only('percentage', 'duty', 'competency_level')->all()));

    $day = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : '';

    // Plain fields of the description: [name, label, columns of twelve].
    $text = fn (string $name, string $label, int $span = 6, array $more = []) => compact('name', 'label', 'span') + $more;

    $card = 'rounded-2xl border border-line bg-surface p-5 sm:p-6';
    $heading = 'font-display text-lg font-semibold tracking-tight';
    $number = 'rounded-md bg-forest-100 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-forest-800 tabular-nums';
    $label = 'block text-xs font-medium text-ink/60';
    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $cell = $field . ' mt-1 block h-10 w-full px-3';
    $area = $field . ' mt-1 block w-full px-3 py-2 leading-relaxed';
    $grid = 'mt-4 grid gap-4 @2xl:grid-cols-12';
    $spans = [3 => '@2xl:col-span-6 @5xl:col-span-3', 4 => '@2xl:col-span-4', 6 => '@2xl:col-span-6', 12 => '@2xl:col-span-12'];
    $quiet = 'inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-line px-3 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $add = $quiet . ' mt-3';
    $rowHead = 'hidden gap-3 text-xs font-medium text-ink/55 @2xl:grid';
    $choice = 'cursor-pointer rounded-md px-2.5 py-1 text-xs font-medium text-ink/60 transition-colors has-checked:bg-forest-900 has-checked:text-cream has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-sun-500 dark:has-checked:bg-forest-600';
    $bannerButton = 'inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border px-4 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
@endphp

@section('breadcrumb', $isNew ? 'New' : $description->full_title)

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div class="min-w-0">
            <h1 class="font-display text-3xl font-semibold tracking-tight max-sm:line-clamp-2 max-sm:text-2xl sm:text-4xl">{{ $isNew ? 'New position description' : $description->full_title }}</h1>
            <p class="mt-1 text-cream/70">DBM-CSC Form No. 1, Revised Version No. 1, s. 2017</p>
        </div>

        {{-- Here because the banner stays in view the whole way down the
             form. On a phone only Save is kept, or the banner would take a
             third of the screen; Cancel is also at the foot of the form. --}}
        <div class="flex flex-wrap gap-2">
            @unless($isNew)
                <a href="{{ route('positionDescriptionPrint', $description->id) }}" target="_blank" class="{{ $bannerButton }} border-cream/25 hover:border-cream hover:bg-cream hover:text-forest-900 max-sm:hidden">
                    <i class="fas fa-print"></i> Print form
                </a>
            @endunless
            <a href="{{ route('positionDescriptionList') }}" class="{{ $bannerButton }} border-cream/25 hover:border-cream hover:bg-cream hover:text-forest-900 max-sm:hidden">Cancel</a>
            <button type="submit" form="pdForm" class="{{ $bannerButton }} border-cream bg-cream text-forest-900 hover:bg-white">
                <i class="fas fa-save"></i> {{ $isNew ? 'Create description' : 'Save changes' }}
            </button>
        </div>
    </div>
@endsection

@section('body')
<form method="POST" action="{{ $action }}" id="pdForm" autocomplete="off" class="@container mx-auto max-w-6xl space-y-5">
    @csrf

    {{-- Stays on the page, where the toast that says the same goes away. --}}
    @if($errors->any())
        <div role="alert" class="rounded-2xl border border-red-300 bg-red-50 p-5 text-red-800 dark:border-red-400/40 dark:bg-red-500/10 dark:text-red-300">
            <p class="font-semibold"><i class="fas fa-triangle-exclamation mr-1"></i> Nothing was saved.</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- The three cards of plain fields. Each field: name, label, width. --}}
    @foreach([
        ['1&ndash;3', 'Position identity', [
            $text('position_title', '1. Position title', 6, ['required' => true, 'max' => 255]),
            $text('parenthetical_title', 'Parenthetical title', 6, ['max' => 255]),
            $text('item_number', '2. Item number', 6, ['max' => 100]),
            $text('salary_grade', '3. Salary grade', 6, ['max' => 50]),
        ]],
        ['4&ndash;8', 'Governmental unit and office', [
            $text('working_conditions[gov_unit]', '4. Governmental unit', 4, ['value' => $conditions['gov_unit'] ?? '',
                'options' => ['province' => 'Province', 'city' => 'City', 'municipality' => 'Municipality']]),
            $text('working_conditions[gov_class]', 'Income class', 4, ['value' => $conditions['gov_class'] ?? '',
                'options' => array_combine($classes = ['1st Class', '2nd Class', '3rd Class', '4th Class', '5th Class', '6th Class', 'Special'], $classes)]),
            $text('lgu_unit_and_class', 'Unit and class (as written)', 4, ['max' => 255]),
            $text('department_agency', '5. Department, corporation or agency / LGU', 6, ['max' => 255]),
            $text('bureau_office', '6. Bureau or office', 6, ['max' => 255, 'list' => 'officeList']),
            $text('division_branch', '7. Department / branch / division', 6, ['max' => 255]),
            $text('workstation', '8. Workstation / place of work', 6, ['max' => 255]),
        ]],
        ['9&ndash;14', 'Appropriation and supervision', [
            $text('present_approp_act', '9. Present appropriation act', 3, ['max' => 255]),
            $text('previous_approp_act', '10. Previous appropriation act', 3, ['max' => 255]),
            $text('salary_authorized', '11. Salary authorized', 3, ['max' => 255]),
            $text('other_compensation', '12. Other compensation', 3, ['max' => 255]),
            $text('immediate_supervisor_title', '13. Position title of immediate supervisor', 6, ['max' => 255]),
            $text('next_higher_supervisor_title', '14. Position title of next higher supervisor', 6, ['max' => 255]),
        ]],
    ] as [$items, $cardTitle, $fields])
        <section class="{{ $card }}">
            <h2 class="{{ $heading }} flex items-center gap-3"><span class="{{ $number }}">{!! $items !!}</span> {{ $cardTitle }}</h2>

            <div class="{{ $grid }}">
                @foreach($fields as $spec)
                    @php
                        $id = 'pd-' . preg_replace('/\W+/', '-', trim($spec['name'], ']'));
                    @endphp
                    <div class="{{ $spans[$spec['span']] }}">
                        <label for="{{ $id }}" class="{{ $label }}">
                            {{ $spec['label'] }}@if($spec['required'] ?? false)<span class="text-sun-700" title="Required"> *</span>@endif
                        </label>
                        @isset($spec['options'])
                            <select id="{{ $id }}" name="{{ $spec['name'] }}" class="{{ $cell }} pr-8">
                                <option value="">Select</option>
                                @foreach($spec['options'] as $value => $wording)
                                    <option value="{{ $value }}" @selected($spec['value'] === $value)>{{ $wording }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" id="{{ $id }}" name="{{ $spec['name'] }}" value="{{ old($spec['name'], $description->{$spec['name']}) }}"
                                   maxlength="{{ $spec['max'] }}" class="{{ $cell }}"
                                   @isset($spec['list']) list="{{ $spec['list'] }}" @endisset @if($spec['required'] ?? false) required @endif>
                        @endisset
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <datalist id="officeList">
        @foreach($offices as $office)
            <option value="{{ $office->office_name }}">
        @endforeach
    </datalist>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }} flex items-center gap-3"><span class="{{ $number }}">15</span> Positions directly supervised</h2>
        <p class="mt-1 text-ink/60">If more than seven, list only item numbers and titles.</p>

        <div data-repeat="supervised" class="mt-4">
            <div class="{{ $rowHead }} mb-1 grid-cols-[minmax(0,1fr)_14rem_auto]" aria-hidden="true">
                <span>Position title</span><span>Item number</span><span class="w-10"></span>
            </div>
            <ol class="space-y-4 @2xl:space-y-2" data-repeat-rows>
                @foreach($supervised as $at => $row)
                    @include('positions.partials.row', ['kind' => 'supervised', 'list' => 'supervised', 'at' => $at, 'row' => $row, 'levels' => $levels])
                @endforeach
            </ol>
            <template>
                @include('positions.partials.row', ['kind' => 'supervised', 'list' => 'supervised', 'at' => '__i__', 'row' => [], 'levels' => $levels])
            </template>
            <button type="button" data-repeat-add class="{{ $add }}"><i class="fas fa-plus text-xs"></i> Add a position</button>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }} flex items-center gap-3"><span class="{{ $number }}">16</span> <label for="pd-equipment">Machine, equipment and tools used regularly</label></h2>
        <div class="mt-3">
            <textarea id="pd-equipment" name="equipment_used" rows="3" class="{{ $area }}">{{ old('equipment_used', $description->equipment_used) }}</textarea>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }} flex items-center gap-3"><span class="{{ $number }}">17</span> Contacts, clients and stakeholders</h2>
        <p class="mt-1 text-ink/60">How often the position deals with each.</p>

        <div class="mt-4 grid gap-x-10 gap-y-6 @4xl:grid-cols-2">
            @foreach(['internal' => ['17a. Internal', $internal], 'external' => ['17b. External', $external]] as $side => [$sideTitle, $groups])
                <div>
                    <h3 class="font-medium">{{ $sideTitle }}</h3>
                    <div class="mt-1 divide-y divide-line">
                        @foreach($groups as $key => $group)
                            @php
                                $chosen = $contacts[$side][$key] ?? '';
                            @endphp
                            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-2.5" role="radiogroup" aria-label="{{ $group }}">
                                <span>{{ $group }}</span>
                                <span class="flex gap-0.5 rounded-lg border border-line bg-paper p-0.5">
                                    @foreach($frequencies + ['' => 'None'] as $value => $often)
                                        <label class="{{ $choice }}">
                                            <input type="radio" name="contacts[{{ $side }}][{{ $key }}]" value="{{ $value }}" class="sr-only" @checked((string) $chosen === (string) $value)>
                                            {{ $often }}
                                        </label>
                                    @endforeach
                                </span>
                            </div>
                        @endforeach
                    </div>

                    @if($side === 'external')
                        <label for="pd-contacts-others" class="{{ $label }} mt-3">Others, please specify</label>
                        <input type="text" id="pd-contacts-others" name="contacts[external_others_specify]" value="{{ $contacts['external_others_specify'] ?? '' }}" class="{{ $cell }}">
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }} flex items-center gap-3"><span class="{{ $number }}">18</span> Working condition</h2>

        <div class="mt-4 grid items-end gap-4 @2xl:grid-cols-2">
            <div class="flex h-10 items-center gap-6">
                @foreach(['office_work' => 'Office work', 'field_work' => 'Field work'] as $key => $condition)
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="working_conditions[{{ $key }}]" value="1" class="size-4 accent-forest-600" @checked(!empty($conditions[$key]))>
                        {{ $condition }}
                    </label>
                @endforeach
            </div>
            <div>
                <label for="pd-conditions-others" class="{{ $label }}">Others, please specify</label>
                <input type="text" id="pd-conditions-others" name="working_conditions[others]" value="{{ $conditions['others'] ?? '' }}" class="{{ $cell }}">
            </div>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }} flex items-center gap-3"><span class="{{ $number }}">19&ndash;20</span> General functions</h2>

        <div class="mt-4 grid gap-4 @2xl:grid-cols-2">
            @foreach(['unit_general_function' => '19. General function of the unit or section', 'position_general_function' => '20. General function of the position (job summary)'] as $name => $wording)
                <div>
                    <label for="pd-{{ $name }}" class="{{ $label }}">{{ $wording }}</label>
                    <textarea id="pd-{{ $name }}" name="{{ $name }}" rows="6" class="{{ $area }}">{{ old($name, $description->{$name}) }}</textarea>
                </div>
            @endforeach
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="{{ $heading }} flex items-center gap-3"><span class="{{ $number }}">21</span> Qualification standards</h2>
        <p class="mt-1 text-ink/60">Shown on every posting of this item.</p>

        <div class="mt-4 grid gap-4 @2xl:grid-cols-2 @5xl:grid-cols-4">
            @foreach(['qs_education' => '21a. Education', 'qs_experience' => '21b. Experience', 'qs_training' => '21c. Training', 'qs_eligibility' => '21d. Eligibility'] as $name => $wording)
                <div>
                    <label for="pd-{{ $name }}" class="{{ $label }}">{{ $wording }}</label>
                    <textarea id="pd-{{ $name }}" name="{{ $name }}" rows="4" class="{{ $area }}">{{ old($name, $description->{$name}) }}</textarea>
                </div>
            @endforeach
        </div>

        @foreach(['core_competencies' => ['21e. Core competencies', $core, 'Add a core competency'], 'leadership_competencies' => ['21f. Leadership competencies', $lead, 'Add a leadership competency']] as $list => [$listTitle, $rows, $addWording])
            <div data-repeat="{{ $list }}" class="mt-6 border-t border-line pt-5">
                <h3 class="font-medium">{{ $listTitle }}</h3>
                <div class="{{ $rowHead }} mt-3 mb-1 grid-cols-[minmax(0,1fr)_14rem_auto]" aria-hidden="true">
                    <span>Competency</span><span>Competency level</span><span class="w-10"></span>
                </div>
                <ol class="mt-3 space-y-4 @2xl:mt-0 @2xl:space-y-2" data-repeat-rows>
                    @foreach($rows as $at => $row)
                        @include('positions.partials.row', ['kind' => 'competency', 'list' => $list, 'at' => $at, 'row' => $row, 'levels' => $levels])
                    @endforeach
                </ol>
                <template>
                    @include('positions.partials.row', ['kind' => 'competency', 'list' => $list, 'at' => '__i__', 'row' => [], 'levels' => $levels])
                </template>
                <button type="button" data-repeat-add class="{{ $add }}"><i class="fas fa-plus text-xs"></i> {{ $addWording }}</button>
            </div>
        @endforeach
    </section>

    <section class="{{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
            <h2 class="{{ $heading }} flex items-center gap-3"><span class="{{ $number }}">22</span> Statement of duties and responsibilities</h2>
            {{-- The duties should account for the whole working week; past
                 100 this turns red, so it is caught here and not at signing. --}}
            <p id="dutyTotal" aria-live="polite" class="rounded-full bg-forest-100 px-3 py-1 text-xs font-medium text-forest-800 tabular-nums data-[over]:bg-red-50 data-[over]:text-red-700 dark:data-[over]:bg-red-500/10 dark:data-[over]:text-red-300">0% of working time</p>
        </div>
        <p class="mt-1 text-ink/60">The technical competencies of the position.</p>

        <div data-repeat="duties" class="mt-4">
            <div class="{{ $rowHead }} mb-1 grid-cols-[7rem_minmax(0,1fr)_12rem_auto]" aria-hidden="true">
                <span>% of time</span><span>Duty or responsibility</span><span>Competency level</span><span class="w-10"></span>
            </div>
            <ol class="space-y-5 @2xl:space-y-2" data-repeat-rows>
                @foreach($duties as $at => $row)
                    @include('positions.partials.row', ['kind' => 'duty', 'list' => 'duties', 'at' => $at, 'row' => $row, 'levels' => $levels])
                @endforeach
            </ol>
            <template>
                @include('positions.partials.row', ['kind' => 'duty', 'list' => 'duties', 'at' => '__i__', 'row' => [], 'levels' => $levels])
            </template>
            <button type="button" data-repeat-add class="{{ $add }}"><i class="fas fa-plus text-xs"></i> Add a duty</button>
        </div>
    </section>

    {{-- Publication. This is what used to be the separate "Job Openings"
         screen: the same position, advertised. Everything descriptive is
         taken from the sections above, so nothing is typed twice. --}}
    <section class="{{ str_replace('border-line', 'border-sun-500/50', $card) }}">
        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
            <h2 class="{{ $heading }} flex items-center gap-3">
                <span class="grid size-7 place-items-center rounded-md bg-sun-100 text-xs text-sun-700"><i class="fas fa-bullhorn"></i></span> Advertise this position
            </h2>
            @if($posting)
                <p class="rounded-full bg-sun-100 px-3 py-1 text-xs font-medium text-sun-700">
                    Currently {{ strtolower($posting->status) }}, {{ $posting->applications()->count() }} {{ $posting->applications()->count() == 1 ? 'applicant' : 'applicants' }}
                </p>
            @endif
        </div>
        <p class="mt-1 max-w-3xl text-ink/60">
            Optional. Fill this in when the item becomes vacant: it is published only when both dates are given,
            with the title, office and qualification standards above copied onto the posting.
        </p>

        <div class="mt-4 grid gap-4 @2xl:grid-cols-2 @5xl:grid-cols-4">
            <div>
                <label for="pd-type" class="{{ $label }}">Nature of appointment</label>
                <select id="pd-type" name="type" class="{{ $cell }} pr-8">
                    @foreach($types as $value => $wording)
                        <option value="{{ $value }}" @selected(old('type', optional($posting)->type) === $value)>{{ $wording }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="pd-salary" class="{{ $label }}">Monthly salary</label>
                <input type="number" id="pd-salary" name="salary" step="0.01" min="0" inputmode="decimal" value="{{ old('salary', optional($posting)->salary) }}" class="{{ $cell }} tabular-nums">
            </div>
            <div>
                <label for="pd-posted" class="{{ $label }}">Date posted</label>
                <input type="date" id="pd-posted" name="posted_at" value="{{ old('posted_at', $day(optional($posting)->posted_at)) }}" class="{{ $cell }}">
            </div>
            <div>
                <label for="pd-closing" class="{{ $label }}">Closing date</label>
                <input type="date" id="pd-closing" name="expiration_at" value="{{ old('expiration_at', $day(optional($posting)->expiration_at)) }}" class="{{ $cell }}">
            </div>
            <div class="@2xl:col-span-2">
                <label for="pd-vacancy" class="{{ $label }}">Vacancy status</label>
                <select id="pd-vacancy" name="vacancy_status" class="{{ $cell }} pr-8">
                    <option value="Open" @selected(old('vacancy_status', optional($posting)->status) !== 'Closed')>Open: accepting applications</option>
                    <option value="Closed" @selected(old('vacancy_status', optional($posting)->status) === 'Closed')>Closed</option>
                </select>
            </div>
        </div>

        @if($posting)
            <label class="mt-5 flex cursor-pointer gap-3 rounded-xl border border-line p-4 transition-colors has-checked:border-forest-600 has-checked:bg-forest-100/60">
                <input type="checkbox" name="new_round" value="1" class="mt-0.5 size-4 shrink-0 accent-forest-600" @checked(old('new_round'))>
                <span>
                    <span class="block font-medium">Publish as a new recruitment round, not as a change to the current one</span>
                    <span class="mt-0.5 block leading-relaxed text-ink/65">For re-advertising the item later. The existing round keeps its own applicants, interview panel and Comparative Assessment.</span>
                </span>
            </label>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('psbAssessment', $posting->id) }}" class="{{ $quiet }}"><i class="fas fa-scale-balanced text-xs text-ink/50"></i> Comparative Assessment</a>
                <a href="{{ route('careersPortal') }}" target="_blank" class="{{ $quiet }}"><i class="fas fa-up-right-from-square text-xs text-ink/50"></i> View on the careers portal</a>
            </div>
        @endif
    </section>

    <section class="{{ $card }} flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        @unless($isNew)
            <a href="{{ route('positionDescriptionPrint', $description->id) }}" target="_blank" class="{{ str_replace('h-9', 'h-10', $quiet) }} sm:hidden"><i class="fas fa-print text-xs text-ink/50"></i> Print form</a>
        @endunless
        <div class="w-44">
            <label for="pd-status" class="{{ $label }}">This description is</label>
            <select id="pd-status" name="status" class="{{ $cell }} pr-8">
                <option value="active" @selected(old('status', $description->status) !== 'archived')>Active</option>
                <option value="archived" @selected(old('status', $description->status) === 'archived')>Archived</option>
            </select>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('positionDescriptionList') }}" class="inline-flex h-10 items-center rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Cancel</a>
            <button type="submit" class="h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">
                <i class="fas fa-save mr-1"></i> {{ $isNew ? 'Create description' : 'Save changes' }}
            </button>
        </div>
    </section>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('pdForm');

    /* ------------------------------------------------------ repeating rows */
    // A new row is the section's <template>, with __i__ in its field names
    // swapped for a number no other row of the section has. The numbers only
    // keep the rows apart in the post; the controller renumbers them.
    Array.prototype.forEach.call(form.querySelectorAll('[data-repeat]'), function (section) {
        var rows = section.querySelector('[data-repeat-rows]');
        var template = section.querySelector('template');
        var next = rows.children.length;

        section.querySelector('[data-repeat-add]').addEventListener('click', function () {
            rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__i__/g, next++));
            var row = rows.lastElementChild;

            // The template's selects have not been dressed by the shell yet.
            Array.prototype.forEach.call(row.querySelectorAll('select'), window.hrisSelect);
            row.querySelector('input, textarea').focus();
        });

        rows.addEventListener('click', function (event) {
            var button = event.target.closest('[data-repeat-remove]');
            if (!button) return;

            // The last row is emptied rather than removed, so the section
            // never collapses to nothing.
            if (rows.children.length > 1) {
                button.closest('[data-repeat-row]').remove();
            } else {
                Array.prototype.forEach.call(rows.querySelectorAll('input, textarea, select'), function (control) {
                    if (control.tagName === 'SELECT') { control.selectedIndex = 0; } else { control.value = ''; }
                });
            }
            totalDuties();
        });
    });

    /* -------------------------------------------------------- duties total */
    var total = document.getElementById('dutyTotal');

    function totalDuties() {
        var sum = 0;
        Array.prototype.forEach.call(form.querySelectorAll('[data-duty-percent]'), function (input) { sum += parseFloat(input.value) || 0; });
        sum = Math.round(sum * 100) / 100;

        total.textContent = sum + '% of working time';
        if (sum > 100) { total.dataset.over = ''; } else { delete total.dataset.over; }
    }

    form.addEventListener('input', function (event) {
        if (event.target.matches('[data-duty-percent]')) { totalDuties(); }
    });

    totalDuties();
})();
</script>
@endpush
