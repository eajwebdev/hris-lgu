{{--
    Sidebar for layouts/app.

    Three states, all driven from <html>:
      - default        full width, inset on the left (desktop)
      - rail:          desktop, collapsed to icons   [data-sidebar="collapsed"]
      - drawer:        phones, slid in over the page [data-drawer="open"]

    The entries are $menu, built by sidebar_menu() in layouts/app.
--}}
<aside id="appSidebar" aria-label="Main navigation"
       class="fixed inset-y-0 left-0 isolate z-40 flex w-64 -translate-x-full flex-col overflow-hidden bg-forest-900 text-cream transition-[translate,width] duration-200 drawer:translate-x-0 lg:inset-y-2.5 lg:left-2.5 lg:translate-x-0 lg:rounded-3xl rail:w-19">

    {{-- Backdrop photo under a green wash. The wash is heaviest at the top,
         where the photo is bright sky and the brand and account sit on it; the
         solid forest-900 on the <aside> is what shows if the image is missing. --}}
    <img src="{{ asset('images/sidebarpic.jpeg') }}" alt="" aria-hidden="true"
         class="pointer-events-none absolute inset-0 -z-20 size-full object-cover">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-linear-to-b from-forest-950/95 via-forest-950/85 to-forest-900/75"></div>

    <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3 px-5 pt-5 pb-1 rail:justify-center rail:px-0">
        <img src="{{ asset('Uploads/logo.png') }}" alt="Municipality of Mabinay Official Seal" class="size-10 shrink-0" width="40" height="40">
        <span class="leading-tight rail:hidden">
            <strong class="block font-display text-lg font-semibold tracking-tight">HRIS</strong>
            <span class="block text-xs text-cream/60">LGU Mabinay</span>
        </span>
    </a>

    <div class="mx-3 mt-4 flex shrink-0 items-center gap-3 rounded-2xl bg-cream/8 p-3 rail:mx-auto rail:bg-transparent rail:p-0">
        <img src="{{ $accountPhoto }}" alt="" class="size-10 shrink-0 rounded-full object-cover ring-2 ring-cream/20">
        <span class="min-w-0 leading-tight rail:hidden">
            <span class="block truncate font-medium">{{ $accountName }}</span>
            <span class="block truncate text-xs text-cream/60">{{ $accountRole }}</span>
        </span>
    </div>

    <nav class="mt-2 min-h-0 flex-1 overflow-y-auto px-3 pb-4 [scrollbar-width:thin]">
        @foreach($menu as $section)
            {{-- Collapsed, the section names go and a rule stands in for them. --}}
            <p class="px-3 pt-5 pb-1.5 text-xs font-medium text-cream/45 rail:hidden">{{ $section['label'] }}</p>

            <ul class="space-y-0.5 {{ $loop->first ? 'rail:mt-3' : 'rail:mt-2 rail:border-t rail:border-cream/10 rail:pt-2' }}">
                @foreach($section['items'] as $item)
                    <li>
                        @if($item['children'])
                            @php
                                // Open on any of its pages: the parent's own
                                // "active" misses some (position-descriptions, psb).
                                $treeOpen = $item['active'] || collect($item['children'])->contains('active', true);
                            @endphp
                            {{-- Opens the rail first when collapsed: the submenu
                                 has nowhere to go in a strip of icons. --}}
                            <details class="group/tree" data-sidebar-tree @if($treeOpen) open @endif>
                                <summary title="{{ $item['title'] }}"
                                         class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-colors hover:bg-cream/8 hover:text-cream focus-visible:outline-2 focus-visible:outline-sun-500 rail:justify-center rail:px-0 [&::-webkit-details-marker]:hidden {{ $treeOpen ? 'text-cream' : 'text-cream/75' }}">
                                    <i class="{{ $item['icon'] }} w-5 shrink-0 text-center"></i>
                                    <span class="flex-1 rail:hidden">{{ $item['label'] }}</span>
                                    {{-- Font Awesome's own stylesheet is unlayered, so a display
                                         utility on the <i> itself loses to it. Hide the wrapper. --}}
                                    <span class="rail:hidden"><i class="fas fa-angle-right text-xs text-cream/50 transition-transform group-open/tree:rotate-90"></i></span>
                                </summary>

                                <ul class="mt-0.5 mb-1 ml-5.5 space-y-0.5 border-l border-cream/15 pl-3 rail:hidden">
                                    @foreach($item['children'] as $child)
                                        <li>
                                            <a href="{{ $child['url'] }}" @if($child['active']) aria-current="page" @endif
                                               class="block rounded-lg px-3 py-2 transition-colors hover:bg-cream/8 hover:text-cream focus-visible:outline-2 focus-visible:outline-sun-500 {{ $child['active'] ? 'bg-cream/12 font-medium text-cream' : 'text-cream/70' }}">
                                                {{ $child['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @else
                            <a href="{{ $item['url'] }}" title="{{ $item['title'] }}" @if($item['active']) aria-current="page" @endif
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-sun-500 rail:justify-center rail:px-0 {{ $item['active'] ? 'bg-cream text-forest-900' : 'text-cream/75 hover:bg-cream/8 hover:text-cream' }}">
                                <i class="{{ $item['icon'] }} w-5 shrink-0 text-center"></i>
                                <span class="rail:hidden">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>
</aside>
