<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SINTA Dashboard') - Sekolah Vokasi IPB</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    @stack('styles')
</head>
<body>
    <!-- SIDEBAR -->s
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo"><img src="{{ asset('images/logo_bravo.png') }}" alt="Logo SINTA" class="logo"></div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ Request::routeIs('dashboard') ? 'active' : '' }}">
                    <span class="icon">📈</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('beranda') }}" class="{{ Request::routeIs('beranda') ? 'active' : '' }}">
                    <span class="icon">🔍</span>
                    <span>Cari Data</span>
                </a>
            </li>
            <li>
                <a href="{{ route('daftar-dosen') }}" class="{{ Request::routeIs('daftar-dosen') ? 'active' : '' }}">
                    <span class="icon">👥</span>
                    <span>Daftar Dosen</span>
                </a>
            </li>
            <li>
                <a href="{{ route('publications.index') }}" class="{{ Request::routeIs('publications.index') ? 'active' : '' }}">
                    <span class="icon">📚</span>
                    <span>Publications</span>
                </a>
            </li>
            <li>
                <a href="{{ route('hki.index') }}" class="{{ Request::routeIs('hki.index') ? 'active' : '' }}">
                    <span class="icon">🔎</span>
                    <span>Cari HKI Prodi</span>
                </a>
            </li>
            <li>
                <a href="{{ route('ipr.list') }}" class="{{ Request::routeIs('ipr.list') ? 'active' : '' }}">
                    <span class="icon">📜</span>
                    <span>Daftar HKI/IPR</span>
                </a>
            </li>
            <li>
                <a href="{{ route('simaker.index') }}" class="{{ Request::routeIs('simaker.*') ? 'active' : '' }}">
                    <span class="icon">📊</span>
                    <span>SIMAKER</span>
                </a>
            </li>
        </ul>
    </aside>

    <div class="container">
        <div class="main-content">
            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Setup CSRF token untuk AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    </script>
    
    @stack('scripts')
</body>
</html>