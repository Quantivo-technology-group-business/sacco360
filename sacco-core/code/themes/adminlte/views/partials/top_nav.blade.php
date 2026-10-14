@php
    $navUser = Auth::user();
    $navInitials = strtoupper(substr($navUser->first_name ?? 'U', 0, 1) . substr($navUser->last_name ?? '', 0, 1));
    $navRole = optional($navUser->roles->first())->display_name
        ?? optional($navUser->roles->first())->name
        ?? trans_choice('user::general.user', 1);
    $navBranch = $navUser->branch_id
        ? \Illuminate\Support\Facades\DB::table('branches')->where('id', $navUser->branch_id)->value('name')
        : null;
    $navPhoto = !empty($navUser->photo)
        ? asset('storage/uploads/' . $navUser->photo)
        : asset('themes/adminlte/img/user.png');
    $navUnread = $navUser->unreadNotifications()->count();
@endphp
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-md-flex align-items-center ml-2">
            <a href="{{ url('/') }}" class="d-flex align-items-center text-decoration-none">
                @if(!empty($company_logo))
                    <img src="{{ asset('storage/uploads/' . $company_logo) }}" alt="{{ $company_name ?? config('app.name') }}" class="img-circle" style="width:32px;height:32px;object-fit:cover;">
                @endif
                <span class="ml-2 font-weight-bold text-dark">{{ $company_name ?? config('app.name') }}</span>
            </a>
        </li>
    </ul>

    <form class="form-inline ml-3" action="{{url('sacco/search')}}" method="get">
        <div class="input-group input-group-sm">
            <input class="form-control form-control-navbar" name="q" type="search"
                   placeholder="{{trans('sacco::general.global_search')}}" aria-label="Search"
                   value="{{request('q')}}">
            <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" aria-label="{{ trans_choice('core::general.notification', 2) }}">
                <i class="far fa-bell"></i>
                @if($navUnread > 0)
                    <span class="badge badge-warning navbar-badge">{{ $navUnread }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">{{ $navUnread }} {{ trans_choice('core::general.notification', 2) }}</span>
                @forelse($navUser->unreadNotifications as $notification)
                    <div class="dropdown-divider"></div>
                    <a href="@if(!empty($notification->data['link'])) {{url($notification->data['link'])}} @else {{url('user/profile/notification/'.$notification->id.'/show')}} @endif"
                       class="dropdown-item">
                        {{ $notification->data['message'] }}
                        <span class="float-right text-muted text-sm">{{ $notification->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <div class="dropdown-divider"></div>
                    <span class="dropdown-item text-muted text-sm">{{ trans_choice('core::general.notification', 2) }} — {{ trans_choice('core::general.no', 1) }}</span>
                @endforelse
                <div class="dropdown-divider"></div>
                <a href="{{ url('user/profile/notification') }}"
                   class="dropdown-item dropdown-footer">{{ __('core::general.View All') }}</a>
            </div>
        </li>

        <li class="nav-item dropdown user-menu sacco-user-menu">
            <a href="#" class="nav-link dropdown-toggle sacco-user-menu__trigger" data-toggle="dropdown" aria-expanded="false">
                <span class="sacco-user-menu__avatar sacco-user-menu__avatar--sm">
                    @if(!empty($navUser->photo))
                        <img src="{{ $navPhoto }}" alt="{{ $navUser->full_name }}">
                    @else
                        <span class="sacco-user-menu__initials">{{ $navInitials }}</span>
                    @endif
                </span>
                <span class="sacco-user-menu__trigger-text d-none d-md-inline">
                    <span class="sacco-user-menu__trigger-name">{{ $navUser->full_name }}</span>
                    <span class="sacco-user-menu__trigger-role">{{ $navRole }}</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-right sacco-user-menu__dropdown">
                <li class="sacco-user-menu__header">
                    <div class="sacco-user-menu__avatar sacco-user-menu__avatar--lg">
                        @if(!empty($navUser->photo))
                            <img src="{{ $navPhoto }}" alt="{{ $navUser->full_name }}">
                        @else
                            <span class="sacco-user-menu__initials">{{ $navInitials }}</span>
                        @endif
                    </div>
                    <div class="sacco-user-menu__identity">
                        <strong>{{ $navUser->full_name }}</strong>
                        <span class="sacco-user-menu__email">{{ $navUser->email }}</span>
                        <span class="sacco-user-menu__badge">{{ $navRole }}</span>
                    </div>
                    @if($navBranch || $navUser->last_login)
                        <div class="sacco-user-menu__meta">
                            @if($navBranch)
                                <span><i class="fas fa-building fa-fw"></i> {{ $navBranch }}</span>
                            @endif
                            @if($navUser->last_login)
                                <span><i class="fas fa-clock fa-fw"></i> {{ __('general.last_login') }}: {{ \Carbon\Carbon::parse($navUser->last_login)->diffForHumans() }}</span>
                            @endif
                        </div>
                    @endif
                </li>
                <li class="sacco-user-menu__links">
                    <a href="{{ url('user/profile') }}" class="sacco-user-menu__link">
                        <i class="fas fa-user-circle"></i>
                        <span>{{ trans_choice('core::general.profile', 1) }}</span>
                        <i class="fas fa-chevron-right sacco-user-menu__link-arrow"></i>
                    </a>
                    <a href="{{ url('user/profile/change_password') }}" class="sacco-user-menu__link">
                        <i class="fas fa-key"></i>
                        <span>{{ __('user::general.Change Password') }}</span>
                        <i class="fas fa-chevron-right sacco-user-menu__link-arrow"></i>
                    </a>
                    <a href="{{ url('user/profile/notification') }}" class="sacco-user-menu__link">
                        <i class="fas fa-bell"></i>
                        <span>{{ __('user::general.Notifications') }}</span>
                        @if($navUnread > 0)
                            <span class="badge badge-warning sacco-user-menu__link-badge">{{ $navUnread }}</span>
                        @endif
                        <i class="fas fa-chevron-right sacco-user-menu__link-arrow"></i>
                    </a>
                </li>
                <li class="sacco-user-menu__footer">
                    <a href="{{ url('user/profile') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-cog mr-1"></i> {{ trans_choice('core::general.account', 1) }}
                    </a>
                    <button type="button" class="btn btn-sacco-accent btn-sm" onclick="saccoLogout()">
                        <i class="fas fa-sign-out-alt mr-1"></i> {{ trans_choice('core::general.logout', 1) }}
                    </button>
                </li>
            </ul>
        </li>
    </ul>
</nav>
<form action="{{ url('logout') }}" method="post" id="logout_form" class="d-none">
    @csrf
</form>
<script>
    function saccoLogout() {
        document.getElementById('logout_form').submit();
    }
</script>
