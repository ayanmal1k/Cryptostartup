<nav class="navbar navbar-default navbar-fixed-top navbar-top jf-topbar">
    <div class="container-fluid jf-topbar__inner">
        <div class="navbar-header jf-topbar__left">
            <button type="button" class="hamburger btn-link jf-topbar__menu" aria-label="Toggle menu">
                <svg class="jf-menu-icon" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true">
                    <line class="jf-menu-icon__bar jf-menu-icon__bar--1" x1="3" y1="4" x2="13" y2="4"></line>
                    <line class="jf-menu-icon__bar jf-menu-icon__bar--2" x1="3" y1="8" x2="13" y2="8"></line>
                    <line class="jf-menu-icon__bar jf-menu-icon__bar--3" x1="3" y1="12" x2="13" y2="12"></line>
                </svg>
            </button>
            @section('breadcrumbs')
            <ol class="breadcrumb jf-topbar__breadcrumb hidden-xs">
                @php
                $segments = array_filter(explode('/', str_replace(route('voyager.dashboard'), '', Request::url())));
                $url = route('voyager.dashboard');
                @endphp
                @if(count($segments) == 0)
                    <li class="active"><i class="voyager-home" aria-hidden="true"></i><span>{{ __('Admin') }}</span></li>
                @else
                    <li>
                        <a href="{{ route('voyager.dashboard')}}"><i class="voyager-home" aria-hidden="true"></i><span>{{ __('Admin') }}</span></a>
                    </li>
                    @foreach ($segments as $segment)
                        @php
                        $url .= '/'.$segment;
                        @endphp
                        @if ($loop->last)
                            <li class="active"><span>{{ ucfirst(urldecode($segment)) }}</span></li>
                        @else
                            <li>
                                <a href="{{ $url }}"><span>{{ ucfirst(urldecode($segment)) }}</span></a>
                            </li>
                        @endif
                    @endforeach
                @endif
            </ol>
            @show
        </div>
        <ul class="nav navbar-nav jf-topbar__right @if (__('voyager::generic.is_rtl') == 'true') navbar-left @else navbar-right @endif">
            <li class="dropdown profile jf-topbar__profile">
                <a href="#" class="dropdown-toggle jf-topbar__profile-btn" data-toggle="dropdown" role="button"
                   aria-expanded="false">
                    <img src="{{ $user_avatar }}" class="profile-img jf-topbar__avatar" alt="">
                    <i class="voyager-angle-down jf-topbar__profile-chevron" aria-hidden="true"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-animated jf-topbar__dropdown">
                    <li class="jf-topbar__dropdown-head">
                        <img src="{{ $user_avatar }}" class="jf-topbar__dropdown-avatar" alt="">
                        <div class="jf-topbar__dropdown-user">
                            <span class="jf-topbar__dropdown-name">{{ Auth::user()->name }}</span>
                            <span class="jf-topbar__dropdown-email">{{ Auth::user()->email }}</span>
                        </div>
                    </li>
                    <li class="jf-topbar__dropdown-sep" role="separator"></li>
                    <?php $nav_items = config('voyager.dashboard.navbar_items'); ?>
                    @if(is_array($nav_items) && !empty($nav_items))
                    @foreach($nav_items as $name => $item)
                    <li class="jf-topbar__dropdown-item @if(isset($item['route']) && $item['route'] == 'voyager.logout') jf-topbar__dropdown-item--logout @endif">
                        @if(isset($item['route']) && $item['route'] == 'voyager.logout')
                        <form action="{{ route('voyager.logout') }}" method="POST" class="jf-topbar__dropdown-logout-form">
                            {{ csrf_field() }}
                            <button type="submit" class="jf-topbar__dropdown-logout">
                                @if(isset($item['icon_class']) && !empty($item['icon_class']))
                                <i class="{!! $item['icon_class'] !!}"></i>
                                @endif
                                <span>{{ __($name) }}</span>
                            </button>
                        </form>
                        @else
                        <a href="{{ isset($item['route']) && Route::has($item['route']) ? route($item['route']) : (isset($item['route']) ? $item['route'] : '#') }}"
                           class="jf-topbar__dropdown-link"
                           {!! isset($item['target_blank']) && $item['target_blank'] ? 'target="_blank"' : '' !!}>
                            @if(isset($item['icon_class']) && !empty($item['icon_class']))
                            <i class="{!! $item['icon_class'] !!}"></i>
                            @endif
                            <span>{{ __($name) }}</span>
                        </a>
                        @endif
                    </li>
                    @endforeach
                    @endif
                </ul>
            </li>
        </ul>
    </div>
</nav>
