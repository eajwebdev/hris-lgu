{{-- Submit button for a form that asks for a PDF. partials/pdf-preview swaps
     the two labels when the form is sent. --}}
<button type="submit"
        class="h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 disabled:cursor-default disabled:opacity-80 dark:bg-forest-600 dark:hover:bg-forest-500">
    <span data-idle><i class="fas fa-file-pdf mr-1"></i> Generate</span>
    <span data-busy-label hidden class="inline-flex items-center gap-2">
        <span class="size-3.5 animate-spin rounded-full border-2 border-cream/40 border-t-cream"></span> Generating&hellip;
    </span>
</button>
