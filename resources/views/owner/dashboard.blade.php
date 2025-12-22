@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Owner Dashboard</h1>
                <div class="btn-group">
                    <a href="{{ route('owner.laporan.mingguan') }}" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Laporan Mingguan
                    </a>
                    <a href="{{ route('owner.laporan.bulanan') }}" class="btn btn-success">
                        <i class="fas fa-chart-line"></i> Laporan Bulanan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mt-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Transaksi Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $data['total_transaksi_hari_ini'] ?? 0 }}
                            </div>
                            <small class="text-muted">Transaksi selesai hari ini</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Penjualan Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($data['total_penjualan_hari_ini'] ?? 0, 0, ',', '.') }}
                            </div>
                            <small class="text-muted">Total pendapatan hari ini</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Produk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $data['total_produk'] ?? 0 }}
                            </div>
                            <small class="text-muted">Produk di sistem</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Kasir</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $data['total_users'] ?? 0 }}
                            </div>
                            <small class="text-muted">Jumlah kasir aktif</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="row mt-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Penjualan Minggu Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($data['total_penjualan_minggu_ini'] ?? 0, 0, ',', '.') }}
                            </div>
                            <small class="text-muted">{{ Carbon\Carbon::now()->startOfWeek()->format('d/m') }} - {{ Carbon\Carbon::now()->endOfWeek()->format('d/m/Y') }}</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Penjualan Bulan Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($data['total_penjualan_bulan_ini'] ?? 0, 0, ',', '.') }}
                            </div>
                            <small class="text-muted">{{ Carbon\Carbon::now()->translatedFormat('F Y') }}</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Produk Hampir Habis</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $data['produk_habis']->count() ?? 0 }}
                            </div>
                            <small class="text-muted">Stok ≤ 5 pcs</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions & Low Stock -->
    <div class="row mt-4">

        <!-- Recent Transactions -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Transaksi Terbaru</h6>

                </div>
                <div class="card-body">
                    @if($data['transaksi_terbaru']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kasir</th>
                                    <th>Total</th>
                                    <th>Metode</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['transaksi_terbaru'] as $transaksi)
                                <tr>
                                    <td>
                                        <a href="{{ route('transaksi.show', $transaksi->id) }}" class="text-decoration-none">
                                            #{{ str_pad($transaksi->id, 6, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </td>
                                    <td>{{ $transaksi->user->name }}</td>
                                    <td class="text-success fw-bold">
                                        Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @if($transaksi->metode_pembayaran == 'cash')
                                        <span class="badge bg-success">CASH</span>
                                        @elseif($transaksi->metode_pembayaran == 'qris')
                                        <span class="badge bg-primary">QRIS</span>
                                        @else
                                        <span class="badge bg-info">{{ strtoupper($transaksi->metode_pembayaran) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $transaksi->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('owner.transaksi.show', $transaksi->id) }}"
                                            class="btn btn-info" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-receipt fa-3x mb-3"></i>
                        <p class="mb-0">Belum ada transaksi</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger">Produk Hampir Habis</h6>
                </div>
                <div class="card-body">
                    @if($data['produk_habis']->count() > 0)
                    <div class="list-group">
                        @foreach($data['produk_habis'] as $produk)
                        <a href="{{ route('produk.edit', $produk->id) }}"
                            class="list-group-item list-group-item-action list-group-item-danger d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $produk->nama_produk }}</h6>
                                <small class="text-muted">Harga: Rp {{ number_format($produk->harga, 0, ',', '.') }}</small>
                            </div>
                            <span class="badge bg-danger rounded-pill">Stok: {{ $produk->stok }}</span>
                        </a>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-success py-3">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <p class="mb-0">Tidak ada produk yang hampir habis</p>
                        <small class="text-muted">Semua stok aman</small>
                    </div>
                    @endif
                </div>
            </div>


        </div>
    </div>
</div>

<!-- Add Carbon.js if not already included -->
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/locale/id.js"></script>
<script>
    dayjs.locale('id');
</script>
@endsection