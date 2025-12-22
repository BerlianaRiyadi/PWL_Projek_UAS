@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Detail Transaksi #{{ $transaksi->id }}</h1>
                <div>
                    <a href="{{ route('transaksi.receipt', $transaksi->id) }}" target="_blank" class="btn btn-warning">
                        <i class="fas fa-print"></i> Cetak Struk
                    </a>
                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list"></i> Detail Produk
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transaksi->transactionDetails as $detail)
                                        <tr>
                                            <td>{{ $detail->product->nama_produk }}</td>
                                            <td>Rp {{ number_format($detail->harga_saat_transaksi, 0, ',', '.') }}</td>
                                            <td>{{ $detail->kuantitas }}</td>
                                            <td>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><strong>Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Informasi Transaksi
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID Transaksi</strong></td>
                                    <td>#{{ $transaksi->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal</strong></td>
                                    <td>{{ $transaksi->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Kasir</strong></td>
                                    <td>{{ $transaksi->user->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Metode Bayar</strong></td>
                                    <td>
                                        @if($transaksi->metode_pembayaran == 'cash')
                                            <span class="badge bg-success">Cash</span>
                                        @elseif($transaksi->metode_pembayaran == 'qris')
                                            <span class="badge bg-primary">QRIS</span>
                                        @else
                                            <span class="badge bg-info">Transfer</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status</strong></td>
                                    <td>
                                        @if($transaksi->status == 'completed')
                                            <span class="badge bg-success">Selesai</span>
                                        @else
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total</strong></td>
                                    <td class="h5 text-success">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
                                </tr>
                            </table>

                            @if($transaksi->status == 'completed')
                            <form action="{{ route('transaksi.cancel', $transaksi->id) }}" method="POST" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Yakin ingin membatalkan transaksi ini? Stok akan dikembalikan.')">
                                    <i class="fas fa-times"></i> Batalkan Transaksi
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection