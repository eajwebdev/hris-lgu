{{--
    A generated PDF, in a frame, for the report screens on layouts/app (DTR,
    Logs, Tardiness): a form above asks for it, this shows it.

    Building the PDF takes a second or two on the server. Without feedback the
    page just sits there and looks frozen, so the frame is covered by a spinner
    until the document has actually loaded, and the form's button says so from
    the moment it is pressed.

    The form that asks for the PDF is marked data-generates-pdf and uses
    partials/generate-button.

      $pdfUrl    where the PDF comes from, or null before anything is generated
      $working   what the spinner says ("Generating the DTR")
      $prompt    what the empty frame says
--}}
<section class="relative mt-5 overflow-hidden rounded-2xl border border-line bg-surface">
    @if($pdfUrl)
        <div data-pdf-loader class="absolute inset-0 z-10 grid place-items-center bg-surface">
            <div class="text-center text-ink/60">
                <span class="mx-auto block size-9 animate-spin rounded-full border-[3px] border-line border-t-forest-600"></span>
                <p class="mt-3">{{ $working }}&hellip;</p>
            </div>
        </div>
        <iframe data-pdf-frame src="{{ $pdfUrl }}" title="{{ $working }}"
                class="block h-[max(34rem,calc(100dvh-19rem))] w-full border-0 bg-white"></iframe>
    @else
        <div class="grid h-[max(22rem,calc(100dvh-19rem))] place-items-center px-6 text-center">
            <div>
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-forest-100 text-xl text-forest-700"><i class="fas fa-file-pdf"></i></span>
                <p class="mt-4 text-ink/60">{{ $prompt }}</p>
            </div>
        </div>
    @endif
</section>

@push('scripts')
<script>
    (function () {
        // Uncover the frame once the PDF has actually rendered in it.
        var frame = document.querySelector('[data-pdf-frame]');
        var loader = document.querySelector('[data-pdf-loader]');
        if (frame && loader) {
            frame.addEventListener('load', function () { loader.hidden = true; });
        }

        // The form reloads the page before the PDF is built, so tell the user
        // the click registered instead of leaving the button looking idle.
        document.querySelectorAll('form[data-generates-pdf]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');
                if (!button || button.dataset.busy) return;
                button.dataset.busy = '1';
                button.disabled = true;
                button.querySelector('[data-idle]').hidden = true;
                button.querySelector('[data-busy-label]').hidden = false;
            });
        });
    })();
</script>
@endpush
