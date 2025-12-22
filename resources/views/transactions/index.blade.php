@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Daftar Transaksi</h1>
                <a href="{{ route('transaksi.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Transaksi Baru
                </a>
            </div>

            <!-- Filter dan Pencarian -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="{{ route('transaksi.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Filter Tanggal</label>
                                <select name="date_filter" class="form-select" id="dateFilter">
                                    <option value="">Semua Tanggal</option>
                                    <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                                    <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                                    <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>Minggu Ini</option>
                                    <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>Bulan Ini</option>
                                    <option value="year" {{ request('date_filter') == 'year' ? 'selected' : '' }}>Tahun Ini</option>
                                    <option value="custom" {{ request('date_filter') == 'custom' ? 'selected' : '' }}>Rentang Tanggal</option>
                                </select>
                            </div>

                            <div class="col-md-2" id="startDateGroup" style="display: {{ request('date_filter') == 'custom' ? 'block' : 'none' }};">
                                <label class="form-label">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ request('start_date') }}">
                            </div>

                            <div class="col-md-2" id="endDateGroup" style="display: {{ request('date_filter') == 'custom' ? 'block' : 'none' }};">
                                <label class="form-label">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ request('end_date') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Filter Bulan</label>
                                <input type="month" name="month" class="form-control"
                                    value="{{ request('month') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Filter Tahun</label>
                                <input type="number" name="year" class="form-control"
                                    min="2000" max="{{ date('Y') + 1 }}"
                                    value="{{ request('year') ?: date('Y') }}"
                                    placeholder="{{ date('Y') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Pencarian</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Cari transaksi..."
                                        value="{{ request('search') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-redo"></i> Reset
                                        </a>
                                    </div>

                                    @if(request()->anyFilled(['date_filter', 'month', 'year', 'start_date', 'end_date', 'search']))
                                    <div class="alert alert-info py-2 px-3 mb-0">
                                        <small>
                                            <i class="fas fa-info-circle"></i>
                                            Filter aktif:
                                            @if(request('date_filter'))
                                            {{ ucfirst(request('date_filter')) }},
                                            @endif
                                            @if(request('month'))
                                            Bulan {{ date('F Y', strtotime(request('month').'-01')) }},
                                            @endif
                                            @if(request('year'))
                                            Tahun {{ request('year') }},
                                            @endif
                                            @if(request('search'))
                                            Pencarian: "{{ request('search') }}"
                                            @endif
                                        </small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistik -->
            @if($transactions->count() > 0)
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Transaksi</h6>
                                    <h3 class="mb-0">{{ $totalTransactions }}</h3>
                                </div>
                                <i class="fas fa-receipt fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Pendapatan</h6>
                                    <h3 class="mb-0">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                                </div>
                                <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Rata-rata Transaksi</h6>
                                    <h3 class="mb-0">Rp {{ number_format($averageTransaction, 0, ',', '.') }}</h3>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Transaksi Hari Ini</h6>
                                    <h3 class="mb-0">{{ $todayTransactions }}</h3>
                                </div>
                                <i class="fas fa-calendar-day fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Tabel Transaksi -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar Transaksi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ID Transaksi</th>
                                    <th>Tanggal</th>
                                    <th>Kasir</th>
                                    <th>Total</th>
                                    <th>Metode Bayar</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ ($transactions->currentPage() - 1) * $transactions->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <strong>#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                    </td>
                                    <td>
                                        <div>{{ $transaction->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $transaction->created_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>{{ $transaction->user->name }}</td>
                                    <td>
                                        <strong class="text-success">Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}</strong>
                                    </td>
                                    <td>
                                        @if($transaction->metode_pembayaran == 'cash')
                                        <span class="badge bg-success">Cash</span>
                                        @elseif($transaction->metode_pembayaran == 'qris')
                                        <span class="badge bg-primary">QRIS</span>
                                        @else
                                        <span class="badge bg-info">Transfer</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transaction->status == 'completed')
                                        <span class="badge bg-success">Selesai</span>
                                        @else
                                        <span class="badge bg-danger">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('transaksi.show', $transaction->id) }}" class="btn btn-sm btn-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('transaksi.receipt', $transaction->id) }}" target="_blank"
                                                class="btn btn-sm btn-warning" title="Cetak Struk">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @if($transaction->status == 'completed')
                                            <form action="{{ route('transaksi.cancel', $transaction->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Yakin ingin membatalkan transaksi ini? Stok akan dikembalikan.')"
                                                    title="Batalkan">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-receipt fa-3x mb-3"></i>
                                        <p>Belum ada transaksi.
                                            @if(request()->anyFilled(['date_filter', 'month', 'year', 'start_date', 'end_date', 'search']))
                                            <a href="{{ route('transaksi.index') }}">Tampilkan semua transaksi</a>
                                            @else
                                            <a href="{{ route('transaksi.create') }}">Buat transaksi pertama</a>
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($transactions->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Menampilkan
                            <strong>{{ $transactions->firstItem() ?? 0 }}</strong> -
                            <strong>{{ $transactions->lastItem() ?? 0 }}</strong>
                            dari <strong>{{ $transactions->total() }}</strong> transaksi
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                {{-- Previous Page Link --}}
                                @if($transactions->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                                @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $transactions->previousPageUrl() }}" rel="prev">&laquo;</a>
                                </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
                                @if($page == $transactions->currentPage())
                                <li class="page-item active">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                                @elseif($page >= $transactions->currentPage() - 2 && $page <= $transactions->currentPage() + 2)
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                    </li>
                                    @endif
                                    @endforeach

                                    {{-- Next Page Link --}}
                                    @if($transactions->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $transactions->nextPageUrl() }}" rel="next">&raquo;</a>
                                    </li>
                                    @else
                                    <li class="page-item disabled">
                                        <span class="page-link">&raquo;</span>
                                    </li>
                                    @endif
                            </ul>
                        </nav>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateFilter = document.getElementById('dateFilter');
        const startDateGroup = document.getElementById('startDateGroup');
        const endDateGroup = document.getElementById('endDateGroup');

        // Toggle custom date inputs
        dateFilter.addEventListener('change', function() {
            if (this.value === 'custom') {
                startDateGroup.style.display = 'block';
                endDateGroup.style.display = 'block';
            } else {
                startDateGroup.style.display = 'none';
                endDateGroup.style.display = 'none';
            }
        });

        // Set today as default for end date
        const today = new Date().toISOString().split('T')[0];
        const endDateInput = document.querySelector('input[name="end_date"]');
        if (endDateInput && !endDateInput.value) {
            endDateInput.value = today;
        }

        // Set 30 days ago as default for start date
        const startDateInput = document.querySelector('input[name="start_date"]');
        if (startDateInput && !startDateInput.value) {
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            startDateInput.value = thirtyDaysAgo.toISOString().split('T')[0];
        }
    });
</script>

<style>
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        padding: 0.375rem 0.75rem;
    }

    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }

    .btn-group .btn {
        margin-right: 2px;
    }

    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, .125);
    }
</style>
@endsection