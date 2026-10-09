@extends('layouts.app')

@php
    // Personal Information, the first page of an employee's Personal Data
    // Sheet. HR opens it for anyone (pds/personal-info/{id}); an employee
    // opens their own (pds). There is no Save button: each field is sent to
    // EmployeeController::employeeUpdate on its own the moment it changes,
    // named by the employees column it fills (emp/partials/pds-autosave).
    //
    // The fields are described here and drawn by emp/partials/field.

    $isStaff = $guard == 'web';
    $fullName = trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname)));

    $same = fn (array $values) => array_combine($values, $values);

    $countries = [
        'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan',
        'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana',
        'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia', 'Cameroon', 'Canada', 'Central African Republic', 'Chad', 'Chile',
        'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Democratic Republic of the Congo', 'Denmark',
        'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia',
        'Fiji', 'Finland', 'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana',
        'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy', 'Ivory Coast', 'Jamaica', 'Japan',
        'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein',
        'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico',
        'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands',
        'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Korea', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau', 'Panama', 'Papua New Guinea',
        'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia',
        'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone',
        'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Korea', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan',
        'Suriname', 'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago',
        'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay',
        'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe',
    ];

    // Dual citizenship is the only case that names a country and how the
    // second citizenship came about; a Filipino's are left blank and locked.
    $isDual = $employee->citizenship == 2;

    $nameFields = [
        ['name' => 'lname', 'label' => 'Last name'],
        ['name' => 'fname', 'label' => 'First name'],
        ['name' => 'mname', 'label' => 'Middle name'],
        ['name' => 'suffix', 'label' => 'Suffix', 'type' => 'select',
            'options' => ['' => 'N/A'] + $same(['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V'])],
        ['name' => 'prefix', 'label' => 'Prefix', 'type' => 'select',
            'options' => ['' => 'N/A'] + $same(['Ph.D.', 'Atty.', 'Dr.', 'Engr.', 'RChE.', 'J.D.', 'M.S.W.', 'C.P.A.', 'C.L.E.A.', 'DIT.'])],
        ['name' => 'title_prefix', 'label' => 'Title prefix', 'type' => 'select',
            'options' => ['' => 'N/A'] + $same(['MBA', 'DPA', 'MPA', 'MD', 'RN', 'LLM', 'MSW', 'CPA', 'DIT', 'CNA', 'CHRP'])],
    ];

    $birthFields = [
        ['name' => 'bdate', 'label' => 'Birth date', 'type' => 'date'],
        // Worked out from the birth date, here and again by the server when
        // the date is saved; never sent on its own.
        ['name' => 'age', 'label' => 'Age', 'readonly' => true, 'placeholder' => 'From the birth date',
            'value' => $employee->bdate ? \Carbon\Carbon::parse($employee->bdate)->age : ''],
        ['name' => 'b_place', 'label' => 'Birth place'],
        // Item 5 is "SEX AT BIRTH" on the 2026 PDS.
        ['name' => 'sex', 'label' => 'Sex at birth', 'type' => 'select', 'prompt' => 'Select',
            'options' => $same(['Male', 'Female'])],
        // Solo Parent is listed on the 2026 PDS (item 6), and the printed
        // form ticks a box for this exact value.
        ['name' => 'civil_status', 'label' => 'Civil status', 'type' => 'select', 'prompt' => 'Select',
            'options' => $same(['Single', 'Married', 'Separated', 'Widowed', 'Solo Parent']) + ['Other' => 'Other/s']],
    ];

    $bodyFields = [
        // The two heights and the two weights are one measurement each: the
        // page fills in the other unit as one is typed, and the server stores
        // both when either is saved.
        ['name' => 'height_cm', 'label' => 'Height (cm)'],
        ['name' => 'height_m', 'label' => 'Height (m)'],
        ['name' => 'weight_kg', 'label' => 'Weight (kg)'],
        ['name' => 'weight_lb', 'label' => 'Weight (lb)'],
        ['name' => 'b_type', 'label' => 'Blood type', 'type' => 'select',
            'options' => ['' => 'N/A'] + $same(['A+', 'A-', 'AB+', 'AB-', 'B+', 'B-', 'O+', 'O-'])],
    ];

    // Plantilla number, employment status and salary are HR's to set; an
    // employee does not see them here.
    $workFields = array_values(array_filter([
        ['name' => 'date_hired', 'label' => 'Date hired', 'type' => 'date'],
        $isStaff ? ['name' => 'item_no', 'label' => 'Item / plantilla no.'] : null,
        $isStaff ? ['name' => 'emp_status', 'label' => 'Employment status', 'type' => 'select', 'prompt' => 'Select',
            'options' => $stat->pluck('status_name', 'id')->all()] : null,
        ['name' => 'position', 'label' => 'Position'],
        ['name' => 'emp_dept', 'label' => 'Department / office', 'type' => 'select', 'prompt' => 'Select',
            'options' => $offices->pluck('office_name', 'id')->all()],
        ['name' => 'supervisor', 'label' => 'Immediate supervisor', 'type' => 'select',
            'options' => [0 => 'None'] + $supervisor->mapWithKeys(fn ($sup) => [$sup->id => strtoupper(trim($sup->lname . ' ' . $sup->fname . ' ' . $sup->mname))])->all()],
        $isStaff ? ['name' => 'emp_salary', 'label' => 'Salary'] : null,
    ]));

    // SSS and UMID are separate numbers. They once shared one box; anything
    // typed there still prints at item 10 of the PDS, because that row falls
    // back to the SSS number whenever no UMID has been recorded.
    $numberFields = [
        ['name' => 'gsis', 'label' => 'GSIS'],
        ['name' => 'pagibig', 'label' => 'Pag-IBIG'],
        ['name' => 'philhealth', 'label' => 'PhilHealth'],
        ['name' => 'sss', 'label' => 'SSS no.'],
        ['name' => 'umid', 'label' => 'UMID ID no.'],
        ['name' => 'philsys', 'label' => 'PhilSys no. (PSN)'],
        ['name' => 'tin', 'label' => 'TIN'],
    ];

    $contactFields = [
        ['name' => 'telephone', 'label' => 'Telephone number'],
        ['name' => 'mobile', 'label' => 'Mobile number', 'placeholder' => '0912-345-6789'],
        // The address is also what the employee signs in with, so it is HR's
        // to change: saving it moves the username along with it.
        ['name' => 'org_email', 'label' => 'Email address', 'type' => 'email', 'readonly' => !$isStaff,
            'hint' => $isStaff ? 'Also the username this employee signs in with.' : 'Only HR can change this.'],
    ];

    // The two addresses are the same eight fields under two column prefixes.
    // The controller hands over the provinces of the saved region, but only
    // the saved city and barangay themselves; the page fetches their
    // neighbours once it has loaded.
    $addresses = [
        'Residential address' => ['prefix' => 'add_', 'provinces' => $hprovinces, 'cities' => $hcities, 'barangay' => $hbarangays],
        'Permanent address' => ['prefix' => 'padd_', 'provinces' => $gprovinces, 'cities' => $gcities, 'barangay' => $gbarangays],
    ];

    $card = 'rounded-2xl border border-line bg-surface p-5 sm:p-6';
    $heading = 'font-display text-lg font-semibold tracking-tight';
    $subheading = 'mt-6 border-t border-line pt-5 font-medium';
    $grid = 'mt-4 grid gap-4 @md:grid-cols-2 @2xl:grid-cols-3 @4xl:grid-cols-4';
    $radio = 'inline-flex cursor-pointer items-center gap-2 has-disabled:cursor-not-allowed has-disabled:text-ink/40';
