<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'منصة تصميم الكتب الذكية') - Laravel AI Book & Page Design</title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark border-bottom border-secondary border-opacity-25" style="background-color: #0d0f17;">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                <i class="bi bi-book-half text-primary fs-4"></i>
                <span>منصة تصميم الكتب الذكية</span>
                <span class="badge bg-primary bg-opacity-25 text-primary fs-7 border border-primary border-opacity-25 ms-2">GPT Image 2</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
                    @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active text-primary fw-bold' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-grid-fill me-1"></i> المشاريع
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('projects.create') ? 'active text-primary fw-bold' : '' }}" href="{{ route('projects.create') }}">
                            <i class="bi bi-plus-circle-fill me-1"></i> مشروع جديد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('settings.index') ? 'active text-primary fw-bold' : '' }}" href="{{ route('settings.index') }}">
                            <i class="bi bi-gear-fill me-1"></i> الإعدادات
                        </a>
                    </li>
                    @endauth
                </ul>
                
                @auth
                <div class="d-flex align-items-center gap-3">
                    <span class="text-secondary small">
                        <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
                    </span>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                            <i class="bi bi-box-arrow-right me-1"></i> خروج
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </div>
    </nav>

    <main>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
