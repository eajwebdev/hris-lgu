{{-- AdminLTE rendering of the sidebar. What is in the menu, who sees it and
     which link is lit all come from sidebar_menu() in app/Helpers/helpers.php,
     which layouts/app-sidebar (the Tailwind shell) draws from too. --}}
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        @foreach(sidebar_menu($guard) as $section)
            <li class="nav-header">{{ $section['label'] }}</li>

            @foreach($section['items'] as $item)
                @if($item['children'])
                    <li class="nav-item has-treeview {{ $item['active'] ? 'menu-open' : '' }}">
                        <a href="{{ $item['url'] }}" title="{{ $item['title'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}">
                            <i class="nav-icon {{ $item['icon'] }}"></i>
                            <p>
                                {{ $item['label'] }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @foreach($item['children'] as $child)
                                <li class="nav-item">
                                    <a href="{{ $child['url'] }}" class="nav-link {{ $child['active'] ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>{{ $child['label'] }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a href="{{ $item['url'] }}" title="{{ $item['title'] }}"
                           class="nav-link {{ $item['active'] ? 'active' : '' }}">
                            <i class="nav-icon {{ $item['icon'] }}"></i>
                            <p>{{ $item['label'] }}</p>
                        </a>
                    </li>
                @endif
            @endforeach
        @endforeach
    </ul>
</nav>
