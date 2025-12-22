<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome CDN (TAMBAHKAN INI) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Styles -->
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }

        .sidebar .nav-link {
            color: #fff;
        }

        .sidebar .nav-link:hover {
            background-color: #495057;
        }

        .navbar-brand {
            font-weight: bold;
        }

        .badge {
            font-size: 0.8em;
            padding: 5px 10px;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .modal-alert {
            border-left: 4px solid #0dcaf0;
            padding: 10px 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>


<body>
    <div id="app">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            @auth
                            <li class="nav-item">
                                <a class="nav-link active" href="{{ route('dashboard') }}">
                                    Dashboard
                                </a>
                            </li>

                            <!-- Menu untuk Kasir -->
                            @if(Auth::user()->role === 'kasir')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('produk.index') }}">
                                    <i class="fas fa-boxes"></i> Lihat Produk
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('produk.create') }}">
                                    <i class="fas fa-plus"></i> Tambah Produk
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('transaksi.index') }}">
                                    <i class="fas fa-cash-register"></i> Kelola Transaksi
                                </a>
                            </li>
                            @endif

                            <!-- Menu untuk Owner -->
                            @auth
                            @if(Auth::user()->role === 'owner')
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="ownerDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-crown"></i> Owner Menu
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="ownerDropdown">
                                    <li><a class="dropdown-item" href="{{ route('owner.dashboard') }}">
                                            <i class="fas fa-tachometer-alt"></i> Dashboard Owner
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="{{ route('owner.users.index') }}">
                                            <i class="fas fa-users"></i> Kelola User
                                        </a></li>
                                    <li><a class="dropdown-item" href="{{ route('owner.laporan.mingguan') }}">
                                            <i class="fas fa-chart-bar"></i> Laporan Mingguan
                                        </a></li>
                                    <li><a class="dropdown-item" href="{{ route('owner.laporan.bulanan') }}">
                                            <i class="fas fa-chart-line"></i> Laporan Bulanan
                                        </a></li>
                                </ul>
                            </li>
                            @endif
                            @endauth

                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                    document.getElementById('logout-form').submit();">
                                    Logout
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                            @endauth
                        </ul>
                    </div>
                </nav>

                <!-- Main content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <!-- Navigation Bar -->
                    <nav class="navbar navbar-expand-lg navbar-light bg-light">
                        <div class="container-fluid">
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <span class="navbar-brand">
                                @if(auth()->check())
                                Hello, {{ auth()->user()->name }} ({{ auth()->user()->role }})
                                @endif
                            </span>
                        </div>
                    </nav>

                    <!-- Content -->
                    <div class="py-4">
                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Scripts -->
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('[data-bs-toggle="collapse"]');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
        });
    </script>

    @stack('scripts')
</body>

</html>