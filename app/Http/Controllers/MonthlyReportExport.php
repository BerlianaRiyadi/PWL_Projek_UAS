<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonthlyReportExport implements WithMultipleSheets
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        $sheets[] = new MonthlyTransactionSummarySheet($this->month);
        $sheets[] = new MonthlyTransactionDetailSheet($this->month);
        $sheets[] = new MonthlyProductSalesSheet($this->month);
        $sheets[] = new MonthlyDailySummarySheet($this->month);
        $sheets[] = new MonthlyKasirPerformanceSheet($this->month);
        
        return $sheets;
    }
}

class MonthlyTransactionSummarySheet implements FromCollection, WithHeadings, WithTitle
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection()
    {
        $transactions = Transaction::where('status', 'completed')
            ->whereYear('created_at', $this->month->year)
            ->whereMonth('created_at', $this->month->month)
            ->get();

        $summary = [
            [
                'Bulan' => $this->month->translatedFormat('F Y'),
                'Total Transaksi' => $transactions->count(),
                'Total Pendapatan' => 'Rp ' . number_format($transactions->sum('total_harga'), 0, ',', '.'),
                'Rata-rata Harian' => 'Rp ' . number_format($transactions->count() > 0 ? $transactions->sum('total_harga') / $this->month->daysInMonth : 0, 0, ',', '.'),
                'Rata-rata Transaksi' => 'Rp ' . number_format($transactions->count() > 0 ? $transactions->sum('total_harga') / $transactions->count() : 0, 0, ',', '.'),
            ]
        ];

        return collect($summary);
    }

    public function headings(): array
    {
        return ['Bulan', 'Total Transaksi', 'Total Pendapatan', 'Rata-rata Harian', 'Rata-rata Transaksi'];
    }

    public function title(): string
    {
        return 'Ringkasan Bulanan';
    }
}

class MonthlyTransactionDetailSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection()
    {
        return Transaction::with(['user', 'transactionDetails.product'])
            ->where('status', 'completed')
            ->whereYear('created_at', $this->month->year)
            ->whereMonth('created_at', $this->month->month)
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID Transaksi',
            'Tanggal',
            'Waktu',
            'Kasir',
            'Total Harga',
            'Jumlah Bayar',
            'Kembalian',
            'Metode Pembayaran',
            'Jumlah Item',
            'Detail Item'
        ];
    }

    public function map($transaction): array
    {
        $items = $transaction->transactionDetails->map(function ($detail) {
            return $detail->product->nama_produk . ' (' . $detail->kuantitas . ' x Rp ' . number_format($detail->harga_saat_transaksi, 0, ',', '.') . ') = Rp ' . number_format($detail->subtotal, 0, ',', '.');
        })->implode("; ");

        return [
            'TRX-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
            $transaction->created_at->format('d/m/Y'),
            $transaction->created_at->format('H:i:s'),
            $transaction->user->name,
            'Rp ' . number_format($transaction->total_harga, 0, ',', '.'),
            'Rp ' . number_format($transaction->jumlah_bayar, 0, ',', '.'),
            'Rp ' . number_format($transaction->kembalian, 0, ',', '.'),
            strtoupper($transaction->metode_pembayaran),
            $transaction->transactionDetails->sum('kuantitas'),
            $items
        ];
    }

    public function title(): string
    {
        return 'Detail Transaksi';
    }
}

class MonthlyProductSalesSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection()
    {
        return TransactionDetail::select(
                'products.nama_produk',
                DB::raw('SUM(transaction_details.kuantitas) as total_terjual'),
                DB::raw('SUM(transaction_details.subtotal) as total_pendapatan'),
                DB::raw('AVG(products.harga) as harga_rata')
            )
            ->join('transactions', 'transaction_details.transaksi_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.produk_id', '=', 'products.id')
            ->where('transactions.status', 'completed')
            ->whereYear('transactions.created_at', $this->month->year)
            ->whereMonth('transactions.created_at', $this->month->month)
            ->groupBy('products.id', 'products.nama_produk')
            ->orderByDesc('total_terjual')
            ->get();
    }

    public function headings(): array
    {
        return ['Nama Produk', 'Total Terjual', 'Total Pendapatan', 'Harga Rata-rata', 'Persentase'];
    }

    public function map($product): array
    {
        $totalPendapatan = Transaction::where('status', 'completed')
            ->whereYear('created_at', $this->month->year)
            ->whereMonth('created_at', $this->month->month)
            ->sum('total_harga');
        
        $persentase = $totalPendapatan > 0 ? ($product->total_pendapatan / $totalPendapatan) * 100 : 0;

        return [
            $product->nama_produk,
            $product->total_terjual,
            'Rp ' . number_format($product->total_pendapatan, 0, ',', '.'),
            'Rp ' . number_format($product->harga_rata, 0, ',', '.'),
            number_format($persentase, 2) . '%'
        ];
    }

    public function title(): string
    {
        return 'Penjualan Produk';
    }
}

class MonthlyDailySummarySheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection()
    {
        return Transaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(total_harga) as total_pendapatan'),
                DB::raw('AVG(total_harga) as rata_transaksi')
            )
            ->where('status', 'completed')
            ->whereYear('created_at', $this->month->year)
            ->whereMonth('created_at', $this->month->month)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'Total Transaksi', 'Total Pendapatan', 'Rata-rata Transaksi'];
    }

    public function map($daily): array
    {
        return [
            Carbon::parse($daily->date)->format('d/m/Y'),
            $daily->total_transaksi,
            'Rp ' . number_format($daily->total_pendapatan, 0, ',', '.'),
            'Rp ' . number_format($daily->rata_transaksi, 0, ',', '.')
        ];
    }

    public function title(): string
    {
        return 'Statistik Harian';
    }
}

class MonthlyKasirPerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection()
    {
        return Transaction::select(
                'users.name',
                DB::raw('COUNT(transactions.id) as total_transaksi'),
                DB::raw('SUM(transactions.total_harga) as total_pendapatan'),
                DB::raw('AVG(transactions.total_harga) as rata_transaksi')
            )
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactions.status', 'completed')
            ->whereYear('transactions.created_at', $this->month->year)
            ->whereMonth('transactions.created_at', $this->month->month)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_pendapatan')
            ->get();
    }

    public function headings(): array
    {
        return ['Nama Kasir', 'Total Transaksi', 'Total Pendapatan', 'Rata-rata Transaksi', 'Persentase'];
    }

    public function map($kasir): array
    {
        $totalPendapatan = Transaction::where('status', 'completed')
            ->whereYear('created_at', $this->month->year)
            ->whereMonth('created_at', $this->month->month)
            ->sum('total_harga');
        
        $persentase = $totalPendapatan > 0 ? ($kasir->total_pendapatan / $totalPendapatan) * 100 : 0;

        return [
            $kasir->name,
            $kasir->total_transaksi,
            'Rp ' . number_format($kasir->total_pendapatan, 0, ',', '.'),
            'Rp ' . number_format($kasir->rata_transaksi, 0, ',', '.'),
            number_format($persentase, 2) . '%'
        ];
    }

    public function title(): string
    {
        return 'Performansi Kasir';
    }
}