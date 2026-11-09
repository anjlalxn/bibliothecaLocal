<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bibliotheca') | Bibliotheca</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Momo+Trust+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('style.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

    @php
        $navItems = [
            ['Dashboard',  'dashboard',  'bi-house'],
            ['Books',      'books',      'bi-book'],
            ['Borrowing',  'borrowing',  'bi-clock'],
            ['Return',     'return',     'bi-arrow-repeat'],
            ['Members',    'members',    'bi-person-circle'],
            ['Categories', 'categories', 'bi-tags'],
            ['Analytics',  'analytics',  'bi-graph-up'],
        ];

        $currentUser = auth()->user();
        $displayName = $currentUser
            ? ($currentUser->username ?: $currentUser->name ?: $currentUser->email ?: 'User')
            : null;
    @endphp

    <div class="d-md-flex">

        <aside class="sidebar offcanvas-md offcanvas-start d-flex flex-column" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
            <div class="offcanvas-header d-md-none">
                <h5 class="offcanvas-title visually-hidden" id="sidebarLabel">Menu</h5>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
            </div>

            <div class="offcanvas-body d-flex flex-column">
                <div class="sidebar-logo d-flex align-items-center justify-content-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('images/logo.png') }}" alt="Bibliotheca" class="img-fluid">
                    </a>
                </div>

                <ul class="nav flex-column">
                    @foreach ($navItems as [$label, $routeName, $icon])
                        @php
                            $isActive = request()->routeIs($routeName, $routeName . '.*');
                            $href = \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : '#';
                        @endphp
                        <li class="nav-item">
                            <a href="{{ $href }}"
                               class="nav-link {{ $isActive ? 'active' : '' }}"
                               @if ($isActive) aria-current="page" @endif>
                                <i class="bi {{ $icon }}"></i> {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="sidebar-footer mt-auto">
                    @if ($displayName)
                        <div class="sidebar-user d-flex align-items-center gap-2">
                            <i class="bi bi-person"></i> {{ $displayName }}
                        </div>
                    @endif
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-logout w-100">Logout</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex-grow-1 d-flex flex-column min-vh-100">

            <header class="mobile-topbar d-flex d-md-none align-items-center justify-content-between">
                <div class="sidebar-logo d-flex align-items-center justify-content-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('images/logo.png') }}" alt="Bibliotheca" class="img-fluid">
                    </a>
                </div>
                <button class="btn btn-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
            </header>

            <main class="main-content">
                <h1 class="page-title page-title--spaced">@yield('heading')</h1>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>