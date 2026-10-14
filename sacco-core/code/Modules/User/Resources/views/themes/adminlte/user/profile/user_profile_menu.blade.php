@php
    $profileInitials = strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1));
    $profilePhoto = !empty($user->photo) ? asset('storage/uploads/' . $user->photo) : null;
    $profileBranch = $user->branch_id
        ? \Illuminate\Support\Facades\DB::table('branches')->where('id', $user->branch_id)->value('name')
        : null;
    $profileSegment = Request::segment(3) ?: 'account';
    $profileNav = [
        ['segment' => 'account', 'url' => url('user/profile'), 'icon' => 'fa-user-circle', 'label' => __('user::general.Account Information')],
        ['segment' => 'change_password', 'url' => url('user/profile/change_password'), 'icon' => 'fa-key', 'label' => __('user::general.Change Password')],
        ['segment' => 'notification', 'url' => url('user/profile/notification'), 'icon' => 'fa-bell', 'label' => __('user::general.Notifications')],
        ['segment' => 'activity_log', 'url' => url('user/profile/activity_log'), 'icon' => 'fa-history', 'label' => __('user::general.Activity Logs')],
        ['segment' => 'two_factor', 'url' => url('user/profile/two_factor'), 'icon' => 'fa-shield-alt', 'label' => __('user::general.Two Factor Authentication')],
        ['segment' => 'note', 'url' => url('user/profile/note'), 'icon' => 'fa-sticky-note', 'label' => __('user::general.Notes')],
        ['segment' => 'api', 'url' => url('user/profile/api'), 'icon' => 'fa-code', 'label' => __('user::general.API Keys')],
    ];
    if ($profileSegment === '') {
        $profileSegment = 'account';
    }
@endphp
<div class="card profile-sidebar">
    <div class="profile-sidebar__hero">
        <div class="profile-sidebar__avatar">
            @if($profilePhoto)
                <a href="{{ $profilePhoto }}" class="fancybox">
                    <img src="{{ $profilePhoto }}" alt="{{ $user->full_name }}">
                </a>
            @else
                <span class="profile-sidebar__initials">{{ $profileInitials }}</span>
            @endif
        </div>
        <h3 class="profile-sidebar__name">{{ $user->first_name }} {{ $user->last_name }}</h3>
        <p class="profile-sidebar__email">{{ $user->email }}</p>
        <div class="profile-sidebar__badges">
            @foreach($user->roles as $role)
                <span class="profile-sidebar__badge">{{ $role->display_name ?? $role->name }}</span>
            @endforeach
        </div>
    </div>

    <div class="profile-sidebar__meta">
        @if($profileBranch)
            <div class="profile-sidebar__meta-item">
                <i class="fas fa-building"></i>
                <span>{{ $profileBranch }}</span>
            </div>
        @endif
        @if($user->phone)
            <div class="profile-sidebar__meta-item">
                <i class="fas fa-phone"></i>
                <span>{{ $user->phone }}</span>
            </div>
        @endif
        @if($user->last_login)
            <div class="profile-sidebar__meta-item">
                <i class="fas fa-clock"></i>
                <span>{{ __('general.last_login') }} {{ \Carbon\Carbon::parse($user->last_login)->diffForHumans() }}</span>
            </div>
        @endif
    </div>

    <nav class="profile-sidebar__nav">
        @foreach($profileNav as $item)
            <a href="{{ $item['url'] }}"
               class="profile-sidebar__nav-link @if($profileSegment === $item['segment'] || ($profileSegment === '' && $item['segment'] === 'account')) active @endif">
                <i class="fas {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
                <i class="fas fa-chevron-right profile-sidebar__nav-arrow"></i>
            </a>
        @endforeach
    </nav>
</div>