@endphp

@section('breadcrumb', $isStaff ? $fullName : 'Personal Information')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Personal information', 'autosaves' => true])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <form id="pdsForm" data-save-url="{{ route('employeeUpdate') }}" data-employee="{{ $empid }}" novalidate autocomplete="off" class="@container space-y-5">
        <section class="{{ $card }}">
            <h2 class="{{ $heading }}">Personal information</h2>

            <div class="{{ $grid }}">
                @foreach($nameFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec])
                @endforeach
            </div>

            <h3 class="{{ $subheading }}">Birth and civil status</h3>
            <div class="{{ $grid }}">
                @foreach($birthFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec])
                @endforeach
            </div>

            <h3 class="{{ $subheading }}">Citizenship</h3>
            <div class="{{ $grid }}" data-citizenship>
                @include('emp.partials.field', ['spec' => [
                    'name' => 'citizenship', 'label' => 'Citizenship', 'type' => 'select', 'prompt' => 'Select',
                    'options' => [1 => 'Filipino', 2 => 'Dual citizenship'],
                ]])

                <fieldset class="@md:col-span-2">
                    <legend class="block text-xs font-medium text-ink/60">Dual citizenship acquired</legend>
                    <div class="mt-1 flex min-h-10 flex-wrap items-center gap-x-6 gap-y-1">
                        @foreach([1 => 'By birth', 2 => 'By naturalization'] as $category => $wording)
                            <label class="{{ $radio }}">
                                <input type="radio" name="c_category" value="{{ $category }}" data-save data-dual
                                       class="size-4 accent-forest-600" @checked($employee->c_category == $category) @disabled(!$isDual)>
                                {{ $wording }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                @include('emp.partials.field', ['spec' => [
                    'name' => 'country', 'label' => 'Country of the other citizenship', 'type' => 'select', 'prompt' => 'Select',
                    'options' => $same($countries), 'data' => ['dual' => ''],
                ]])
            </div>

            <h3 class="{{ $subheading }}">Height, weight and blood type</h3>
            <div class="{{ $grid }}">
                @foreach($bodyFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec])
                @endforeach
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $heading }}">Employment</h2>

            <div class="{{ $grid }}">
                @foreach($workFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec])
                @endforeach
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $heading }}">Government numbers and contact</h2>

            <div class="{{ $grid }}">
                @foreach($numberFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec])
                @endforeach
            </div>

            <h3 class="{{ $subheading }}">Contact</h3>
            <div class="{{ $grid }}">
                @foreach($contactFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec])
                @endforeach
            </div>
        </section>

        @foreach($addresses as $addressTitle => $address)
            @php
                $prefix = $address['prefix'];
            @endphp
            <section class="{{ $card }}" data-address>
                <h2 class="{{ $heading }}">{{ $addressTitle }}</h2>

                <div class="{{ $grid }}">
                    @foreach([
                        ['name' => $prefix . 'region', 'label' => 'Region', 'level' => 'region', 'options' => $regions->pluck('name', 'region_id')->all()],
                        ['name' => $prefix . 'prov', 'label' => 'Province', 'level' => 'prov', 'options' => $address['provinces']->pluck('name', 'province_id')->all()],
                        ['name' => $prefix . 'city', 'label' => 'City / municipality', 'level' => 'city', 'options' => $address['cities']->pluck('name', 'city_id')->all()],
                        ['name' => $prefix . 'brgy', 'label' => 'Barangay', 'level' => 'brgy', 'options' => $address['barangay'] ? [$address['barangay']->id => $address['barangay']->name] : []],
                    ] as $place)
                        @include('emp.partials.field', ['spec' => $place + ['type' => 'select', 'prompt' => 'Select', 'data' => ['address-level' => $place['level']]]])
                    @endforeach

                    @foreach([
                        ['name' => $prefix . 'block', 'label' => 'House / block / lot no.'],
                        ['name' => $prefix . 'street', 'label' => 'Street'],
                        ['name' => $prefix . 'village', 'label' => 'Subdivision / village'],
                        ['name' => $prefix . 'zcode', 'label' => 'ZIP code', 'type' => 'number'],
                    ] as $spec)
                        @include('emp.partials.field', ['spec' => $spec])
                    @endforeach
                </div>
            </section>
        @endforeach
    </form>
