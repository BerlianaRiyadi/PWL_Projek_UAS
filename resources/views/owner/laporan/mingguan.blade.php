@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Laporan Mingguan</h1>
                <a href="{{ route('owner.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
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
                                        {{ $summary['total_transaksi'] }}
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
                                        Rp {{ number_format($summary['total_pendapatan'], 0, ',', '.') }}
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
                                        Rata-rata Transaksi</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        Rp {{ number_format($summary['rata_transaksi'], 0, ',', '.') }}
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
                                        Periode</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $summary['start_date'] }} - {{ $summary['end_date'] }}
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

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Transaksi</h5>
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
                                <a class="dropdown-item" href="{{ route('owner.export.laporan.mingguan', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                                    <i class="fas fa-file-pdf text-danger"></i> PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('owner.export.laporan.mingguan', array_merge(request()->all(), ['format' => 'excel'])) }}">
                                    <i class="fas fa-file-excel text-success"></i> Excel
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    @if($transactions->count() > 0)
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
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('owner.transaksi.show', $transaction->id) }}"
                                                class="btn btn-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('owner.transaksi.receipt', $transaction->id) }}"
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
                                    <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                                    <td colspan="3" class="fw-bold text-success">
                                        Rp {{ number_format($summary['total_pendapatan'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-receipt fa-3x mb-3"></i>
                        <h5>Tidak ada transaksi</h5>
                        <p class="mb-0">Tidak ada transaksi pada periode {{ $summary['start_date'] }} - {{ $summary['end_date'] }}</p>
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

    // Set max date for end_date
    document.addEventListener('DOMContentLoaded', function() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const today = new Date().toISOString().split('T')[0];

        // Set max date to today
        endDate.max = today;

        // Update end_date max when start_date changes
        startDate.addEventListener('change', function() {
            endDate.min = this.value;
            if (endDate.value < this.value) {
                endDate.value = this.value;
            }
        });

        // Update start_date max when end_date changes
        endDate.addEventListener('change', function() {
            startDate.max = this.value;
        });
    });
</script>

<style>
    @media print {

        .btn,
        .card-header .dropdown,
        .card-header .d-flex {
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
</style>
@endsection