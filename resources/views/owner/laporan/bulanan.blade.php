@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Laporan Bulanan</h1>
                <a href="{{ route('owner.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter"></i> Filter Laporan
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('owner.laporan.bulanan') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-md-10">
                                <label for="month" class="form-label">Pilih Bulan</label>
                                <input type="month" class="form-control" id="month" name="month" 
                                       value="{{ request('month') ?? \Carbon\Carbon::now()->format('Y-m') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Transaksi</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summary['total_transaksi'] ?? 0 }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Pendapatan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        Rp {{ number_format($summary['total_pendapatan'] ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Rata-rata Harian</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        Rp {{ number_format($summary['rata_harian'] ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Bulan</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $summary['bulan'] ?? \Carbon\Carbon::now()->translatedFormat('F Y') }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generate Daily Data jika tidak ada dari controller -->
            @php
                // Jika $dailyData tidak ada dari controller, generate dari transactions
                if(!isset($dailyData) && isset($transactions)) {
                    $dailyData = $transactions->groupBy(function($transaction) {
                        return $transaction->created_at->format('Y-m-d');
                    })->map(function($dayTransactions, $date) {
                        return (object)[
                            'date' => $date,
                            'total_transaksi' => $dayTransactions->count(),
                            'total_pendapatan' => $dayTransactions->sum('total_harga')
                        ];
                    })->values()->sortBy('date');
                }
            @endphp

            <!-- Daily Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistik Harian</h5>
                </div>
                <div class="card-body">
                    @if(isset($dailyData) && $dailyData->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jumlah Transaksi</th>
                                        <th>Total Pendapatan</th>
                                        <th>Rata-rata per Transaksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyData as $day)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
                                        <td class="text-center">{{ $day->total_transaksi }}</td>
                                        <td class="text-success fw-bold">
                                            Rp {{ number_format($day->total_pendapatan, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @php
                                                $avg = $day->total_transaksi > 0 ? $day->total_pendapatan / $day->total_transaksi : 0;
                                            @endphp
                                            Rp {{ number_format($avg, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-group-divider">
                                        <td class="fw-bold">Total</td>
                                        <td class="text-center fw-bold">{{ $dailyData->sum('total_transaksi') }}</td>
                                        <td class="text-success fw-bold">
                                            Rp {{ number_format($dailyData->sum('total_pendapatan'), 0, ',', '.') }}
                                        </td>
                                        <td class="fw-bold">
                                            @php
                                                $totalTransactions = $dailyData->sum('total_transaksi');
                                                $totalPendapatan = $dailyData->sum('total_pendapatan');
                                                $overallAvg = $totalTransactions > 0 ? $totalPendapatan / $totalTransactions : 0;
                                            @endphp
                                            Rp {{ number_format($overallAvg, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <p class="mb-0">Tidak ada data transaksi harian untuk bulan {{ $summary['bulan'] ?? \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Product Sales (Opsional) -->
            @php
                // Generate product sales data jika tidak ada dari controller
                if(!isset($productSales) && isset($transactions)) {
                    $productSales = [];
                    foreach($transactions as $transaction) {
                        foreach($transaction->transactionDetails as $detail) {
                            $productName = $detail->product->nama_produk;
                            if (!isset($productSales[$productName])) {
                                $productSales[$productName] = [
                                    'nama_produk' => $productName,
                                    'total_terjual' => 0,
                                    'total_pendapatan' => 0
                                ];
                            }
                            $productSales[$productName]['total_terjual'] += $detail->kuantitas;
                            $productSales[$productName]['total_pendapatan'] += $detail->subtotal;
                        }
                    }
                    $productSales = collect($productSales)->sortByDesc('total_terjual')->take(10);
                }
            @endphp

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Transaksi Bulanan</h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="printReport()">
                                    <i class="fas fa-print"></i> Print
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('owner.export.laporan.bulanan', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                                    <i class="fas fa-file-pdf text-danger"></i> PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('owner.export.laporan.bulanan', array_merge(request()->all(), ['format' => 'excel'])) }}">
                                    <i class="fas fa-file-excel text-success"></i> Excel
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($transactions) && $transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>ID Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Kasir</th>
                                        <th>Total</th>
                                        <th>Metode Bayar</th>
                                        <th>Jumlah Item</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                        </td>
                                        <td>
                                            <div>{{ $transaction->created_at->format('d/m/Y') }}</div>
                                            <small class="text-muted">{{ $transaction->created_at->format('H:i:s') }}</small>
                                        </td>
                                        <td>{{ $transaction->user->name }}</td>
                                        <td class="text-success fw-bold">
                                            Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @if($transaction->metode_pembayaran == 'cash')
                                                <span class="badge bg-success">CASH</span>
                                            @else
                                                <span class="badge bg-primary">QRIS</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">
                                                {{ $transaction->transactionDetails->sum('kuantitas') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('transaksi.show', $transaction->id) }}" 
                                                   class="btn btn-info" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('transaksi.receipt', $transaction->id) }}" 
                                                   target="_blank" class="btn btn-warning" title="Cetak Struk">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-group-divider">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">TOTAL BULAN {{ strtoupper($summary['bulan'] ?? '') }}:</td>
                                        <td colspan="4" class="fw-bold text-success">
                                            Rp {{ number_format($summary['total_pendapatan'] ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-receipt fa-3x mb-3"></i>
                            <h5>Tidak ada transaksi</h5>
                            <p class="mb-0">Tidak ada transaksi pada bulan {{ $summary['bulan'] ?? \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function printReport() {
        window.print();
    }

    // Set max month to current month
    document.addEventListener('DOMContentLoaded', function() {
        const monthInput = document.getElementById('month');
        const currentMonth = new Date().toISOString().slice(0, 7);
        monthInput.max = currentMonth;
        
        // If no month selected, set to current month
        if (!monthInput.value) {
            monthInput.value = currentMonth;
        }
    });
</script>

<style>
    @media print {
        .btn, .card-header .dropdown, .card-header .d-flex {
            display: none !important;
        }
        
        .card {
            border: none;
            box-shadow: none;
        }
        
        .table th {
            background-color: #f8f9fa !important;
            color: #000 !important;
        }
    }
    
    .table tfoot tr {
        background-color: #f8f9fa;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
</style>
@endsection