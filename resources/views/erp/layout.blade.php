<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('erp.name'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/image.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --erp-primary: #fe790d;
            --erp-secondary: #2c3e50;
            --erp-bg: #f4f6f9;
            --erp-border: #e5e8ee;
            --erp-text: #1f2937;
        }
        body {
            background: var(--erp-bg);
            color: var(--erp-text);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            font-size: 14px;
        }
        .erp-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 207px 1fr;
        }
        .erp-sidebar {
            background: #454643;
            color: #d7d7d7;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: inset -1px 0 rgba(0, 0, 0, .16);
            scrollbar-width: thin;
            scrollbar-color: #5d5e5a #454643;
        }
        .erp-brand {
            min-height: 62px;
            background: #fff;
            color: #2d3136;
            border-bottom: 1px solid #d7d7d7;
        }
        .erp-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: var(--erp-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
        }
        .erp-brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            object-fit: contain;
            background: #fff;
            padding: 2px;
            flex: 0 0 auto;
        }
        .erp-brand-title {
            min-width: 0;
            max-width: 106px;
            font-size: 16px;
            font-weight: 400;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .erp-nav a,
        .erp-nav button {
            color: #d8d8d8;
            text-decoration: none;
            border-radius: 0;
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 9px 14px 9px 22px;
            margin: 0;
            border: 0;
            background: transparent;
            width: 100%;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.2;
            transition: background-color .14s ease, color .14s ease;
        }
        .erp-nav a:hover,
        .erp-nav a.active,
        .erp-nav button:hover {
            color: #fff;
            background: #ff5722;
        }
        .erp-nav button.active {
            color: #fff;
            background: #3f403e;
        }
        .erp-nav a.active {
            border-left: 0;
        }
        .erp-nav button.active {
            box-shadow: inset 4px 0 #ff5722;
        }
        .erp-nav i:first-child {
            width: 26px;
            flex: 0 0 26px;
            text-align: center;
            font-size: 19px;
            color: currentColor;
        }
        .erp-nav .nav-child {
            min-height: 42px;
            padding-left: 34px;
            font-size: 13px;
            color: #d8d8d8;
            background: #3f403e;
        }
        .erp-nav .nav-child:hover,
        .erp-nav .nav-child.active {
            background: #ff5722;
            color: #fff;
        }
        .erp-nav .nav-caret {
            margin-left: auto;
            transition: transform .18s ease;
            width: auto;
            flex: 0 0 auto;
            font-size: 13px;
        }
        .erp-nav button[aria-expanded="true"] .nav-caret {
            transform: rotate(180deg);
        }
        .erp-main {
            min-width: 0;
        }
        .erp-topbar {
            height: 74px;
            background: #fff;
            border-bottom: 1px solid var(--erp-border);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .erp-card {
            background: #fff;
            border: 1px solid var(--erp-border);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        }
        .kpi-card {
            min-height: 126px;
        }
        .kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: var(--erp-primary);
        }
        .btn-primary {
            --bs-btn-bg: var(--erp-primary);
            --bs-btn-border-color: var(--erp-primary);
            --bs-btn-hover-bg: #df6500;
            --bs-btn-hover-border-color: #df6500;
        }
        .text-primary {
            color: var(--erp-primary) !important;
        }
        .badge-soft {
            background: #fff3e8;
            color: #b65000;
            border: 1px solid #ffd7b7;
        }
        .table thead th {
            color: #657489;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .02em;
            background: #f8fafc;
        }
        .select2-container .select2-selection--single {
            height: 38px;
            border-color: #dee2e6;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        @media (max-width: 991.98px) {
            .erp-shell {
                grid-template-columns: 1fr;
            }
            .erp-sidebar {
                height: auto;
                position: relative;
            }
            .erp-topbar {
                position: relative;
            }
        }
        @media print {
            .erp-sidebar,
            .erp-topbar,
            .no-print {
                display: none !important;
            }
            .erp-shell {
                display: block;
            }
            body {
                background: #fff;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="erp-shell">
    <aside class="erp-sidebar">
        <div class="erp-brand d-flex align-items-center gap-2 px-3">
            <img src="{{ asset('images/image.png') }}" alt="{{ config('erp.name') }} logo" class="erp-brand-logo">
            <div class="erp-brand-title">SEA-JET I...</div>
        </div>
        <nav class="erp-nav py-0 accordion" id="erpSidebarNav">
            @foreach(config('erp.sidebar') as $sectionIndex => $section)
                @php
                    $sectionActive = false;
                    if (isset($section['items'])) {
                        foreach ($section['items'] as $item) {
                            foreach ($item['patterns'] as $pattern) {
                                if (($pattern === '/' && request()->is('/')) || request()->is($pattern)) {
                                    $sectionActive = true;
                                }
                            }
                        }
                    } else {
                        foreach ($section['patterns'] as $pattern) {
                            if (($pattern === '/' && request()->path() === '/') || request()->is($pattern)) {
                                $sectionActive = true;
                            }
                        }
                    }
                    $collapseId = 'sidebarSection'.$sectionIndex;
                @endphp
                <div class="mb-1">
                    @if(isset($section['items']))
                        <button class="{{ $sectionActive ? 'active' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $sectionActive ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                            <i class="bi {{ $section['icon'] }}"></i>
                            <span>{{ $section['label'] }}</span>
                            <i class="bi bi-chevron-down nav-caret"></i>
                        </button>
                        <div id="{{ $collapseId }}" class="collapse {{ $sectionActive ? 'show' : '' }}" data-bs-parent="#erpSidebarNav">
                            @foreach($section['items'] as $item)
                                @php
                                    $active = false;
                                    foreach ($item['patterns'] as $pattern) {
                                        if (($pattern === '/' && request()->path() === '/') || request()->is($pattern)) {
                                            $active = true;
                                        }
                                    }
                                    $params = isset($item['params']) ? $item['params'] : [];
                                    $url = route($item['route'], $params);
                                @endphp
                                <a href="{{ $url }}" class="nav-child {{ $active ? 'active' : '' }}">
                                    <i class="bi {{ isset($item['icon']) ? $item['icon'] : $section['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        @php
                            $params = isset($section['params']) ? $section['params'] : [];
                            $url = route($section['route'], $params);
                        @endphp
                        <a href="{{ $url }}" class="{{ $sectionActive ? 'active' : '' }}">
                            <i class="bi {{ $section['icon'] }}"></i>
                            <span>{{ $section['label'] }}</span>
                        </a>
                    @endif
                </div>
            @endforeach
        </nav>
    </aside>
    <main class="erp-main">
        <header class="erp-topbar d-flex align-items-center justify-content-between px-4">
            <div>
                <div class="fw-semibold">@yield('page-title', 'ERP Dashboard')</div>
                <small class="text-muted">Multi-company | Multi-branch | Multi-currency ready</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('erp.notifications') }}" class="btn btn-outline-secondary btn-sm" title="Notifications"><i class="bi bi-bell"></i></a>
                <a href="{{ route('erp.invoices.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Quick Entry</a>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name ?? 'User' }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header">
                            <div class="fw-semibold">{{ auth()->user()->email ?? '' }}</div>
                            <small>{{ auth()->user()->role ?? '' }}</small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        <section class="p-4">
            @yield('content')
        </section>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        $('.erp-select').select2({ width: 'resolve' });
        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    });
</script>
@stack('scripts')
</body>
</html>
