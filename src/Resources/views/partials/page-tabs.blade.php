{{--
    The tab bar of a standalone page: the plugin's pages a viewer can open,
    and the page being shown. $active names the tab that is current; $page
    adds one more tab for a page that is not in the menu, such as a ratter
    or a full list, and is then the current one.
--}}
@php
    $tabs = [
        'dashboard' => [route('ratting-manager.dashboard'), 'fa-tachometer-alt', trans('ratting-manager::menu.dashboard')],
        'leaderboard' => [route('ratting-manager.leaderboard'), 'fa-trophy', trans('ratting-manager::menu.leaderboard')],
    ];
    if (\RattingManager\Support\Access::isDirector()) {
        $tabs['ratters'] = [route('ratting-manager.ratters.index'), 'fa-search', trans('ratting-manager::menu.ratter_lookup')];
    }
    $active = isset($page) ? null : ($active ?? null);
@endphp
@foreach($tabs as $key => [$url, $icon, $label])
    <li class="nav-item">
        <a class="nav-link {{ $active === $key ? 'active' : '' }}" href="{{ $url }}"><i class="fas {{ $icon }}"></i> {{ $label }}</a>
    </li>
@endforeach
@isset($page)
    <li class="nav-item">
        <a class="nav-link active" href="#" aria-current="page"><i class="fas {{ $page['icon'] }}"></i> {{ $page['label'] }}</a>
    </li>
@endisset
