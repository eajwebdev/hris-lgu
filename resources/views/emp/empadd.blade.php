@extends('layouts.app')

@php
    // Add Employee. One ordinary form posted to EmployeeController::empCreate;
    // the fields are described here and drawn by emp/partials/add-field.

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

    $personalFields = [
        ['name' => 'date_hired', 'label' => 'Date Hired', 'type' => 'date', 'icon' => 'fa-calendar-days'],
        ['name' => 'item_no', 'label' => 'Item / Plantilla No.', 'icon' => 'fa-file-lines', 'placeholder' => 'Enter item no.'],
        ['name' => 'emp_ID', 'label' => 'Employee ID', 'icon' => 'fa-user', 'placeholder' => 'e.g. 1234-56', 'required' => true,
            'status' => 'empIdStatus', 'attrs' => ['id' => 'emp_ID']],
        ['name' => 'lname', 'label' => 'Last Name', 'icon' => 'fa-font', 'placeholder' => 'Enter last name', 'required' => true],
        ['name' => 'fname', 'label' => 'First Name', 'icon' => 'fa-font', 'placeholder' => 'Enter first name', 'required' => true],
        ['name' => 'mname', 'label' => 'Middle Name', 'icon' => 'fa-font', 'placeholder' => 'Enter middle name'],

        ['name' => 'suffix', 'label' => 'Suffix', 'type' => 'select', 'prompt' => 'Select suffix',
            'options' => $same(['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V'])],
        ['name' => 'prefix', 'label' => 'Prefix', 'type' => 'select', 'prompt' => 'Select prefix',
            'options' => $same(['Atty.', 'Dr.', 'Eng.', 'Mr.', 'Mrs.', 'Ms.', 'Not Applicable'])],
        ['name' => 'title_prefix', 'label' => 'Title Prefix', 'icon' => 'fa-briefcase', 'placeholder' => 'e.g. MBA / DPA / MD etc.'],
        ['name' => 'bdate', 'label' => 'Birth Date', 'type' => 'date', 'icon' => 'fa-calendar-days'],
        // Worked out from the birth date.
        ['name' => 'age', 'label' => 'Age', 'icon' => 'fa-hashtag', 'placeholder' => 'From the birth date', 'readonly' => true],
        ['name' => 'b_place', 'label' => 'Birth Place', 'icon' => 'fa-location-dot', 'placeholder' => 'Enter birth place'],

        ['name' => 'sex', 'label' => 'Sex', 'type' => 'select', 'prompt' => 'Select sex', 'required' => true,
            'options' => $same(['Male', 'Female'])],
        ['name' => 'civil_status', 'label' => 'Civil Status', 'type' => 'select', 'prompt' => 'Select civil status',
            'options' => $same(['Single', 'Married', 'Separated', 'Widowed']) + ['Other' => 'Other/s']],
        ['name' => 'citizenship', 'label' => 'Citizenship', 'type' => 'select', 'prompt' => 'Select citizenship',
            'options' => [1 => 'Filipino', 2 => 'Dual Citizenship']],
    ];

    // After the citizenship answers, which are drawn by hand between them.
    $assignmentFields = [
        ['name' => 'emp_status', 'label' => 'Employment Status', 'type' => 'select', 'prompt' => 'Select status', 'icon' => 'fa-user-check', 'required' => true,
            'options' => $stat->pluck('status_name', 'id')->all()],
        ['name' => 'position', 'label' => 'Position', 'icon' => 'fa-user-tie', 'placeholder' => 'Enter position', 'required' => true],
        ['name' => 'emp_dept', 'label' => 'Department/Office', 'type' => 'select', 'prompt' => 'Select department/office', 'icon' => 'fa-building', 'required' => true,
            'options' => $offices->pluck('office_name', 'id')->all()],
        ['name' => 'supervisor', 'label' => 'Immediate Supervisor', 'type' => 'select', 'prompt' => 'Select supervisor', 'icon' => 'fa-sitemap',
            'options' => $supervisor->mapWithKeys(fn ($sup) => [$sup->id => strtoupper(trim($sup->lname . ' ' . $sup->fname . ' ' . ($sup->mname ? substr($sup->mname, 0, 1) . '.' : '')))])->all()],

        // The two heights and the two weights are one measurement each: the
        // page fills in the other unit as one is typed.
        ['name' => 'height_cm', 'label' => 'Height (cm)', 'type' => 'number', 'icon' => 'fa-ruler-vertical', 'placeholder' => 'e.g. 170', 'attrs' => ['step' => 'any', 'min' => '0']],
        ['name' => 'height_ft', 'label' => 'Height (ft)', 'type' => 'number', 'icon' => 'fa-ruler-vertical', 'placeholder' => 'e.g. 5.6', 'attrs' => ['step' => 'any', 'min' => '0']],
        ['name' => 'weight_kg', 'label' => 'Weight (kg)', 'type' => 'number', 'icon' => 'fa-weight-scale', 'placeholder' => 'e.g. 65', 'attrs' => ['step' => 'any', 'min' => '0']],
        ['name' => 'weight_lb', 'label' => 'Weight (lbs)', 'type' => 'number', 'icon' => 'fa-weight-scale', 'placeholder' => 'e.g. 143', 'attrs' => ['step' => 'any', 'min' => '0']],
        ['name' => 'b_type', 'label' => 'Blood Type', 'type' => 'select', 'prompt' => 'Select blood type', 'icon' => 'fa-droplet',
            'options' => $same(['A+', 'A-', 'AB+', 'AB-', 'B+', 'B-', 'O+', 'O-'])],

        ['name' => 'gsis', 'label' => 'GSIS', 'icon' => 'fa-id-card', 'placeholder' => 'Enter GSIS No.'],
        ['name' => 'pagibig', 'label' => 'PAGIBIG', 'icon' => 'fa-house', 'placeholder' => 'Enter PAGIBIG No.'],
        ['name' => 'philhealth', 'label' => 'PhilHealth', 'icon' => 'fa-notes-medical', 'placeholder' => 'Enter PhilHealth No.'],
        ['name' => 'sss', 'label' => 'SSS', 'icon' => 'fa-shield-halved', 'placeholder' => 'Enter SSS No.'],

        ['name' => 'tin', 'label' => 'TIN', 'icon' => 'fa-receipt', 'placeholder' => 'Enter TIN No.', 'span' => 3],
        ['name' => 'telephone', 'label' => 'Telephone Number', 'icon' => 'fa-phone', 'placeholder' => 'Enter telephone number', 'span' => 3],
        ['name' => 'org_email', 'label' => 'Email Address', 'type' => 'email', 'icon' => 'fa-envelope', 'placeholder' => 'Enter email address', 'span' => 3],
        ['name' => 'mobile', 'label' => 'Mobile Number', 'icon' => 'fa-mobile-screen', 'placeholder' => '0912-345-6789', 'span' => 3],
    ];

    // The two addresses are the same eight fields under two column prefixes.
    // Only the regions come with the page; each list below a region is
    // fetched once the one above it is chosen.
    $addresses = [
        'add_' => ['title' => 'Residential Address', 'about' => 'Current home address of the employee.', 'icon' => 'fa-house'],
        'padd_' => ['title' => 'Permanent Address', 'about' => 'Permanent address, if different from the residential address.', 'icon' => 'fa-location-dot'],
    ];

    $card = 'rounded-2xl border border-line bg-surface p-5 shadow-sm shadow-forest-950/5 sm:p-6';
    $badge = 'grid size-11 shrink-0 place-items-center rounded-xl bg-forest-700 text-lg text-white shadow-sm dark:bg-forest-600';
    $heading = 'font-display text-lg font-bold tracking-tight';
    $about = 'text-xs text-ink/55';
    $radio = 'inline-flex cursor-pointer items-center gap-2 has-disabled:cursor-not-allowed has-disabled:text-ink/40';
