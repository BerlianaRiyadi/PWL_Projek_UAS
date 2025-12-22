<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $transaksi->id }}</title>
    <style>
        /* RESET & PRINT STYLES */
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* GLOBAL STYLES */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Courier New', 'Consolas', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #f5f5f5;
            padding: 0;
            margin: 0;
        }
        
        .receipt-container {
            width: 80mm;
            background: white;
            padding: 10mm;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 10mm;
            padding-bottom: 5mm;
            border-bottom: 2px dashed #000;
        }
        
        .store-name {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 3mm;
        }
        
        .store-info {
            font-size: 10px;
            line-height: 1.5;
            color: #333;
        }
        
        /* TRANSACTION INFO */
        .transaction-info {
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 1px dashed #000;
        }
        
        .transaction-id {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 3mm;
            letter-spacing: 1px;
        }
        
        .info-line {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 2mm;
        }
        
        .info-label {
            font-weight: normal;
        }
        
        .info-value {
            font-weight: bold;
        }
        
        /* ITEMS */
        .items-section {
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 1px dashed #000;
        }
        
        .items-header {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 2mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #000;
        }
        
        .item {
            margin-bottom: 3mm;
            font-size: 11px;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        
        .item-qty-price {
            color: #666;
        }
        
        .item-subtotal {
            font-weight: bold;
        }
        
        /* TOTALS */
        .totals-section {
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 2px dashed #000;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 2mm;
        }
        
        .total-line.grand-total {
            font-size: 14px;
            font-weight: bold;
            margin-top: 3mm;
            padding-top: 3mm;
            border-top: 1px solid #000;
        }
        
        /* FOOTER */
        .footer {
            text-align: center;
            margin-top: 5mm;
        }
        
        .thank-you {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3mm;
            letter-spacing: 1px;
        }
        
        .footer-note {
            font-size: 9px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 2mm;
        }
        
        .barcode {
            text-align: center;
            font-family: 'Libre Barcode 128', cursive;
            font-size: 40px;
            margin: 5mm 0;
            letter-spacing: 0;
        }
        
        .barcode-number {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: -3mm;
        }
        
        .print-time {
            font-size: 8px;
            color: #999;
            text-align: center;
            margin-top: 3mm;
        }
        
        /* RESPONSIVE */
        @media screen and (max-width: 600px) {
            body {
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
            }
        }
    </style>
    
    <!-- Barcode Font -->
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
</head>

<body>
    <div class="receipt-container">
        <!-- HEADER -->
        <div class="header">
            <div class="store-name">TOKO MODERN</div>
            <div class="store-info">
                Jl. Raya Pare Wates, Sumber Agung, Plosoklaten<br>
                Telp: (021) 1234-5678<br>
                www.asajayaplayground.com
            </div>
        </div>

        <!-- TRANSACTION INFO -->
        <div class="transaction-info">
            <div class="transaction-id">STRUK #{{ str_pad($transaksi->id, 6, '0', STR_PAD_LEFT) }}</div>
            
            <div class="info-line">
                <span class="info-label">Tanggal</span>
                <span class="info-value">{{ $transaksi->created_at->format('d/m/Y H:i') }}</span>
            </div>
            
            <div class="info-line">
                <span class="info-label">Kasir</span>
                <span class="info-value">{{ $transaksi->user->name }}</span>
            </div>
            
            <div class="info-line">
                <span class="info-label">Metode</span>
                <span class="info-value">{{ strtoupper($transaksi->metode_pembayaran) }}</span>
            </div>
        </div>

        <!-- ITEMS -->
        <div class="items-section">
            <div class="items-header">DETAIL PEMBELIAN</div>
            
            @foreach($transaksi->transactionDetails as $detail)
            <div class="item">
                <div class="item-name">{{ $detail->product->nama_produk }}</div>
                <div class="item-details">
                    <span class="item-qty-price">
                        {{ $detail->kuantitas }} x Rp {{ number_format($detail->harga_saat_transaksi, 0, ',', '.') }}
                    </span>
                    <span class="item-subtotal">
                        Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>

        <!-- TOTALS -->
        <div class="totals-section">
            <div class="total-line">
                <span>Subtotal</span>
                <span>Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span>
            </div>
            
            <div class="total-line">
                <span>Dibayar</span>
                <span>Rp {{ number_format($transaksi->jumlah_bayar, 0, ',', '.') }}</span>
            </div>
            
            <div class="total-line">
                <span>Kembalian</span>
                <span>Rp {{ number_format($transaksi->kembalian, 0, ',', '.') }}</span>
            </div>
            
            <div class="total-line grand-total">
                <span>TOTAL</span>
                <span>Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- BARCODE -->
        <div class="barcode">*{{ str_pad($transaksi->id, 10, '0', STR_PAD_LEFT) }}*</div>
        <div class="barcode-number">{{ str_pad($transaksi->id, 10, '0', STR_PAD_LEFT) }}</div>

        <!-- FOOTER -->
        <div class="footer">
            <div class="thank-you">TERIMA KASIH</div>
            <div class="footer-note">
                Barang yang sudah dibeli<br>
                tidak dapat ditukar/dikembalikan
            </div>
            <div class="footer-note">
                Struk ini adalah bukti pembayaran yang sah
            </div>
            <div class="print-time">
                Dicetak: {{ date('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };

        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 500);
        };
    </script>
</body>
</html>