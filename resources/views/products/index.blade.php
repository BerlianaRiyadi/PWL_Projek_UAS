@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Manajemen Produk</h1>
                <a href="{{ route('produk.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Produk
                </a>
            </div>

            <!-- Filter dan Pencarian -->
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('produk.index') }}" method="GET">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control" 
                                        placeholder="Cari nama produk..." 
                                        value="{{ request('search') }}">
                                    @if(request('search'))
                                    <a href="{{ route('produk.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    @endif
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Produk -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-box text-primary"></i> Daftar Produk
                        </h5>
                        @if($products->total() > 0)
                        <span class="badge bg-primary">{{ $products->total() }} Produk</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%" class="text-center">#</th>
                                    <th width="25%">Nama Produk</th>
                                    <th width="15%">Kategori</th>
                                    <th width="15%" class="text-end">Harga</th>
                                    <th width="10%" class="text-center">Stok</th>
                                    <th width="12%" class="text-center">Status</th>
                                    <th width="18%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                <tr>
                                    <td class="text-center text-muted">
                                        {{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                                    </td>
                                    <td>
                                        <strong class="text-dark">{{ $product->nama_produk }}</strong>
                                    </td>
                                    <td>
                                        @if($product->category)
                                            <span class="badge bg-info text-dark">
                                                <i class="fas fa-tag"></i> {{ $product->category->nama_kategori }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Tanpa Kategori</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success">Rp {{ number_format($product->harga, 0, ',', '.') }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $product->stock_status_color }} fs-6 px-3 py-2">
                                            {{ $product->stok }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($product->stock_status == 'out_of_stock')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle"></i> Habis
                                            </span>
                                        @elseif($product->stock_status == 'low_stock')
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-exclamation-triangle"></i> Menipis
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i> Tersedia
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('produk.edit', $product->id) }}" 
                                                class="btn btn-sm btn-warning" 
                                                title="Edit Produk">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#stockModal{{ $product->id }}"
                                                title="Update Stok">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <form action="{{ route('produk.destroy', $product->id) }}" 
                                                method="POST" 
                                                class="d-inline"
                                                onsubmit="return confirm('Yakin ingin menghapus produk {{ $product->nama_produk }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                    class="btn btn-sm btn-danger"
                                                    title="Hapus Produk">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Modal Update Stok -->
                                        <div class="modal fade" id="stockModal{{ $product->id }}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-info text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-boxes"></i> Update Stok
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('produk.update-stock', $product->id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="text-center mb-3">
                                                                <h6 class="text-primary">{{ $product->nama_produk }}</h6>
                                                            </div>
                                                            <div class="alert alert-light border">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span class="text-muted">Stok Saat Ini:</span>
                                                                    <span class="badge bg-{{ $product->stock_status_color }} fs-5 px-3 py-2">
                                                                        {{ $product->stok }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="stok{{ $product->id }}" class="form-label fw-bold">
                                                                    Stok Baru <span class="text-danger">*</span>
                                                                </label>
                                                                <input type="number" 
                                                                    class="form-control form-control-lg text-center" 
                                                                    id="stok{{ $product->id }}" 
                                                                    name="stok" 
                                                                    value="{{ $product->stok }}" 
                                                                    min="0" 
                                                                    required
                                                                    style="font-size: 1.5rem; font-weight: bold;">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times"></i> Batal
                                                            </button>
                                                            <button type="submit" class="btn btn-info">
                                                                <i class="fas fa-save"></i> Update Stok
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-box-open fa-4x mb-3 opacity-50"></i>
                                            <h5>Belum ada produk</h5>
                                            <p class="mb-3">Mulai dengan menambahkan produk pertama Anda</p>
                                            <a href="{{ route('produk.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Tambah Produk
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination Footer -->
                @if($products->hasPages())
                <div class="card-footer bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="text-muted small">
                                Menampilkan <strong>{{ $products->firstItem() }}</strong> 
                                sampai <strong>{{ $products->lastItem() }}</strong> 
                                dari <strong>{{ $products->total() }}</strong> produk
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                                {{ $products->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom styling for table */
    .table tbody tr {
        border-bottom: 1px solid #dee2e6;
    }

    /* Button group styling */
    .btn-group .btn {
        border-radius: 0;
    }

    .btn-group .btn:first-child {
        border-top-left-radius: 0.25rem;
        border-bottom-left-radius: 0.25rem;
    }

    .btn-group .btn:last-child {
        border-top-right-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
    }

    /* Badge styling */
    .badge {
        font-weight: 500;
        padding: 0.4em 0.8em;
    }

    /* Card shadow */
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    /* Pagination styling */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        color: #0d6efd;
        border-color: #dee2e6;
    }

    .page-link:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    /* Modal input styling */
    .modal-body input[type="number"] {
        border: 2px solid #dee2e6;
    }

    .modal-body input[type="number"]:focus {
        border-color: #0dcaf0;
        box-shadow: 0 0 0 0.25rem rgba(13, 202, 240, 0.25);
    }

    /* Empty state styling */
    .opacity-50 {
        opacity: 0.5;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .btn-group {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .btn-group .btn {
            border-radius: 0.25rem !important;
            margin-bottom: 0.25rem;
        }

        .table {
            font-size: 0.875rem;
        }
    }
</style>
@endsection