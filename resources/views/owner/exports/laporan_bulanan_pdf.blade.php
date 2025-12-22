<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h2 { margin: 0; color: #333; font-size: 18px; }
        .header p { margin: 3px 0; color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background-color: #f2f2f2; text-align: left; padding: 6px; border: 1px solid #ddd; font-weight: bold; font-size: 10px; }
        td { padding: 6px; border: 1px solid #ddd; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { background-color: #f8f9fa; padding: 12px; border-radius: 5px; margin-bottom: 15px; }
        .summary-item { display: inline-block; margin-right: 25px; }
        .summary-label { font-weight: bold; color: #555; font-size: 10px; }
        .summary-value { font-size: 14px; font-weight: bold; color: #333; }
        .page-break { page-break-before: always; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 9px; }
        .section-title { background-color: #e9ecef; padding: 6px; font-weight: bold; margin-top: 15px; font-size: 12px; }
        .compact { margin: 0; padding: 0; }
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
            <div class="summary-label">Bulan</div>
            <div class="summary-value">{{ $summary['bulan'] }}</div>
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
    <div class="section-title">Detail Transaksi ({{ $transactions->count() }} transaksi)</div>
    <table class="compact">
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="8%">ID</th>
                <th width="12%">Tanggal</th>
                <th width="12%">Kasir</th>
                <th width="10%">Total</th>
                <th width="8%">Metode</th>
                <th width="47%">Detail Item</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $transaction)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $transaction->created_at->format('d/m H:i') }}</td>
                <td>{{ Str::limit($transaction->user->name, 12) }}</td>
                <td class="text-right">Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}</td>
                <td class="text-center">{{ strtoupper($transaction->metode_pembayaran) }}</td>
                <td>
                    @foreach($transaction->transactionDetails as $detail)
                    • {{ Str::limit($detail->product->nama_produk, 20) }} ({{ $detail->kuantitas }})<br>
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
    <div class="section-title">Rekap Penjualan Produk</div>
    <table class="compact">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="40%">Nama Produk</th>
                <th width="15%">Qty Terjual</th>
                <th width="20%">Total Pendapatan</th>
                <th width="10%">Harga Rata</th>
                <th width="10%">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productSales as $index => $product)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ Str::limit($product->nama_produk, 30) }}</td>
                <td class="text-center">{{ $product->total_terjual }}</td>
                <td class="text-right">Rp {{ number_format($product->total_pendapatan, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($product->total_terjual > 0 ? $product->total_pendapatan / $product->total_terjual : 0, 0, ',', '.') }}</td>
                <td class="text-center">
                    @php
                        $persentase = $summary['total_pendapatan'] > 0 ? ($product->total_pendapatan / $summary['total_pendapatan']) * 100 : 0;
                    @endphp
                    {{ number_format($persentase, 1) }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>*** Laporan Bulanan - Sistem Kasir ***</p>
    </div>
</body>
</html>