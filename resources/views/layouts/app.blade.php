<!DOCTYPE html>
<html>
<head>
    <title>Telkom Battle Map</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('scripts')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/map.css') }}">
    <link rel="stylesheet" href="{{ asset('css/analytics.css') }}">
</head>
@stack('scripts')
<body>

<div class="header"></div>

<div class="navbar">
    <a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}">Map</a>
    <a href="/analytics" class="{{ request()->is('analytics') ? 'active' : '' }}">Analytics</a>
    <a href="/history"   class="{{ request()->is('history')   ? 'active' : '' }}">History</a>

    @if(session('auth_user') && session('auth_user')['role'] === 'admin')
        <a href="/users" class="{{ request()->is('users') ? 'active' : '' }}">👥 Users</a>
    @endif

    {{-- Auth section di kanan --}}
    <div style="margin-left:auto;display:flex;align-items:center;gap:10px;">
        @if(session('auth_user'))
            <span style="font-size:13px;color:#6c757d;">
                👤 {{ session('auth_user')['name'] }}
            </span>
            <form method="POST" action="{{ url('/logout') }}" style="margin:0;">
                @csrf
                <button type="submit"
                        style="padding:5px 14px;background:#f0f0f0;color:#495057;border:none;
                               border-radius:20px;font-size:13px;cursor:pointer;font-weight:600;">
                    Logout
                </button>
            </form>
        @else
            <a href="/login"
               style="padding:5px 16px;background:#ed1c24;color:#fff;border-radius:20px;
                      font-size:13px;font-weight:700;text-decoration:none;">
                🔑 Login
            </a>
        @endif
    </div>
</div>

<div class="container">
    @yield('content')
</div>

</body>
</html>