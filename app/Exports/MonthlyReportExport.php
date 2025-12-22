<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class MonthlyReportExport implements WithMultipleSheets, ShouldAutoSize
{
    protected $month;
    protected $transactions;
    protected $productSales;
    protected $paymentSummary;
    protected $dailySummary;
    protected $kasirPerformance;
    protected $totalPendapatan;
    protected $totalTransactions;

    public function __construct($month)
    {
        $this->month = $month;
        
        // Get all data once for performance
        $this->transactions = Transaction::with(['user', 'transactionDetails.product'])
            ->where('status', 'completed')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->latest()
            ->get();

        $this->totalPendapatan = $this->transactions->sum('total_harga');
        $this->totalTransactions = $this->transactions->count();

        $this->productSales = TransactionDetail::select(
                'products.id',
                'products.nama_produk',
                DB::raw('SUM(transaction_details.kuantitas) as total_terjual'),
                DB::raw('SUM(transaction_details.subtotal) as total_pendapatan'),
                DB::raw('AVG(products.harga) as harga_rata')
            )
            ->join('transactions', 'transaction_details.transaksi_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.produk_id', '=', 'products.id')
            ->where('transactions.status', 'completed')
            ->whereYear('transactions.created_at', $month->year)
            ->whereMonth('transactions.created_at', $month->month)
            ->groupBy('products.id', 'products.nama_produk')
            ->orderByDesc('total_terjual')
            ->get();

        $this->paymentSummary = Transaction::select(
                'metode_pembayaran',
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(total_harga) as total_pendapatan')
            )
            ->where('status', 'completed')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->groupBy('metode_pembayaran')
            ->get();

        $this->dailySummary = Transaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(total_harga) as total_pendapatan'),
                DB::raw('AVG(total_harga) as rata_transaksi')
            )
            ->where('status', 'completed')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->kasirPerformance = Transaction::select(
                'users.id',
                'users.name',
                DB::raw('COUNT(transactions.id) as total_transaksi'),
                DB::raw('SUM(transactions.total_harga) as total_pendapatan'),
                DB::raw('AVG(transactions.total_harga) as rata_transaksi'),
                DB::raw('MAX(transactions.created_at) as transaksi_terakhir')
            )
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactions.status', 'completed')
            ->whereYear('transactions.created_at', $month->year)
            ->whereMonth('transactions.created_at', $month->month)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_pendapatan')
            ->get();
    }

    public function sheets(): array
    {
        $sheets = [];
        
        $sheets[] = new MonthlySummarySheet(
            $this->month, 
            $this->transactions, 
            $this->productSales,
            $this->paymentSummary,
            $this->dailySummary,
            $this->kasirPerformance,
            $this->totalPendapatan
        );
        
        $sheets[] = new MonthlyTransactionSheet($this->transactions);
        
        $sheets[] = new MonthlyProductSheet($this->productSales, $this->totalPendapatan);
        
        $sheets[] = new MonthlyDailySheet($this->dailySummary, $this->month);
        
        $sheets[] = new MonthlyPaymentSheet($this->paymentSummary, $this->totalTransactions, $this->totalPendapatan);
        
        $sheets[] = new MonthlyKasirSheet($this->kasirPerformance, $this->totalPendapatan, $this->totalTransactions);
        
        return $sheets;
    }
}

class MonthlySummarySheet implements FromCollection, WithTitle, WithStyles, ShouldAutoSize
{
    protected $month;
    protected $transactions;
    protected $productSales;
    protected $paymentSummary;
    protected $dailySummary;
    protected $kasirPerformance;
    protected $totalPendapatan;
    protected $totalItems;

    public function __construct($month, $transactions, $productSales, $paymentSummary, $dailySummary, $kasirPerformance, $totalPendapatan)
    {
        $this->month = $month;
        $this->transactions = $transactions;
        $this->productSales = $productSales;
        $this->paymentSummary = $paymentSummary;
        $this->dailySummary = $dailySummary;
        $this->kasirPerformance = $kasirPerformance;
        $this->totalPendapatan = $totalPendapatan;
        $this->totalItems = $transactions->flatMap->transactionDetails->sum('kuantitas');
    }

