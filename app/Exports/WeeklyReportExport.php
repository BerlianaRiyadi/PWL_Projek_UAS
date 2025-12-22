<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class WeeklyReportExport implements WithMultipleSheets, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $transactions;
    protected $productSales;
    protected $paymentSummary;
    protected $dailySummary;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        // Get data once for all sheets
        $this->transactions = Transaction::with(['user', 'transactionDetails.product'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        $this->productSales = TransactionDetail::select(
                'products.nama_produk',
                DB::raw('SUM(transaction_details.kuantitas) as total_terjual'),
                DB::raw('SUM(transaction_details.subtotal) as total_pendapatan')
            )
            ->join('transactions', 'transaction_details.transaksi_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.produk_id', '=', 'products.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.nama_produk')
            ->orderByDesc('total_terjual')
            ->get();

        $this->paymentSummary = Transaction::select(
                'metode_pembayaran',
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(total_harga) as total_pendapatan')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('metode_pembayaran')
            ->get();

        $this->dailySummary = Transaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(total_harga) as total_pendapatan')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function sheets(): array
    {
        $sheets = [];
        
        $sheets[] = new WeeklySummarySheet(
            $this->startDate, 
            $this->endDate, 
            $this->transactions, 
            $this->productSales,
            $this->paymentSummary
        );
        
        $sheets[] = new WeeklyTransactionSheet($this->transactions);
        
        $sheets[] = new WeeklyProductSheet($this->productSales, $this->transactions->sum('total_harga'));
        
        $sheets[] = new WeeklyDailySheet($this->dailySummary);
        
        $sheets[] = new WeeklyPaymentSheet($this->paymentSummary, $this->transactions->count(), $this->transactions->sum('total_harga'));
        
        return $sheets;
    }
}

class WeeklySummarySheet implements FromCollection, WithTitle, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $transactions;
    protected $productSales;
    protected $paymentSummary;

    public function __construct($startDate, $endDate, $transactions, $productSales, $paymentSummary)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->transactions = $transactions;
        $this->productSales = $productSales;
        $this->paymentSummary = $paymentSummary;
    }

    public function collection()
    {
        $totalPendapatan = $this->transactions->sum('total_harga');
        $totalItems = $this->transactions->flatMap->transactionDetails->sum('kuantitas');
        
        // Top 5 produk
        $topProducts = $this->productSales->take(5);
        
        // Summary data
        $data = [
            ['LAPORAN PENJUALAN MINGGUAN', '', '', '', ''],
            ['TOKO KASIR', '', '', '', ''],
            ['', '', '', '', ''],
            ['PERIODE', $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y'), '', 'Dicetak', now()->format('d/m/Y H:i:s')],
            ['', '', '', '', ''],
            ['RINGKASAN UTAMA', '', '', '', ''],
            ['Total Transaksi', $this->transactions->count(), '', 'Total Item Terjual', $totalItems],
            ['Total Pendapatan', 'Rp ' . number_format($totalPendapatan, 0, ',', '.'), '', 'Rata Transaksi', 'Rp ' . number_format($this->transactions->count() > 0 ? $totalPendapatan / $this->transactions->count() : 0, 0, ',', '.')],
            ['', '', '', '', ''],
            ['STATISTIK PEMBAYARAN', '', '', '', ''],
        ];
        
        foreach ($this->paymentSummary as $payment) {
            $percentage = $totalPendapatan > 0 ? ($payment->total_pendapatan / $totalPendapatan) * 100 : 0;
            $data[] = [
                strtoupper($payment->metode_pembayaran),
                $payment->total_transaksi . ' transaksi',
                'Rp ' . number_format($payment->total_pendapatan, 0, ',', '.'),
                number_format($percentage, 1) . '%',
                ''
            ];
        }
        
        $data[] = ['', '', '', '', ''];
        $data[] = ['PRODUK TERLARIS (TOP 5)', '', '', '', ''];
        $data[] = ['Nama Produk', 'Qty Terjual', 'Total Pendapatan', 'Harga Rata', 'Persentase'];
        
        foreach ($topProducts as $product) {
            $percentage = $totalPendapatan > 0 ? ($product->total_pendapatan / $totalPendapatan) * 100 : 0;
            $avgPrice = $product->total_terjual > 0 ? $product->total_pendapatan / $product->total_terjual : 0;
            $data[] = [
                $product->nama_produk,
                $product->total_terjual,
                'Rp ' . number_format($product->total_pendapatan, 0, ',', '.'),
                'Rp ' . number_format($avgPrice, 0, ',', '.'),
                number_format($percentage, 1) . '%'
            ];
        }
        
        return collect($data);
    }

    public function title(): string
    {
        return 'RINGKASAN';
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells for title
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A6:E6');
        $sheet->mergeCells('A10:E10');
        $sheet->mergeCells('A17:E17');
        
        // Style for titles
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A10')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A17')->getFont()->setBold(true)->setSize(12);
        
        // Center align titles
        $sheet->getStyle('A1:E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style for headers
        $sheet->getStyle('A17:E17')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A17:E17')->getFont()->setBold(true);
        
        // Borders
        $sheet->getStyle('A17:E' . (17 + $this->productSales->count()))->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['bold' => true, 'size' => 14]],
            6 => ['font' => ['bold' => true, 'size' => 12]],
            10 => ['font' => ['bold' => true, 'size' => 12]],
            17 => ['font' => ['bold' => true]],
        ];
    }
}

class WeeklyTransactionSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'NO',
            'ID TRANSAKSI',
            'TANGGAL',
            'WAKTU',
            'KASIR',
            'TOTAL HARGA',
            'JUMLAH BAYAR',
            'KEMBALIAN',
            'METODE BAYAR',
            'JUMLAH ITEM',
            'DETAIL ITEM'
        ];
    }

    public function map($transaction): array
    {
        $items = $transaction->transactionDetails->map(function ($detail) {
            return sprintf(
                "%s (%d x Rp %s) = Rp %s",
                $detail->product->nama_produk,
                $detail->kuantitas,
                number_format($detail->harga_saat_transaksi, 0, ',', '.'),
                number_format($detail->subtotal, 0, ',', '.')
            );
        })->implode("\n");

        return [
            '', // NO akan diisi di mapping
            'TRX-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
            $transaction->created_at->format('d/m/Y'),
            $transaction->created_at->format('H:i:s'),
            $transaction->user->name,
            $transaction->total_harga,
            $transaction->jumlah_bayar,
            $transaction->kembalian,
            strtoupper($transaction->metode_pembayaran),
            $transaction->transactionDetails->sum('kuantitas'),
            $items
        ];
    }

    public function title(): string
    {
        return 'TRANSAKSI';
    }

    public function styles(Worksheet $sheet)
    {
        // Auto size columns
        foreach(range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Set wrap text for item details
        $sheet->getStyle('K2:K' . ($this->transactions->count() + 1))->getAlignment()->setWrapText(true);
        
        // Header style
        $sheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4CAF50');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        
        // Number formatting
        $sheet->getStyle('F2:H' . ($this->transactions->count() + 1))->getNumberFormat()->setFormatCode('#,##0');
        
        // Borders
        $lastRow = $this->transactions->count() + 1;
        $sheet->getStyle('A1:K' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add row numbers
        for ($i = 0; $i < $this->transactions->count(); $i++) {
            $sheet->setCellValue('A' . ($i + 2), $i + 1);
        }
        
        return [];
    }
}

class WeeklyProductSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $productSales;
    protected $totalPendapatan;

    public function __construct($productSales, $totalPendapatan)
    {
        $this->productSales = $productSales;
        $this->totalPendapatan = $totalPendapatan;
    }

    public function collection()
    {
        return $this->productSales;
    }

    public function headings(): array
    {
        return [
            'NO',
            'NAMA PRODUK',
            'QTY TERJUAL',
            'TOTAL PENDAPATAN',
            'HARGA RATA-RATA',
            'PERSENTASE'
        ];
    }

    public function map($product): array
    {
        $percentage = $this->totalPendapatan > 0 ? ($product->total_pendapatan / $this->totalPendapatan) * 100 : 0;
        $avgPrice = $product->total_terjual > 0 ? $product->total_pendapatan / $product->total_terjual : 0;
        
        return [
            '', // NO akan diisi di mapping
            $product->nama_produk,
            $product->total_terjual,
            $product->total_pendapatan,
            $avgPrice,
            $percentage
        ];
    }

    public function title(): string
    {
        return 'PRODUK';
    }

    public function styles(Worksheet $sheet)
    {
        // Auto size columns
        foreach(range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Header style
        $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2196F3');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        
        // Number formatting
        $lastRow = $this->productSales->count() + 1;
        $sheet->getStyle('C2:C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D2:E' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode('0.00"%"');
        
        // Borders
        $sheet->getStyle('A1:F' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add row numbers and format currency
        for ($i = 0; $i < $this->productSales->count(); $i++) {
            $row = $i + 2;
            $sheet->setCellValue('A' . $row, $i + 1);
            
            // Format currency with Rp
            $sheet->setCellValue('D' . $row, $this->productSales[$i]->total_pendapatan);
            $sheet->setCellValue('E' . $row, $this->productSales[$i]->total_terjual > 0 ? 
                $this->productSales[$i]->total_pendapatan / $this->productSales[$i]->total_terjual : 0);
            
            // Add Rp prefix through style
            $sheet->getStyle('D' . $row . ':E' . $row)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        }
        
        return [];
    }
}

class WeeklyDailySheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $dailySummary;

    public function __construct($dailySummary)
    {
        $this->dailySummary = $dailySummary;
    }

    public function collection()
    {
        return $this->dailySummary;
    }

    public function headings(): array
    {
        return [
            'NO',
            'TANGGAL',
            'TOTAL TRANSAKSI',
            'TOTAL PENDAPATAN',
            'RATA-RATA PER TRANSAKSI'
        ];
    }

    public function map($daily): array
    {
        $avgTransaction = $daily->total_transaksi > 0 ? $daily->total_pendapatan / $daily->total_transaksi : 0;
        
        return [
            '', // NO akan diisi di mapping
            Carbon::parse($daily->date)->format('d/m/Y'),
            $daily->total_transaksi,
            $daily->total_pendapatan,
            $avgTransaction
        ];
    }

    public function title(): string
    {
        return 'HARIAN';
    }

    public function styles(Worksheet $sheet)
    {
        // Auto size columns
        foreach(range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Header style
        $sheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF9800');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        
        // Number formatting
        $lastRow = $this->dailySummary->count() + 1;
        $sheet->getStyle('C2:C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D2:E' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        
        // Borders
        $sheet->getStyle('A1:E' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add row numbers
        for ($i = 0; $i < $this->dailySummary->count(); $i++) {
            $sheet->setCellValue('A' . ($i + 2), $i + 1);
        }
        
        return [];
    }
}

class WeeklyPaymentSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $paymentSummary;
    protected $totalTransactions;
    protected $totalPendapatan;

    public function __construct($paymentSummary, $totalTransactions, $totalPendapatan)
    {
        $this->paymentSummary = $paymentSummary;
        $this->totalTransactions = $totalTransactions;
        $this->totalPendapatan = $totalPendapatan;
    }

    public function collection()
    {
        return $this->paymentSummary;
    }

    public function headings(): array
    {
        return [
            'NO',
            'METODE PEMBAYARAN',
            'JUMLAH TRANSAKSI',
            'TOTAL PENDAPATAN',
            'PERSENTASE TRANSAKSI',
            'PERSENTASE PENDAPATAN'
        ];
    }

    public function map($payment): array
    {
        $transactionPercentage = $this->totalTransactions > 0 ? ($payment->total_transaksi / $this->totalTransactions) * 100 : 0;
        $incomePercentage = $this->totalPendapatan > 0 ? ($payment->total_pendapatan / $this->totalPendapatan) * 100 : 0;
        
        return [
            '', // NO akan diisi di mapping
            strtoupper($payment->metode_pembayaran),
            $payment->total_transaksi,
            $payment->total_pendapatan,
            $transactionPercentage,
            $incomePercentage
        ];
    }

    public function title(): string
    {
        return 'PEMBAYARAN';
    }

    public function styles(Worksheet $sheet)
    {
        // Auto size columns
        foreach(range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Header style
        $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF9C27B0');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        
        // Number formatting
        $lastRow = $this->paymentSummary->count() + 1;
        $sheet->getStyle('C2:C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('E2:F' . $lastRow)->getNumberFormat()->setFormatCode('0.00"%"');
        
        // Borders
        $sheet->getStyle('A1:F' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add row numbers
        for ($i = 0; $i < $this->paymentSummary->count(); $i++) {
            $sheet->setCellValue('A' . ($i + 2), $i + 1);
        }
        
        // Add total row
        $totalRow = $this->paymentSummary->count() + 3;
        $sheet->setCellValue('B' . $totalRow, 'TOTAL');
        $sheet->setCellValue('C' . $totalRow, $this->totalTransactions);
        $sheet->setCellValue('D' . $totalRow, $this->totalPendapatan);
        $sheet->setCellValue('E' . $totalRow, '100%');
        $sheet->setCellValue('F' . $totalRow, '100%');
        
        // Style total row
        $sheet->getStyle('B' . $totalRow . ':F' . $totalRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFEB3B');
        $sheet->getStyle('B' . $totalRow . ':F' . $totalRow)->getFont()->setBold(true);
        $sheet->getStyle('C' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D' . $totalRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('E' . $totalRow . ':F' . $totalRow)->getNumberFormat()->setFormatCode('0"%"');
        
        return [];
    }
}