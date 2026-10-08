{{--
    Print preview for the SPMS lists: one dialog with one frame, pointed at a
    form when it is opened.

    A button opens it with
        data-print-url    the form's page (opened with ?embed=1 in the frame,
                          and as it is in a new tab)
        data-print-title  what the dialog is headed

    The frame is only given its address on opening and is emptied on closing.
    That matters for the IPCR rating form in particular: asking for it starts
    the employee's IPCR for that period if there is none yet, so it must not
    load until somebody actually asks to see it.
--}}
<dialog id="printDialog" aria-labelledby="printDialogTitle"
        class="m-auto h-[calc(100dvh-2rem)] w-[min(72rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60 open:flex">
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-line px-5 py-3">
        <h2 class="min-w-0 truncate font-display text-lg font-semibold tracking-tight" id="printDialogTitle"></h2>

        <div class="flex items-center gap-2">
            <button type="button" data-print-now class="h-9 cursor-pointer rounded-xl bg-forest-900 px-4 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">
                <i class="fas fa-print mr-1"></i> Print
            </button>
            <a data-print-tab href="#" target="_blank" rel="noopener" class="inline-flex h-9 items-center rounded-xl border border-line px-4 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                Open in new tab
            </a>
            <button type="button" data-dialog-close aria-label="Close" class="-mr-2 grid size-9 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </div>

    <iframe data-print-frame title="Form preview" class="min-h-0 w-full flex-1 border-0 bg-white"></iframe>
</dialog>

@push('scripts')
<script>
    (function () {
        var dialog = document.getElementById('printDialog');
        var frame = dialog.querySelector('[data-print-frame]');

        document.addEventListener('click', function (event) {
            var opener = event.target.closest('[data-print-url]');
            if (!opener) return;

            var url = opener.dataset.printUrl;
            dialog.querySelector('#printDialogTitle').textContent = opener.dataset.printTitle;
            dialog.querySelector('[data-print-tab]').href = url;
            frame.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'embed=1';
            dialog.showModal();
        });

        dialog.querySelector('[data-print-now]').addEventListener('click', function () {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        });

        dialog.addEventListener('close', function () { frame.removeAttribute('src'); });
    })();
</script>
@endpush