    public function collection()
    {
        $topProducts = $this->productSales->take(5);
        $topKasir = $this->kasirPerformance->take(3);
        
        // Build summary data
        $data = [
            ['LAPORAN PENJUALAN BULANAN', '', '', '', '', ''],
            ['TOKO KASIR - SISTEM KASIR MODERN', '', '', '', '', ''],
            ['', '', '', '', '', ''],
            ['BULAN', $this->month->translatedFormat('F Y'), '', 'DICETAK', now()->format('d/m/Y H:i:s'), ''],
            ['', '', '', '', '', ''],
            ['RINGKASAN UTAMA BULAN INI', '', '', '', '', ''],
            ['Total Transaksi', $this->transactions->count(), '', 'Total Item Terjual', $this->totalItems, ''],
            ['Total Pendapatan', 'Rp ' . number_format($this->totalPendapatan, 0, ',', '.'), '', 'Rata Transaksi', 'Rp ' . number_format($this->transactions->count() > 0 ? $this->totalPendapatan / $this->transactions->count() : 0, 0, ',', '.'), ''],
            ['Rata Harian', 'Rp ' . number_format($this->transactions->count() > 0 ? $this->totalPendapatan / $this->month->daysInMonth : 0, 0, ',', '.'), '', 'Hari Aktif', $this->dailySummary->count() . ' hari', ''],
            ['', '', '', '', '', ''],
            ['STATISTIK HARIAN', '', '', '', '', ''],
            ['Hari Terbaik', $this->getBestDay(), '', 'Pendapatan Terbaik', 'Rp ' . number_format($this->getBestDayRevenue(), 0, ',', '.'), ''],
            ['Transaksi Terbanyak', $this->getMostTransactionsDay(), '', 'Jumlah Transaksi', $this->getMostTransactionsCount(), ''],
            ['', '', '', '', '', ''],
            ['METODE PEMBAYARAN', '', '', '', '', ''],
        ];
        
        // Payment methods
        $paymentRow = 16;
        foreach ($this->paymentSummary as $payment) {
            $percentage = $this->totalPendapatan > 0 ? ($payment->total_pendapatan / $this->totalPendapatan) * 100 : 0;
            $data[] = [
                strtoupper($payment->metode_pembayaran),
                $payment->total_transaksi . ' transaksi',
                'Rp ' . number_format($payment->total_pendapatan, 0, ',', '.'),
                number_format($percentage, 1) . '%',
                '',
                ''
            ];
            $paymentRow++;
        }
        
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['PRODUK TERLARIS (TOP 5)', '', '', '', '', ''];
        $data[] = ['No', 'Nama Produk', 'Qty Terjual', 'Total Pendapatan', 'Harga Rata', 'Persentase'];
        
        $productStartRow = $paymentRow + 3;
        foreach ($topProducts as $index => $product) {
            $percentage = $this->totalPendapatan > 0 ? ($product->total_pendapatan / $this->totalPendapatan) * 100 : 0;
            $avgPrice = $product->total_terjual > 0 ? $product->total_pendapatan / $product->total_terjual : 0;
            $data[] = [
                $index + 1,
                $product->nama_produk,
                $product->total_terjual,
                'Rp ' . number_format($product->total_pendapatan, 0, ',', '.'),
                'Rp ' . number_format($avgPrice, 0, ',', '.'),
                number_format($percentage, 1) . '%'
            ];
        }
        
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['KASIR TERBAIK (TOP 3)', '', '', '', '', ''];
        $data[] = ['No', 'Nama Kasir', 'Total Transaksi', 'Total Pendapatan', 'Rata Transaksi', 'Persentase'];
        
        $kasirStartRow = $productStartRow + $topProducts->count() + 4;
        foreach ($topKasir as $index => $kasir) {
            $percentage = $this->totalPendapatan > 0 ? ($kasir->total_pendapatan / $this->totalPendapatan) * 100 : 0;
            $data[] = [
                $index + 1,
                $kasir->name,
                $kasir->total_transaksi,
                'Rp ' . number_format($kasir->total_pendapatan, 0, ',', '.'),
                'Rp ' . number_format($kasir->rata_transaksi, 0, ',', '.'),
                number_format($percentage, 1) . '%'
            ];
        }
        
        return collect($data);
    }

