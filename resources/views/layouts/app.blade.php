<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Student Management System') - {{ \App\Models\SchoolSetting::get('school_name', 'St. Jude Primary School') }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary-navy: #1b365d;
            --primary-accent: #0077b6;
            --sidebar-bg: #0f1e36;
            --sidebar-hover: #1e3a63;
            --sidebar-active: #0077b6;
            --body-bg: #f4f6f9;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--body-bg);
            color: #333333;
            overflow-x: hidden;
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        #sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: #ecf0f1;
            transition: all 0.3s;
            flex-shrink: 0;
            z-index: 1000;
        }
        #sidebar.collapsed {
            margin-left: -260px;
        }
        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .sidebar-brand h5 {
            font-size: 1.05rem;
            margin-bottom: 0;
            font-weight: 700;
            color: #ffffff;
        }
        .sidebar-brand small {
            font-size: 0.75rem;
            color: #a0aec0;
            display: block;
        }
        .sidebar-nav {
            padding: 1rem 0;
            list-style: none;
            margin: 0;
        }
        .sidebar-header {
            padding: 0.5rem 1.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #718096;
            font-weight: 600;
        }
        .sidebar-item {
            margin-bottom: 2px;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.65rem 1.5rem;
            color: #cbd5e0;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .sidebar-link i {
            margin-right: 0.85rem;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        .sidebar-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
        }
        .sidebar-link.active {
            color: #ffffff;
            background-color: var(--sidebar-active);
            border-left: 4px solid #64dfdf;
        }
        #content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .top-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
        }
        .main-content {
            padding: 1.75rem;
            flex-grow: 1;
        }
        .card {
            border: none;
            border-radius: 0.6rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #edf2f7;
            font-weight: 600;
            padding: 1rem 1.25rem;
            border-top-left-radius: 0.6rem !important;
            border-top-right-radius: 0.6rem !important;
        }
        .stat-card {
            border-left: 4px solid var(--primary-accent);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .badge-role {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-transform: uppercase;
        }
        .table thead th {
            background-color: #f8fafc;
            color: #4a5568;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -260px;
                position: fixed;
                height: 100vh;
            }
            #sidebar.show {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-brand">
            <h5><i class="bi bi-mortarboard-fill text-info me-2"></i>{{ \App\Models\SchoolSetting::get('school_name', 'St. Jude SMS') }}</h5>
            <small>{{ \App\Models\SchoolSetting::get('school_town', 'Nairobi, Kenya') }}</small>
        </div>
        <ul class="sidebar-nav">
            @php $user = Auth::user(); @endphp

            @if($user && !$user->hasRole('parent'))
                <li class="sidebar-item">
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard', 'home') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>

                <li class="sidebar-header">Learner Management</li>
                <li class="sidebar-item">
                    <a href="{{ route('learners.index') }}" class="sidebar-link {{ request()->routeIs('learners.*') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i> Learners & Admissions
                    </a>
                </li>
                @if($user->hasRole(['super_admin', 'registrar']))
                <li class="sidebar-item">
                    <a href="{{ route('learners.create') }}" class="sidebar-link {{ request()->routeIs('learners.create') ? 'active' : '' }}">
                        <i class="bi bi-person-plus-fill"></i> New Admission
                    </a>
                </li>
                @endif

                <li class="sidebar-header">Academic Administration</li>
                <li class="sidebar-item">
                    <a href="{{ route('academic.classes') }}" class="sidebar-link {{ request()->routeIs('academic.classes', 'academic.class.show') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Grades & Streams
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('academic.learning-areas') }}" class="sidebar-link {{ request()->routeIs('academic.learning-areas') ? 'active' : '' }}">
                        <i class="bi bi-book-half"></i> CBC Learning Areas
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('academic.years') }}" class="sidebar-link {{ request()->routeIs('academic.years') ? 'active' : '' }}">
                        <i class="bi bi-calendar3"></i> Academic Years & Terms
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('academic.promotion') }}" class="sidebar-link {{ request()->routeIs('academic.promotion') ? 'active' : '' }}">
                        <i class="bi bi-arrow-up-right-square-fill"></i> Learner Promotion
                    </a>
                </li>

                <li class="sidebar-header">School Fees & Accounting</li>
                <li class="sidebar-item">
                    <a href="{{ route('fees.invoices') }}" class="sidebar-link {{ request()->routeIs('fees.invoices*') ? 'active' : '' }}">
                        <i class="bi bi-receipt-cutoff"></i> Invoices & Billing
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('payments.index') }}" class="sidebar-link {{ request()->routeIs('payments.index', 'payments.receipt') ? 'active' : '' }}">
                        <i class="bi bi-credit-card-2-front-fill"></i> Payments & Receipts
                    </a>
                </li>
                @if($user->hasRole(['super_admin', 'bursar']))
                <li class="sidebar-item">
                    <a href="{{ route('payments.create') }}" class="sidebar-link {{ request()->routeIs('payments.create') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack"></i> Record Payment
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('fees.structures') }}" class="sidebar-link {{ request()->routeIs('fees.structures') ? 'active' : '' }}">
                        <i class="bi bi-sliders"></i> Fee Schedules
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('payments.daily-report') }}" class="sidebar-link {{ request()->routeIs('payments.daily-report') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i> Daily Collections
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('payments.arrears-report') }}" class="sidebar-link {{ request()->routeIs('payments.arrears-report') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-octagon-fill"></i> Fee Arrears Report
                    </a>
                </li>
                @endif

                <li class="sidebar-header">Assessments & Attendance</li>
                <li class="sidebar-item">
                    <a href="{{ route('assessments.index') }}" class="sidebar-link {{ request()->routeIs('assessments.index') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check-fill"></i> CBE Assessments
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('attendance.index') }}" class="sidebar-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check-fill"></i> Daily Attendance
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Report Cards
                    </a>
                </li>

                @if($user->hasRole('super_admin'))
                <li class="sidebar-header">System & Security</li>
                <li class="sidebar-item">
                    <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                        <i class="bi bi-gear-fill"></i> School Settings
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('settings.users') }}" class="sidebar-link {{ request()->routeIs('settings.users') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill"></i> Staff & Roles
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('settings.audit-logs') }}" class="sidebar-link {{ request()->routeIs('settings.audit-logs') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i> Audit Logs
                    </a>
                </li>
                @endif
            @else
                <!-- Parent Portal Navigation -->
                <li class="sidebar-header">Parent Portal</li>
                <li class="sidebar-item">
                    <a href="{{ route('parent.dashboard') }}" class="sidebar-link active">
                        <i class="bi bi-house-door-fill"></i> My Children
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    <!-- Content Area -->
    <div id="content">
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary btn-sm me-3" id="sidebarToggle" type="button">
                    <i class="bi bi-list fs-5"></i>
                </button>
                @php
                    $currYear = \App\Models\AcademicYear::current();
                    $currTerm = \App\Models\Term::current();
                @endphp
                <span class="badge bg-primary me-2">
                    <i class="bi bi-calendar2-week me-1"></i> Year {{ $currYear?->name ?? '2026' }}
                </span>
                <span class="badge bg-info text-dark">
                    <i class="bi bi-clock-history me-1"></i> {{ $currTerm?->name ?? 'Term 1' }}
                </span>
            </div>

            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-dark" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 34px; height: 34px; font-weight: bold;">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                        <div class="text-start d-none d-sm-block">
                            <span class="d-block fw-semibold small">{{ Auth::user()->name ?? 'Guest User' }}</span>
                            <span class="badge bg-secondary badge-role">{{ Auth::user()->role_name ?? 'staff' }}</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">Signed in as {{ Auth::user()->email ?? '' }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key me-2"></i>Change Password</a></li>
                        <li><hr class="dropdown-dropdown-divider my-1"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content Body -->
        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-x-octagon-fill me-2"></i>Validation Notice</h6>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-top py-3 px-4 text-muted small d-flex justify-content-between align-items-center">
            <div>
                &copy; {{ date('Y') }} <strong>{{ \App\Models\SchoolSetting::get('school_name', 'St. Jude Primary School') }}</strong>. Registered under Kenyan Basic Education Act & CBC.
            </div>
            <div>
                <span><i class="bi bi-clock me-1"></i> EAT (UTC+3)</span>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
        }
    });
</script>
@stack('scripts')
</body>
</html>

