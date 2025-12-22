<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\DB;

class WeeklyReportExport implements WithMultipleSheets
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        // Sheet 1: Ringkasan Transaksi
        $sheets[] = new WeeklyTransactionSummarySheet($this->startDate, $this->endDate);
        
        // Sheet 2: Detail Transaksi
        $sheets[] = new WeeklyTransactionDetailSheet($this->startDate, $this->endDate);
        
        // Sheet 3: Penjualan Produk
        $sheets[] = new WeeklyProductSalesSheet($this->startDate, $this->endDate);
        
        return $sheets;
    }
}

class WeeklyTransactionSummarySheet implements FromCollection, WithHeadings, WithTitle
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $transactions = Transaction::with('user')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get();

        $summary = [
            [
                'Periode' => $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y'),
                'Total Transaksi' => $transactions->count(),
                'Total Pendapatan' => 'Rp ' . number_format($transactions->sum('total_harga'), 0, ',', '.'),
                'Rata-rata Transaksi' => 'Rp ' . number_format($transactions->count() > 0 ? $transactions->sum('total_harga') / $transactions->count() : 0, 0, ',', '.'),
            ]
        ];

        return collect($summary);
    }

    public function headings(): array
    {
        return ['Periode', 'Total Transaksi', 'Total Pendapatan', 'Rata-rata Transaksi'];
    }

    public function title(): string
    {
        return 'Ringkasan Mingguan';
    }
}

class WeeklyTransactionDetailSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return Transaction::with(['user', 'transactionDetails.product'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
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
        })->implode("\n");

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

class WeeklyProductSalesSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
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
            ->whereBetween('transactions.created_at', [$this->startDate, $this->endDate])
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
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
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