@endphp

@section('breadcrumb', 'Add Employee')

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Add Employee</h1>
            <p class="mt-1 max-w-2xl text-white/85">Create the employee record, assignment, contact details, and account information.</p>
        </div>

        <a href="{{ route('emp_list') }}"
           class="inline-flex h-10 items-center gap-2 rounded-xl bg-white px-4 font-semibold text-forest-900 shadow-md shadow-forest-950/25 transition-colors [text-shadow:none] hover:bg-cream focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            <i class="fas fa-arrow-left"></i> Employee list
        </a>
    </div>
@endsection

@section('body')
<form id="addEmployeeForm" action="{{ route('empCreate') }}" method="POST" class="@container space-y-5">
    @csrf

    <section class="{{ $card }}">
        <header class="flex items-center gap-3.5">
            <span class="{{ $badge }}"><i class="fas fa-id-card"></i></span>
            <div>
                <h2 class="{{ $heading }}">Personal Information</h2>
                <p class="{{ $about }}">Basic information about the employee.</p>
            </div>
        </header>

        <div class="mt-5 grid gap-4 @md:grid-cols-2 @3xl:grid-cols-4 @5xl:grid-cols-12">
            @foreach($personalFields as $spec)
                @include('emp.partials.add-field', ['spec' => $spec])
            @endforeach

            {{-- Only dual citizenship names how the second came about and the
                 other country; for a Filipino both stay locked and empty. --}}
            <fieldset class="@5xl:col-span-2">
                <legend class="block text-[13px] font-semibold text-ink">Dual citizenship by</legend>
                <div class="mt-1.5 flex min-h-11 flex-wrap items-center gap-x-5 gap-y-1">
                    @foreach([1 => 'By Birth', 2 => 'By Naturalization'] as $category => $wording)
                        <label class="{{ $radio }}">
                            <input type="radio" name="c_category" value="{{ $category }}" data-dual
                                   class="size-4.5 accent-forest-600" @checked(old('c_category') == $category)>
                            {{ $wording }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            @include('emp.partials.add-field', ['spec' => [
                'name' => 'country', 'label' => 'Country', 'type' => 'select', 'prompt' => 'Select country',
                'options' => $same($countries), 'attrs' => ['data-dual' => ''],
            ]])

            @foreach($assignmentFields as $spec)
                @include('emp.partials.add-field', ['spec' => $spec])
            @endforeach
        </div>
    </section>

    <div class="grid gap-5 @5xl:grid-cols-2">
        @foreach($addresses as $prefix => $address)
            <section class="{{ $card }} @container" data-address="{{ $prefix }}">
                <header class="flex flex-wrap items-center gap-x-3.5 gap-y-3">
                    <span class="{{ $badge }}"><i class="fas {{ $address['icon'] }}"></i></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="{{ $heading }}">{{ $address['title'] }}</h2>
                        <p class="{{ $about }}">{{ $address['about'] }}</p>
                    </div>

                    @if($prefix === 'padd_')
                        <label class="inline-flex cursor-pointer items-center gap-2.5 font-medium">
                            <input type="checkbox" id="sameAddress" class="size-4.5 rounded accent-forest-600">
                            Same as residential address
                        </label>
                    @endif
                </header>

                <div class="mt-5 grid gap-4 transition-opacity @sm:grid-cols-2 @2xl:grid-cols-4" data-address-fields>
                    @foreach([
                        ['name' => $prefix . 'region', 'label' => 'Region', 'icon' => 'fa-map', 'prompt' => 'Select region', 'level' => 'region',
                            'options' => $regions->pluck('name', 'region_id')->all()],
                        ['name' => $prefix . 'prov', 'label' => 'Province', 'icon' => 'fa-map-location-dot', 'prompt' => 'Select province', 'level' => 'prov', 'options' => []],
                        ['name' => $prefix . 'city', 'label' => 'City / Municipality', 'icon' => 'fa-city', 'prompt' => 'Select city / municipality', 'level' => 'city', 'options' => []],
                        ['name' => $prefix . 'brgy', 'label' => 'Barangay', 'icon' => 'fa-map-pin', 'prompt' => 'Select barangay', 'level' => 'brgy', 'options' => []],
                    ] as $place)
                        @include('emp.partials.add-field', ['spec' => $place + [
                            'type' => 'select', 'span' => 1,
                            'attrs' => ['data-address-level' => $place['level'], 'data-old' => (string) old($place['name'])] + ($place['level'] === 'region' ? [] : ['disabled' => '']),
                        ]])
                    @endforeach

                    @foreach([
                        ['name' => $prefix . 'block', 'label' => 'House/Block/Lot No.', 'icon' => 'fa-house', 'placeholder' => 'Enter house/block/lot no.'],
                        ['name' => $prefix . 'street', 'label' => 'Street', 'icon' => 'fa-road', 'placeholder' => 'Enter street'],
                        ['name' => $prefix . 'village', 'label' => 'Subdivision/Village', 'icon' => 'fa-signs-post', 'placeholder' => 'Enter subdivision/village'],
                        ['name' => $prefix . 'zcode', 'label' => 'ZIP Code', 'type' => 'number', 'icon' => 'fa-location-dot', 'placeholder' => 'Enter zip code'],
                    ] as $spec)
                        @include('emp.partials.add-field', ['spec' => $spec + ['span' => 1]])
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('emp_list') }}"
           class="inline-flex h-11 items-center rounded-xl border border-line bg-surface px-6 font-semibold transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            Cancel
        </a>
        <button type="submit"
                class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-xl bg-forest-700 px-6 font-semibold text-white shadow-sm transition-colors hover:bg-forest-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">
            <i class="fas fa-floppy-disk"></i> Save
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('addEmployeeForm');
    var field = function (name) { return form.elements[name]; };

    // A middle name is stored without the full stop of an initial.
    field('mname').addEventListener('input', function () { this.value = this.value.replace(/\./g, ''); });

    /* --------------------------------------------------------- employee ID */
    // Checked against the employees on record a moment after typing stops.
    // A taken ID holds the form back; the server refuses one as well.
    var empId = field('emp_ID');
    var empIdStatus = document.getElementById('empIdStatus');
    var empIdTimer = null;
    var empIdTaken = false;
    var empIdUrl = "{{ route('checkEmpId', ['empID' => ':empID']) }}";

    function sayEmpId(state, message) {
        empIdStatus.textContent = message;
        empIdStatus.className = 'mt-1 text-xs empty:hidden ' + (state === 'taken' ? 'text-red-600' : state === 'free' ? 'text-forest-700' : 'text-ink/55');
        empId.classList.toggle('border-red-500', state === 'taken');
    }

    empId.addEventListener('input', function () {
        var typed = this.value.trim();
        clearTimeout(empIdTimer);
        empIdTaken = false;

        if (!typed) { sayEmpId(null, ''); return; }
        sayEmpId(null, 'Checking availability…');

        empIdTimer = setTimeout(function () {
            fetch(empIdUrl.replace(':empID', encodeURIComponent(typed)), { headers: { 'Accept': 'application/json' } })
                .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
                .then(function (answer) {
                    if (empId.value.trim() !== typed) return;
                    empIdTaken = !!answer.exists;
                    sayEmpId(empIdTaken ? 'taken' : 'free', empIdTaken ? 'This Employee ID already exists.' : 'Employee ID is available.');
                })
                .catch(function () { sayEmpId(null, ''); });
        }, 400);
    });

    form.addEventListener('submit', function (event) {
        if (!empIdTaken) return;
        event.preventDefault();
        sayEmpId('taken', 'This Employee ID already exists. Please use a different one.');
        empId.focus();
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
    var dual = Array.prototype.slice.call(form.querySelectorAll('[data-dual]'));

    function lockDual() {
        var locked = field('citizenship').value !== '2';
        dual.forEach(function (control) {
            control.disabled = locked;
            if (!locked) return;
            if (control.type === 'radio') { control.checked = false; } else { control.value = ''; }
        });
    }

    field('citizenship').addEventListener('change', lockDual);
    lockDual();

    /* --------------------------------------------------- height and weight */
    function paired(from, to, convert) {
        field(from).addEventListener('input', function () {
            var amount = parseFloat(this.value);
            field(to).value = isNaN(amount) ? '' : convert(amount);
        });
    }

    paired('height_cm', 'height_ft', function (cm) { return (cm / 30.48).toFixed(2); });
    paired('height_ft', 'height_cm', function (ft) { return Math.round(ft * 30.48); });
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
    // beneath it and fetches the list for the next.
    var levels = ['region', 'prov', 'city', 'brgy'];
    var lists = {
        prov: { url: "{{ route('getProvinces', ['regionId' => ':id']) }}", key: 'province_id' },
        city: { url: "{{ route('getCities', ['provinceId' => ':id']) }}", key: 'city_id' },
        brgy: { url: "{{ route('getBarangays', ['cityId' => ':id']) }}", key: 'id' }
    };

    function fetchList(level, parent) {
        return fetch(lists[level].url.replace(':id', encodeURIComponent(parent)), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (response) { return response.ok ? response.json() : Promise.reject(); });
    }

    var addresses = {};

    Array.prototype.forEach.call(form.querySelectorAll('[data-address]'), function (address) {
        var selects = {};
        levels.forEach(function (level) { selects[level] = address.querySelector('[data-address-level="' + level + '"]'); });
        addresses[address.dataset.address] = { section: address, selects: selects };

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
            address.dispatchEvent(new Event('address:filled'));
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

        // A form sent back with errors keeps its region; bring back the
        // lists beneath it and what was chosen in each.
        (function restore(at) {
            var parent = selects[levels[at - 1]];
            var level = levels[at];
            if (!level || !parent.value) return;
            fetchList(level, parent.value).then(function (places) {
                fill(level, places, selects[level].dataset.old);
                restore(at + 1);
            }).catch(function () {});
        })(1);
    });

    /* ----------------------------------------- same as residential address */
    // While ticked, the permanent address is a copy of the residential one,
    // kept in step as that changes, and cannot be edited itself. The copies
    // are still ordinary fields, so the form posts both addresses.
    var same = document.getElementById('sameAddress');
    var home = addresses['add_'];
    var lasting = addresses['padd_'];
    var lastingFields = lasting.section.querySelector('[data-address-fields]');

    function copyAddress() {
        if (!same.checked) return;

        levels.forEach(function (level) {
            var from = home.selects[level], to = lasting.selects[level];
            to.length = 0;
            Array.prototype.forEach.call(from.options, function (option) { to.add(new Option(option.text, option.value)); });
            to.value = from.value;
            to.disabled = from.disabled;
        });

        ['block', 'street', 'village', 'zcode'].forEach(function (part) {
            field('padd_' + part).value = field('add_' + part).value;
        });
    }

    same.addEventListener('change', function () {
        lastingFields.inert = same.checked;
        lastingFields.classList.toggle('opacity-60', same.checked);
        copyAddress();
    });

    home.section.addEventListener('input', copyAddress);
    home.section.addEventListener('change', copyAddress);
    home.section.addEventListener('address:filled', copyAddress);
})();
</script>
@endpush