</div>

@include('emp.partials.pds-autosave')
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('pdsForm');
    var field = function (name) { return form.elements[name]; };

    // Names are stored in capitals; once one is saved, show what was kept.
    form.addEventListener('pds:saved', function (event) {
        if (['lname', 'fname', 'mname'].indexOf(event.target.name) !== -1) { event.target.value = event.target.value.toUpperCase(); }
    });

    /* ----------------------------------------------------------------- age */
    field('bdate').addEventListener('change', function () {
        var born = new Date(this.value);
        var today = new Date();
        var age = today.getFullYear() - born.getFullYear();
        if (today.getMonth() < born.getMonth() || (today.getMonth() === born.getMonth() && today.getDate() < born.getDate())) { age--; }
        field('age').value = isNaN(age) || age < 0 ? '' : age;
    });

    /* --------------------------------------------------------- citizenship */
    // Choosing Filipino empties and locks the dual-citizenship answers; the
    // server clears the same two columns when it saves the choice.
    var dual = Array.prototype.slice.call(form.querySelectorAll('[data-dual]'));

    function lockDual(locked, empty) {
        dual.forEach(function (control) {
            control.disabled = locked;
            if (!empty) return;
            if (control.type === 'radio') { control.checked = false; } else { control.selectedIndex = 0; }
        });
    }

    field('citizenship').addEventListener('change', function () { lockDual(this.value !== '2', this.value !== '2'); });
    lockDual(field('citizenship').value !== '2', false);

    /* --------------------------------------------------- height and weight */
    // Typing one unit fills in the other. Only the one typed is sent; the
    // server works the other out again for itself.
    function paired(from, to, convert) {
        field(from).addEventListener('input', function () {
            var amount = parseFloat(this.value);
            field(to).value = isNaN(amount) ? '' : convert(amount);
        });
    }

    paired('height_cm', 'height_m', function (cm) { return (cm / 100).toFixed(2); });
    paired('height_m', 'height_cm', function (m) { return Math.round(m * 100); });
    paired('weight_kg', 'weight_lb', function (kg) { return Math.round(kg * 2.20462); });
    paired('weight_lb', 'weight_kg', function (lb) { return Math.round(lb / 2.20462); });

    /* -------------------------------------------------------------- mobile */
    // 0912-345-6789 as it is typed.
    field('mobile').addEventListener('input', function () {
        var digits = this.value.replace(/\D/g, '').substring(0, 11);
        this.value = [digits.substring(0, 4), digits.substring(4, 7), digits.substring(7, 11)].filter(Boolean).join('-');
    });

    /* ----------------------------------------------------------- addresses */
    // Region, province, city, barangay: choosing one empties everything
    // beneath it and fetches the list for the next. Each list is a prompt
    // followed by the places.
    var levels = ['region', 'prov', 'city', 'brgy'];
    var lists = {
        prov: { url: "{{ route('getProvinces', ['regionId' => ':id']) }}", key: 'province_id' },
        city: { url: "{{ route('getCities', ['provinceId' => ':id']) }}", key: 'city_id' },
        brgy: { url: "{{ route('getBarangays', ['cityId' => ':id']) }}", key: 'id' }
    };

    Array.prototype.forEach.call(form.querySelectorAll('[data-address]'), function (address) {
        var selects = {};
        levels.forEach(function (level) { selects[level] = address.querySelector('[data-address-level="' + level + '"]'); });

        // Counts the fetches, so a slow answer to an earlier choice cannot
        // overwrite the list for a later one.
        var asked = 0;

        function fill(level, places, keep) {
            var select = selects[level];
            select.length = 1;
            places.forEach(function (place) { select.add(new Option(place.name, place[lists[level].key])); });
            select.value = keep || '';
            if (select.selectedIndex < 1) { select.selectedIndex = 0; }
            select.disabled = !places.length;
        }

        function fetchList(level, parent) {
            return fetch(lists[level].url.replace(':id', encodeURIComponent(parent)), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (response) { return response.ok ? response.json() : Promise.reject(); });
        }

        address.addEventListener('change', function (event) {
            var at = levels.indexOf(event.target.dataset.addressLevel);
            if (at === -1) return;

            levels.slice(at + 1).forEach(function (level) { fill(level, [], ''); });

            var next = levels[at + 1];
            if (!next || !event.target.value) return;

            var mine = ++asked;
            fetchList(next, event.target.value)
                .then(function (places) { if (mine === asked) { fill(next, places, ''); } })
                .catch(function () { hrisToast('error', 'That list could not be loaded. Choose it again.'); });
        });

        // As loaded, a list with nothing to choose from is locked, and the
        // city and barangay lists hold only the saved place. Bring in the
        // rest, keeping what is chosen.
        ['prov', 'city', 'brgy'].forEach(function (level) {
            var select = selects[level];
            var parent = selects[levels[levels.indexOf(level) - 1]];
            select.disabled = select.length < 2;

            if (level === 'prov' || !parent.value) return;

            var mine = asked;
            fetchList(level, parent.value)
                .then(function (places) { if (mine === asked && places.length) { fill(level, places, select.value); } })
                .catch(function () {});
        });
    });
})();
</script>
@endpush
