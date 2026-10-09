@extends('layouts.app')

@php
    // E-Signature, a page of an employee's Personal Data Sheet: the image
    // printed where the sheet and other forms are signed. A PNG, uploaded to
    // PdsController::uploadSignature the moment it is chosen and stored
    // encrypted; $imageData is the current one, or a placeholder.

    $isStaff = $guard == 'web';
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'E-Signature')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'E-signature'])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" id="signature">
        <h2 class="font-display text-lg font-semibold tracking-tight">E-signature</h2>
        <p class="mt-0.5 text-ink/60">Printed where the Personal Data Sheet and other forms are signed.</p>

        <div class="mt-5 grid gap-6 md:grid-cols-2">
            <div>
                <p class="text-xs font-medium text-ink/60">Current signature</p>
                {{-- White in both themes: a signature is dark ink on a
                     transparent or white ground. --}}
                <div class="relative mt-1 aspect-[2/1] overflow-hidden rounded-xl border border-line bg-white">
                    <img src="{{ $imageData }}" alt="The employee's e-signature" data-signature class="absolute inset-3 size-[calc(100%-1.5rem)] object-contain">
                </div>

                <button type="button" data-signature-change
                        class="mt-4 h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 disabled:cursor-wait disabled:opacity-60 dark:bg-forest-600 dark:hover:bg-forest-500">
                    <i class="fas fa-upload mr-1"></i> Upload a signature
                </button>
                <p class="mt-2 text-xs text-ink/55">A PNG image. It replaces the current one as soon as it is chosen.</p>
                <input type="file" data-signature-input hidden accept="image/png">
            </div>

            <div>
                <p class="text-xs font-medium text-ink/60">How to crop it</p>
                <img src="{{ asset('Uploads/esign-note.jpg') }}" alt="Left, correct: the signature fills its frame. Right, wrong: a small signature in the middle of a large empty frame."
                     class="mt-1 w-full rounded-xl border border-line bg-white">
                <p class="mt-2 leading-relaxed text-ink/70">Crop the image to the signature itself. Empty space around it is printed too, and leaves the signature small on the form.</p>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var card = document.getElementById('signature');
    var image = card.querySelector('[data-signature]');
    var button = card.querySelector('[data-signature-change]');
    var input = card.querySelector('[data-signature-input]');

    button.addEventListener('click', function () { input.click(); });

    input.addEventListener('change', function () {
        var file = input.files[0];
        input.value = '';
        if (!file) return;

        if (file.type !== 'image/png') {
            hrisToast('error', 'Only PNG images can be used as a signature.');
            return;
        }

        var body = new FormData();
        body.append('signature', file);
        button.disabled = true;

        fetch("{{ route('uploadSignature', $employee->id) }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (data) {
                    if (!response.ok || !data.success) { return Promise.reject(data); }
                    return data;
                });
            })
            .then(function (data) {
                image.src = data.image_url;
                hrisToast('success', 'The signature has been updated.');
            })
            .catch(function (data) {
                // A refusal lists its reasons by field; anything else has one message.
                var reasons = data && data.errors ? [].concat.apply([], Object.keys(data.errors).map(function (key) { return data.errors[key]; })).join(' ') : (data && data.message);
                hrisToast('error', 'The signature could not be uploaded. ' + (reasons || 'Please try again.'));
            })
            .finally(function () { button.disabled = false; });
    });
})();
</script>
@endpush
