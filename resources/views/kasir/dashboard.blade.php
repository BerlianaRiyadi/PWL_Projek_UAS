@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h2">Kasir Dashboard</h1>
            <p class="lead">Selamat datang, {{ auth()->user()->name }}!</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="{{ route('transaksi.create') }}" class="card text-white bg-primary h-100 text-decoration-none">
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-cash-register fa-3x mb-3"></i>
                        <h5>Transaksi Baru</h5>
                        <p>Mulai transaksi penjualan baru</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <a href="{{ route('produk.index') }}" class="card text-white bg-success h-100 text-decoration-none">
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-boxes fa-3x mb-3"></i>
                        <h5>Lihat Produk</h5>
                        <p>Kelola daftar produk</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <a href="{{ route('produk.create') }}" class="card text-white bg-warning h-100 text-decoration-none">
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <h5>Input Pembelian</h5>
                        <p>Tambah stok produk</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card text-white bg-info h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                    <h5>Statistik Hari Ini</h5>
                    <p>{{ $data['transaksi_hari_ini'] }} Transaksi</p>
                    <p>Rp {{ number_format($data['total_penjualan_hari_ini'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics & Alerts -->
    <div class="row mt-4">
        <!-- Statistics -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold">Statistik Anda Hari Ini</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-primary">{{ $data['transaksi_hari_ini'] }}</h3>
                                <p class="mb-0">Total Transaksi</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-success">Rp {{ number_format($data['total_penjualan_hari_ini'], 0, ',', '.') }}</h3>
                                <p class="mb-0">Total Penjualan</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-warning">{{ $data['produk_menipis'] }}</h3>
                                <p class="mb-0">Produk Menipis</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card shadow mt-4">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold">Transaksi Terbaru Anda</h6>
                </div>
                <div class="card-body">
                    @if($data['transaksi_terbaru']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Total</th>
                                    <th>Metode</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['transaksi_terbaru'] as $transaksi)
                                <tr>
                                    <td>#{{ $transaksi->id }}</td>
                                    <td>Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $transaksi->metode_pembayaran }}</span>
                                    </td>
                                    <td>{{ $transaksi->created_at->format('H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted">Belum ada transaksi hari ini</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold">Peringatan Stok</h6>
                </div>
                <div class="card-body">
                    @if($data['produk_habis'] > 0)
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ $data['produk_habis'] }} produk sudah habis
                    </div>
                    @endif

                    @if($data['produk_menipis'] > 0)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $data['produk_menipis'] }} produk stok menipis
                    </div>
                    @endif

                    @if($data['produk_habis'] == 0 && $data['produk_menipis'] == 0)
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Semua stok produk dalam kondisi baik
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Font Awesome for Icons -->
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
@endsection