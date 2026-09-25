<nav class="creator-nav" aria-label="Creator dashboard">
    <a href="{{ route('creator.dashboard') }}" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">
        <i class="fas fa-home"></i> Overview
    </a>
    <a href="{{ route('creator.videos') }}" class="{{ ($active ?? '') === 'videos' ? 'active' : '' }}">
        <i class="fas fa-video"></i> My Videos
    </a>
    <a href="{{ route('creator.streams') }}" class="{{ ($active ?? '') === 'streams' ? 'active' : '' }}">
        <i class="fas fa-broadcast-tower"></i> Livestreams
    </a>
    <a href="{{ route('creator.analytics') }}" class="{{ ($active ?? '') === 'analytics' ? 'active' : '' }}">
        <i class="fas fa-chart-bar"></i> Analytics
    </a>
    <a href="{{ route('my.settings', ['type' => 'profile']) }}">
        <i class="fas fa-cog"></i> Settings
    </a>
</nav>
