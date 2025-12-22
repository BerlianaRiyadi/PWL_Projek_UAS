<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #333; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f2f2f2; text-align: left; padding: 8px; border: 1px solid #ddd; font-weight: bold; }
        td { padding: 8px; border: 1px solid #ddd; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .summary-item { display: inline-block; margin-right: 30px; }
        .summary-label { font-weight: bold; color: #555; }
        .summary-value { font-size: 16px; font-weight: bold; color: #333; }
        .page-break { page-break-before: always; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 10px; }
        .section-title { background-color: #e9ecef; padding: 8px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $title }}</h2>
        <p>ASA JAYA PLAYGROUND</p>
        <p>Dicetak: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-item">
            <div class="summary-label">Periode</div>
            <div class="summary-value">{{ $summary['start_date'] }} - {{ $summary['end_date'] }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Transaksi</div>
            <div class="summary-value">{{ $summary['total_transaksi'] }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Pendapatan</div>
            <div class="summary-value">Rp {{ number_format($summary['total_pendapatan'], 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Detail Transaksi -->
    <div class="section-title">Detail Transaksi</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">ID Transaksi</th>
                <th width="15%">Tanggal</th>
                <th width="15%">Kasir</th>
                <th width="15%">Total</th>
                <th width="15%">Metode</th>
                <th width="25%">Item</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $transaction)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $transaction->user->name }}</td>
                <td class="text-right">Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}</td>
                <td class="text-center">{{ strtoupper($transaction->metode_pembayaran) }}</td>
                <td>
                    @foreach($transaction->transactionDetails as $detail)
                    • {{ $detail->product->nama_produk }} ({{ $detail->kuantitas }} x Rp {{ number_format($detail->harga_saat_transaksi, 0, ',', '.') }})<br>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                <td colspan="3" class="text-right"><strong>Rp {{ number_format($summary['total_pendapatan'], 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Penjualan Produk -->
    <div class="section-title">Penjualan Produk</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Nama Produk</th>
                <th width="15%">Total Terjual</th>
                <th width="20%">Total Pendapatan</th>
                <th width="15%">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productSales as $index => $product)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $product->nama_produk }}</td>
                <td class="text-center">{{ $product->total_terjual }} pcs</td>
                <td class="text-right">Rp {{ number_format($product->total_pendapatan, 0, ',', '.') }}</td>
                <td class="text-center">
                    @php
                        $persentase = $summary['total_pendapatan'] > 0 ? ($product->total_pendapatan / $summary['total_pendapatan']) * 100 : 0;
                    @endphp
                    {{ number_format($persentase, 2) }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>*** Laporan ini sah sebagai dokumentasi penjualan ***</p>
        <p>Halaman 1 dari 1</p>
    </div>
</body>
</html>