    private function getBestDay()
    {
        if ($this->dailySummary->isEmpty()) return '-';
        
        $bestDay = $this->dailySummary->sortByDesc('total_pendapatan')->first();
        return Carbon::parse($bestDay->date)->format('d/m/Y');
    }

    private function getBestDayRevenue()
    {
        if ($this->dailySummary->isEmpty()) return 0;
        
        return $this->dailySummary->max('total_pendapatan');
    }

    private function getMostTransactionsDay()
    {
        if ($this->dailySummary->isEmpty()) return '-';
        
        $mostTransactions = $this->dailySummary->sortByDesc('total_transaksi')->first();
        return Carbon::parse($mostTransactions->date)->format('d/m/Y');
    }

    private function getMostTransactionsCount()
    {
        if ($this->dailySummary->isEmpty()) return 0;
        
        return $this->dailySummary->max('total_transaksi');
    }

    public function title(): string
    {
        return 'RINGKASAN';
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells for titles
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A6:F6');
        $sheet->mergeCells('A11:F11');
        $sheet->mergeCells('A15:F15');
        
        $productTitleRow = 16 + $this->paymentSummary->count() + 1;
        $sheet->mergeCells('A' . $productTitleRow . ':F' . $productTitleRow);
        
        $kasirTitleRow = $productTitleRow + $this->productSales->count() + 3;
        $sheet->mergeCells('A' . $kasirTitleRow . ':F' . $kasirTitleRow);
        
        // Style for main titles
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A11')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A15')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $productTitleRow)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $kasirTitleRow)->getFont()->setBold(true)->setSize(12);
        
        // Center align main titles
        $sheet->getStyle('A1:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style for section headers
        $sectionHeaders = [
            $productTitleRow + 1,
            $kasirTitleRow + 1
        ];
        
        foreach ($sectionHeaders as $row) {
            $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
            $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
        }
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        
        // Apply borders to data tables
        $productDataStart = $productTitleRow + 1;
        $productDataEnd = $productDataStart + min($this->productSales->count(), 5);
        $sheet->getStyle('A' . $productDataStart . ':F' . $productDataEnd)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $kasirDataStart = $kasirTitleRow + 1;
        $kasirDataEnd = $kasirDataStart + min($this->kasirPerformance->count(), 3);
        $sheet->getStyle('A' . $kasirDataStart . ':F' . $kasirDataEnd)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        return [
            1  => ['font' => ['bold' => true, 'size' => 18]],
            2  => ['font' => ['bold' => true, 'size' => 14]],
            6  => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']]],
            11 => ['font' => ['bold' => true, 'size' => 12]],
            15 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}

class MonthlyTransactionSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
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
            'DETAIL ITEM (PRODUK - QTY - HARGA - SUBTOTAL)'
        ];
    }

    public function map($transaction): array
    {
        $items = $transaction->transactionDetails->map(function ($detail) {
            return sprintf(
                "%s | %d x Rp %s | Rp %s",
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
        $lastRow = $this->transactions->count() + 1;
        
        // Auto size columns
        foreach(range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Set specific widths
        $sheet->getColumnDimension('K')->setWidth(50);
        
        // Header style
        $sheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4CAF50');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set wrap text for item details
        $sheet->getStyle('K2:K' . $lastRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        
        // Number formatting for currency
        $sheet->getStyle('F2:H' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        
        // Center align for some columns
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Borders
        $sheet->getStyle('A1:K' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add row numbers and format
        for ($i = 0; $i < $this->transactions->count(); $i++) {
            $row = $i + 2;
            $sheet->setCellValue('A' . $row, $i + 1);
            
            // Format detail items with line breaks
            $transaction = $this->transactions[$i];
            $itemsText = '';
            foreach ($transaction->transactionDetails as $detail) {
                $itemsText .= sprintf(
                    "• %s\n  Qty: %d x Rp %s = Rp %s\n",
                    $detail->product->nama_produk,
                    $detail->kuantitas,
                    number_format($detail->harga_saat_transaksi, 0, ',', '.'),
                    number_format($detail->subtotal, 0, ',', '.')
                );
            }
            $sheet->setCellValue('K' . $row, $itemsText);
        }
        
        // Add summary row
        $summaryRow = $lastRow + 2;
        $sheet->setCellValue('E' . $summaryRow, 'TOTAL BULAN INI:');
        $sheet->setCellValue('F' . $summaryRow, $this->transactions->sum('total_harga'));
        $sheet->mergeCells('E' . $summaryRow . ':G' . $summaryRow);
        
        // Style summary row
        $sheet->getStyle('E' . $summaryRow . ':G' . $summaryRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFEB3B');
        $sheet->getStyle('E' . $summaryRow . ':G' . $summaryRow)->getFont()->setBold(true);
        $sheet->getStyle('F' . $summaryRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('E' . $summaryRow . ':G' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        return [];
    }
}

class MonthlyProductSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
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
            'RANK',
            'NAMA PRODUK',
            'KODE PRODUK',
            'QTY TERJUAL',
            'TOTAL PENDAPATAN',
            'HARGA RATA-RATA',
            'PERSENTASE',
            'KONTRIBUSI'
        ];
    }

    public function map($product): array
    {
        $percentage = $this->totalPendapatan > 0 ? ($product->total_pendapatan / $this->totalPendapatan) * 100 : 0;
        $avgPrice = $product->total_terjual > 0 ? $product->total_pendapatan / $product->total_terjual : 0;
        
        // Determine contribution level
        $contribution = '';
        if ($percentage >= 20) {
            $contribution = 'SANGAT TINGGI';
        } elseif ($percentage >= 10) {
            $contribution = 'TINGGI';
        } elseif ($percentage >= 5) {
            $contribution = 'SEDANG';
        } elseif ($percentage >= 1) {
            $contribution = 'RENDAH';
        } else {
            $contribution = 'SANGAT RENDAH';
        }
        
        return [
            '', // RANK akan diisi di mapping
            $product->nama_produk,
            'PROD-' . str_pad($product->id, 4, '0', STR_PAD_LEFT),
            $product->total_terjual,
            $product->total_pendapatan,
            $avgPrice,
            $percentage,
            $contribution
        ];
    }

    public function title(): string
    {
        return 'PRODUK';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->productSales->count() + 1;
        
        // Auto size columns
        foreach(range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Header style
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2196F3');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Number formatting
        $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E2:F' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()->setFormatCode('0.00"%"');
        
        // Center align for some columns
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H2:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Right align for currency
        $sheet->getStyle('E2:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Conditional formatting for contribution
        $contributionColumn = 'H';
        for ($i = 2; $i <= $lastRow; $i++) {
            $cellValue = $sheet->getCell($contributionColumn . $i)->getValue();
            $fillColor = '';
            
            switch ($cellValue) {
                case 'SANGAT TINGGI': $fillColor = 'FF4CAF50'; break; // Green
                case 'TINGGI': $fillColor = 'FF8BC34A'; break; // Light Green
                case 'SEDANG': $fillColor = 'FFFFC107'; break; // Yellow
                case 'RENDAH': $fillColor = 'FFFF9800'; break; // Orange
                case 'SANGAT RENDAH': $fillColor = 'FFF44336'; break; // Red
            }
            
            if ($fillColor) {
                $sheet->getStyle($contributionColumn . $i)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($fillColor);
                $sheet->getStyle($contributionColumn . $i)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            }
        }
        
        // Borders
        $sheet->getStyle('A1:H' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add row numbers (ranking)
        for ($i = 0; $i < $this->productSales->count(); $i++) {
            $row = $i + 2;
            $sheet->setCellValue('A' . $row, $i + 1);
            
            // Color code top 3
            if ($i < 3) {
                $color = '';
                switch ($i) {
                    case 0: $color = 'FFFFD700'; break; // Gold
                    case 1: $color = 'FFC0C0C0'; break; // Silver
                    case 2: $color = 'FFCD7F32'; break; // Bronze
                }
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            }
        }
        
        // Add summary
        $summaryRow = $lastRow + 2;
        $sheet->setCellValue('D' . $summaryRow, 'TOTAL:');
        $sheet->setCellValue('E' . $summaryRow, $this->productSales->sum('total_pendapatan'));
        $sheet->setCellValue('F' . $summaryRow, '100%');
        
        // Style summary
        $sheet->getStyle('D' . $summaryRow . ':F' . $summaryRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF9E9E9E');
        $sheet->getStyle('D' . $summaryRow . ':F' . $summaryRow)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('E' . $summaryRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('F' . $summaryRow)->getNumberFormat()->setFormatCode('0"%"');
        
        return [];
    }
}

class MonthlyDailySheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $dailySummary;
    protected $month;

    public function __construct($dailySummary, $month)
    {
        $this->dailySummary = $dailySummary;
        $this->month = $month;
    }

    public function collection()
    {
        return $this->dailySummary;
    }

    public function headings(): array
    {
        return [
            'HARI',
            'TANGGAL',
            'HARI DALAM MINGGU',
            'TOTAL TRANSAKSI',
            'TOTAL PENDAPATAN',
            'RATA PER TRANSAKSI',
            'TREND',
            'KETERANGAN'
        ];
    }

    public function map($daily): array
    {
        $date = Carbon::parse($daily->date);
        $avgTransaction = $daily->total_transaksi > 0 ? $daily->total_pendapatan / $daily->total_transaksi : 0;
        
        // Determine trend
        $trend = '';
        if ($daily->total_transaksi == 0) {
            $trend = 'TIDAK ADA TRANSAKSI';
        } elseif ($daily->total_transaksi >= 10) {
            $trend = 'SANGAT TINGGI';
        } elseif ($daily->total_transaksi >= 5) {
            $trend = 'TINGGI';
        } elseif ($daily->total_transaksi >= 2) {
            $trend = 'SEDANG';
        } else {
            $trend = 'RENDAH';
        }
        
        // Day description
        $description = '';
        if ($date->isWeekend()) {
            $description = 'WEEKEND';
        } elseif ($daily->total_transaksi >= 8) {
            $description = 'HARI SIBUK';
        } elseif ($daily->total_transaksi == 0) {
            $description = 'HARI SEPI';
        } else {
            $description = 'HARI NORMAL';
        }
        
        return [
            $date->day,
            $date->format('d/m/Y'),
            $date->translatedFormat('l'),
            $daily->total_transaksi,
            $daily->total_pendapatan,
            $avgTransaction,
            $trend,
            $description
        ];
    }

    public function title(): string
    {
        return 'HARIAN';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->dailySummary->count() + 1;
        
        // Auto size columns
        foreach(range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Header style
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF9800');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Number formatting
        $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E2:F' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        
        // Center align
        $sheet->getStyle('A2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G2:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Right align for currency
        $sheet->getStyle('E2:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Conditional formatting for trend
        $trendColumn = 'G';
        for ($i = 2; $i <= $lastRow; $i++) {
            $cellValue = $sheet->getCell($trendColumn . $i)->getValue();
            $fillColor = '';
            
            switch ($cellValue) {
                case 'SANGAT TINGGI': $fillColor = 'FF4CAF50'; break;
                case 'TINGGI': $fillColor = 'FF8BC34A'; break;
                case 'SEDANG': $fillColor = 'FFFFC107'; break;
                case 'RENDAH': $fillColor = 'FFFF9800'; break;
                case 'TIDAK ADA TRANSAKSI': $fillColor = 'FFF44336'; break;
            }
            
            if ($fillColor) {
                $sheet->getStyle($trendColumn . $i)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($fillColor);
                $sheet->getStyle($trendColumn . $i)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            }
        }
        
        // Conditional formatting for weekend
        $dateColumn = 'C';
        for ($i = 2; $i <= $lastRow; $i++) {
            $dayName = $sheet->getCell($dateColumn . $i)->getValue();
            if (in_array($dayName, ['Sabtu', 'Minggu'])) {
                $sheet->getStyle('B' . $i . ':C' . $i)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3E5F5');
                $sheet->getStyle('B' . $i . ':C' . $i)->getFont()->setBold(true);
            }
        }
        
        // Borders
        $sheet->getStyle('A1:H' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add monthly summary
        $summaryRow = $lastRow + 2;
        $sheet->setCellValue('C' . $summaryRow, 'RATA-RATA HARIAN:');
        $sheet->setCellValue('D' . $summaryRow, $this->dailySummary->count() > 0 ? $this->dailySummary->sum('total_transaksi') / $this->dailySummary->count() : 0);
        $sheet->setCellValue('E' . $summaryRow, $this->dailySummary->sum('total_pendapatan'));
        $sheet->setCellValue('F' . $summaryRow, $this->dailySummary->sum('total_transaksi') > 0 ? $this->dailySummary->sum('total_pendapatan') / $this->dailySummary->sum('total_transaksi') : 0);
        
        // Style summary
        $sheet->getStyle('C' . $summaryRow . ':F' . $summaryRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF607D8B');
        $sheet->getStyle('C' . $summaryRow . ':F' . $summaryRow)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('D' . $summaryRow)->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle('E' . $summaryRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('F' . $summaryRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        
        return [];
    }
}

class MonthlyPaymentSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
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
            '% TRANSAKSI',
            'TOTAL PENDAPATAN',
            '% PENDAPATAN',
            'RATA PER TRANSAKSI',
            'KETERANGAN'
        ];
    }

    public function map($payment): array
    {
        $transactionPercentage = $this->totalTransactions > 0 ? ($payment->total_transaksi / $this->totalTransactions) * 100 : 0;
        $incomePercentage = $this->totalPendapatan > 0 ? ($payment->total_pendapatan / $this->totalPendapatan) * 100 : 0;
        $avgPerTransaction = $payment->total_transaksi > 0 ? $payment->total_pendapatan / $payment->total_transaksi : 0;
        
        // Description based on usage
        $description = '';
        if ($transactionPercentage >= 50) {
            $description = 'METODE UTAMA';
        } elseif ($transactionPercentage >= 20) {
            $description = 'POPULER';
        } elseif ($transactionPercentage >= 5) {
            $description = 'SEDANG';
        } else {
            $description = 'JARANG';
        }
        
        return [
            '', // NO akan diisi di mapping
            strtoupper($payment->metode_pembayaran),
            $payment->total_transaksi,
            $transactionPercentage,
            $payment->total_pendapatan,
            $incomePercentage,
            $avgPerTransaction,
            $description
        ];
    }

    public function title(): string
    {
        return 'PEMBAYARAN';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->paymentSummary->count() + 1;
        
        // Auto size columns
        foreach(range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Header style
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF9C27B0');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Number formatting
        $sheet->getStyle('C2:C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle('E2:E' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        
        // Center align
        $sheet->getStyle('A2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H2:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Right align for percentages and currency
        $sheet->getStyle('D2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Conditional formatting for description
        $descColumn = 'H';
        for ($i = 2; $i <= $lastRow; $i++) {
            $cellValue = $sheet->getCell($descColumn . $i)->getValue();
            $fillColor = '';
            
            switch ($cellValue) {
                case 'METODE UTAMA': $fillColor = 'FF4CAF50'; break;
                case 'POPULER': $fillColor = 'FF8BC34A'; break;
                case 'SEDANG': $fillColor = 'FFFFC107'; break;
                case 'JARANG': $fillColor = 'FFFF9800'; break;
            }
            
            if ($fillColor) {
                $sheet->getStyle($descColumn . $i)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($fillColor);
                $sheet->getStyle($descColumn . $i)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            }
        }
        
        // Borders
        $sheet->getStyle('A1:H' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add row numbers
        for ($i = 0; $i < $this->paymentSummary->count(); $i++) {
            $sheet->setCellValue('A' . ($i + 2), $i + 1);
        }
        
        // Add total row
        $totalRow = $lastRow + 2;
        $sheet->setCellValue('B' . $totalRow, 'TOTAL');
        $sheet->setCellValue('C' . $totalRow, $this->totalTransactions);
        $sheet->setCellValue('D' . $totalRow, 100);
        $sheet->setCellValue('E' . $totalRow, $this->totalPendapatan);
        $sheet->setCellValue('F' . $totalRow, 100);
        $sheet->setCellValue('G' . $totalRow, $this->totalTransactions > 0 ? $this->totalPendapatan / $this->totalTransactions : 0);
        $sheet->setCellValue('H' . $totalRow, 'KESELURUHAN');
        
        // Style total row
        $sheet->getStyle('B' . $totalRow . ':H' . $totalRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFEB3B');
        $sheet->getStyle('B' . $totalRow . ':H' . $totalRow)->getFont()->setBold(true);
        $sheet->getStyle('C' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D' . $totalRow)->getNumberFormat()->setFormatCode('0"%"');
        $sheet->getStyle('E' . $totalRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('F' . $totalRow)->getNumberFormat()->setFormatCode('0"%"');
        $sheet->getStyle('G' . $totalRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        
        return [];
    }
}

class MonthlyKasirSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $kasirPerformance;
    protected $totalPendapatan;
    protected $totalTransactions;

    public function __construct($kasirPerformance, $totalPendapatan, $totalTransactions)
    {
        $this->kasirPerformance = $kasirPerformance;
        $this->totalPendapatan = $totalPendapatan;
        $this->totalTransactions = $totalTransactions;
    }

    public function collection()
    {
        return $this->kasirPerformance;
    }

    public function headings(): array
    {
        return [
            'RANK',
            'NAMA KASIR',
            'ID KASIR',
            'TOTAL TRANSAKSI',
            '% TRANSAKSI',
            'TOTAL PENDAPATAN',
            '% PENDAPATAN',
            'RATA PER TRANSAKSI',
            'TRANSAKSI TERAKHIR',
            'PERFORMANSI'
        ];
    }

    public function map($kasir): array
    {
        $transactionPercentage = $this->totalTransactions > 0 ? ($kasir->total_transaksi / $this->totalTransactions) * 100 : 0;
        $incomePercentage = $this->totalPendapatan > 0 ? ($kasir->total_pendapatan / $this->totalPendapatan) * 100 : 0;
        
        // Determine performance level
        $performance = '';
        $performanceScore = ($transactionPercentage + $incomePercentage) / 2;
        
        if ($performanceScore >= 30) {
            $performance = 'SANGAT BAIK';
        } elseif ($performanceScore >= 20) {
            $performance = 'BAIK';
        } elseif ($performanceScore >= 10) {
            $performance = 'CUKUP';
        } elseif ($performanceScore > 0) {
            $performance = 'PERLU DITINGKATKAN';
        } else {
            $performance = 'TIDAK ADA TRANSAKSI';
        }
        
        return [
            '', // RANK akan diisi di mapping
            $kasir->name,
            'KSR-' . str_pad($kasir->id, 3, '0', STR_PAD_LEFT),
            $kasir->total_transaksi,
            $transactionPercentage,
            $kasir->total_pendapatan,
            $incomePercentage,
            $kasir->rata_transaksi,
            $kasir->transaksi_terakhir ? Carbon::parse($kasir->transaksi_terakhir)->format('d/m/Y H:i') : '-',
            $performance
        ];
    }

    public function title(): string
    {
        return 'KASIR';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->kasirPerformance->count() + 1;
        
        // Auto size columns
        foreach(range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Header style
        $sheet->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF03A9F4');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Number formatting
        $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E2:E' . $lastRow)->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle('H2:H' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
        
        // Center align
        $sheet->getStyle('A2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Right align for percentages and currency
        $sheet->getStyle('E2:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Conditional formatting for performance
        $perfColumn = 'J';
        for ($i = 2; $i <= $lastRow; $i++) {
            $cellValue = $sheet->getCell($perfColumn . $i)->getValue();
            $fillColor = '';
            
            switch ($cellValue) {
                case 'SANGAT BAIK': $fillColor = 'FF4CAF50'; break;
                case 'BAIK': $fillColor = 'FF8BC34A'; break;
                case 'CUKUP': $fillColor = 'FFFFC107'; break;
                case 'PERLU DITINGKATKAN': $fillColor = 'FFFF9800'; break;
                case 'TIDAK ADA TRANSAKSI': $fillColor = 'FFF44336'; break;
            }
            
            if ($fillColor) {
                $sheet->getStyle($perfColumn . $i)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($fillColor);
                $sheet->getStyle($perfColumn . $i)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            }
        }
        
        // Borders
        $sheet->getStyle('A1:J' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add row numbers (ranking) with colors for top 3
        for ($i = 0; $i < $this->kasirPerformance->count(); $i++) {
            $row = $i + 2;
            $sheet->setCellValue('A' . $row, $i + 1);
            
            // Color code top 3 performers
            if ($i < 3) {
                $color = '';
                switch ($i) {
                    case 0: $color = 'FFFFD700'; break; // Gold
                    case 1: $color = 'FFC0C0C0'; break; // Silver
                    case 2: $color = 'FFCD7F32'; break; // Bronze
                }
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                
                // Highlight top performer row
                if ($i == 0) {
                    $sheet->getStyle('B' . $row . ':J' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F8FF');
                    $sheet->getStyle('B' . $row . ':J' . $row)->getFont()->setBold(true);
                }
            }
        }
        
        // Add summary statistics
        $summaryRow = $lastRow + 2;
        $sheet->setCellValue('B' . $summaryRow, 'STATISTIK KESELURUHAN:');
        $sheet->mergeCells('B' . $summaryRow . ':C' . $summaryRow);
        
        $sheet->setCellValue('D' . ($summaryRow + 1), 'Total Kasir:');
        $sheet->setCellValue('E' . ($summaryRow + 1), $this->kasirPerformance->count());
        
        $sheet->setCellValue('D' . ($summaryRow + 2), 'Rata Transaksi/Kasir:');
        $sheet->setCellValue('E' . ($summaryRow + 2), $this->kasirPerformance->count() > 0 ? $this->totalTransactions / $this->kasirPerformance->count() : 0);
        
        $sheet->setCellValue('D' . ($summaryRow + 3), 'Rata Pendapatan/Kasir:');
        $sheet->setCellValue('E' . ($summaryRow + 3), $this->kasirPerformance->count() > 0 ? $this->totalPendapatan / $this->kasirPerformance->count() : 0);
        
        // Style summary
        $summaryRange = 'B' . $summaryRow . ':E' . ($summaryRow + 3);
        $sheet->getStyle($summaryRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');
        $sheet->getStyle($summaryRange)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B' . $summaryRow)->getFont()->setBold(true)->setSize(11);
        
        $sheet->getStyle('E' . ($summaryRow + 1))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . ($summaryRow + 2))->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle('E' . ($summaryRow + 3))->getNumberFormat()->setFormatCode('"Rp"#,##0');
        
        return [];
    }
}