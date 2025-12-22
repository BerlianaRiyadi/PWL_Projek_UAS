<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Exports\WeeklyReportExport;
use App\Exports\MonthlyReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class OwnerController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        // Hitung transaksi hari ini
        $transaksiHariIni = Transaction::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->get();

        // Hitung transaksi minggu ini
        $transaksiMingguIni = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->get();

        // Hitung transaksi bulan ini
        $transaksiBulanIni = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->get();

        $data = [
            'total_transaksi_hari_ini' => $transaksiHariIni->count(),
            'total_penjualan_hari_ini' => $transaksiHariIni->sum('total_harga'),
            'total_produk' => Product::count(),
            'total_users' => User::kasir()->count(),
            'transaksi_terbaru' => Transaction::with('user')
                ->where('status', 'completed')
                ->latest()
                ->take(5)
                ->get(),
            'produk_habis' => Product::where('stok', '<=', 5)->get(),
            'total_penjualan_minggu_ini' => $transaksiMingguIni->sum('total_harga'),
            'total_penjualan_bulan_ini' => $transaksiBulanIni->sum('total_harga'),
            'total_kasir_aktif' => User::kasir()->count(),
        ];

        return view('owner.dashboard', compact('data'));
    }

    // ==================== USER MANAGEMENT METHODS ====================

    /**
     * Display a listing of users for owner management
     */
    public function users(Request $request)
    {
        $search = $request->get('search');
        
        $users = User::when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return view('owner.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user
     */
    public function createUser()
    {
        // Role otomatis kasir, tidak perlu pilihan
        return view('owner.users.create');
    }

    /**
     * Store a newly created user
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            // Role otomatis kasir
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'kasir', // SELALU kasir
        ]);

        return redirect()->route('owner.users.index')
            ->with('success', 'Kasir berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified user
     */
    public function editUser(User $user)
    {
        // Hanya kirim data user, role tetap kasir
        return view('owner.users.edit', compact('user'));
    }

    /**
     * Update the specified user
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            // Role tetap kasir, tidak perlu validasi
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => 'kasir', // SELALU kasir
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('owner.users.index')
            ->with('success', 'Kasir berhasil diperbarui.');
    }

    /**
     * Remove the specified user
     */
    public function destroyUser(User $user)
    {
        // Prevent owner from deleting themselves
        if (auth()->id() === $user->id) {
            return redirect()->back()
                ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('owner.users.index')
            ->with('success', 'Kasir berhasil dihapus.');
    }

    /**
     * Activity Log - Display system activities
     * (Disederhanakan tanpa model ActivityLog)
     */
    public function activityLog(Request $request)
    {
        // Menampilkan transaksi terbaru sebagai aktivitas
        $recentTransactions = Transaction::with('user')
            ->latest()
            ->take(20)
            ->get();
            
        // Menampilkan user yang baru ditambahkan
        $recentUsers = User::where('id', '!=', auth()->id())
            ->latest()
            ->take(10)
            ->get();

        return view('owner.activity-log', compact('recentTransactions', 'recentUsers'));
    }

    // ==================== REPORT METHODS ====================

    // Laporan Mingguan Detail
    public function laporanMingguan(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfWeek();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfWeek();

        // Get transactions with details
        $transactions = Transaction::with(['user', 'transactionDetails.product'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        // Get daily summary
        $dailySummary = Transaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_transaksi'),
            DB::raw('SUM(total_harga) as total_pendapatan')
        )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get product sales summary
        $productSales = TransactionDetail::select(
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

        // Get payment method summary
        $paymentSummary = Transaction::select(
            'metode_pembayaran',
            DB::raw('COUNT(*) as total_transaksi'),
            DB::raw('SUM(total_harga) as total_pendapatan')
        )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('metode_pembayaran')
            ->get();

        // Get kasir performance
        $kasirPerformance = Transaction::select(
            'users.name',
            DB::raw('COUNT(transactions.id) as total_transaksi'),
            DB::raw('SUM(transactions.total_harga) as total_pendapatan'),
            DB::raw('AVG(transactions.total_harga) as rata_transaksi')
        )
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_pendapatan')
            ->get();

        $summary = [
            'total_transaksi' => $transactions->count(),
            'total_pendapatan' => $transactions->sum('total_harga'),
            'rata_transaksi' => $transactions->count() > 0 ? $transactions->sum('total_harga') / $transactions->count() : 0,
            'start_date' => $startDate->format('d/m/Y'),
            'end_date' => $endDate->format('d/m/Y'),
            'periode_hari' => $startDate->diffInDays($endDate) + 1,
        ];

        return view('owner.laporan.mingguan', compact(
            'transactions',
            'dailySummary',
            'productSales',
            'paymentSummary',
            'kasirPerformance',
            'summary'
        ));
    }

    // Laporan Bulanan Detail
    public function laporanBulanan(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::parse($request->month . '-01')
            : Carbon::now();

        // Get transactions with details
        $transactions = Transaction::with(['user', 'transactionDetails.product'])
            ->where('status', 'completed')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->latest()
            ->get();

        // Get daily summary
        $dailyData = Transaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_transaksi'),
            DB::raw('SUM(total_harga) as total_pendapatan')
        )
            ->where('status', 'completed')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get product sales summary
        $productSales = TransactionDetail::select(
            'products.nama_produk',
            DB::raw('SUM(transaction_details.kuantitas) as total_terjual'),
            DB::raw('SUM(transaction_details.subtotal) as total_pendapatan')
        )
            ->join('transactions', 'transaction_details.transaksi_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.produk_id', '=', 'products.id')
            ->where('transactions.status', 'completed')
            ->whereYear('transactions.created_at', $month->year)
            ->whereMonth('transactions.created_at', $month->month)
            ->groupBy('products.id', 'products.nama_produk')
            ->orderByDesc('total_terjual')
            ->get();

        // Get payment method summary
        $paymentSummary = Transaction::select(
            'metode_pembayaran',
            DB::raw('COUNT(*) as total_transaksi'),
            DB::raw('SUM(total_harga) as total_pendapatan')
        )
            ->where('status', 'completed')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->groupBy('metode_pembayaran')
            ->get();

        // Get kasir performance
        $kasirPerformance = Transaction::select(
            'users.name',
            DB::raw('COUNT(transactions.id) as total_transaksi'),
            DB::raw('SUM(transactions.total_harga) as total_pendapatan'),
            DB::raw('AVG(transactions.total_harga) as rata_transaksi')
        )
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactions.status', 'completed')
            ->whereYear('transactions.created_at', $month->year)
            ->whereMonth('transactions.created_at', $month->month)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_pendapatan')
            ->get();

        $summary = [
            'total_transaksi' => $transactions->count(),
            'total_pendapatan' => $transactions->sum('total_harga'),
            'rata_harian' => $transactions->count() > 0 ? $transactions->sum('total_harga') / $month->daysInMonth : 0,
            'bulan' => $month->translatedFormat('F Y'),
        ];

        return view('owner.laporan.bulanan', compact(
            'transactions',
            'dailyData',
            'productSales',
            'paymentSummary',
            'kasirPerformance',
            'summary'
        ));
    }

    // Export Laporan Mingguan
    public function exportLaporanMingguan(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfWeek();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfWeek();

        $format = $request->get('format', 'pdf');

        if ($format === 'excel') {
            return Excel::download(
                new WeeklyReportExport($startDate, $endDate),
                'laporan-mingguan-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.xlsx'
            );
        } else {
            return $this->exportLaporanMingguanPDF($startDate, $endDate);
        }
    }

    // Export Laporan Bulanan
    public function exportLaporanBulanan(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::parse($request->month . '-01')
            : Carbon::now();

        $format = $request->get('format', 'pdf');

        if ($format === 'excel') {
            return Excel::download(
                new MonthlyReportExport($month),
                'laporan-bulanan-' . $month->format('Y-m') . '.xlsx'
            );
        } else {
            return $this->exportLaporanBulananPDF($month);
        }
    }

    // Private method untuk PDF export mingguan
    private function exportLaporanMingguanPDF($startDate, $endDate)
    {
        // Get data sama seperti di laporan mingguan
        $transactions = Transaction::with(['user', 'transactionDetails.product'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        $productSales = TransactionDetail::select(
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

        $summary = [
            'total_transaksi' => $transactions->count(),
            'total_pendapatan' => $transactions->sum('total_harga'),
            'start_date' => $startDate->format('d/m/Y'),
            'end_date' => $endDate->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('owner.laporan.laporan_mingguan_pdf', [
            'transactions' => $transactions,
            'productSales' => $productSales,
            'summary' => $summary,
            'title' => 'Laporan Mingguan ' . $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y')
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-mingguan-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf');
    }

    // Private method untuk PDF export bulanan
    private function exportLaporanBulananPDF($month)
    {
        $transactions = Transaction::with(['user', 'transactionDetails.product'])
            ->where('status', 'completed')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->latest()
            ->get();

        $productSales = TransactionDetail::select(
            'products.nama_produk',
            DB::raw('SUM(transaction_details.kuantitas) as total_terjual'),
            DB::raw('SUM(transaction_details.subtotal) as total_pendapatan')
        )
            ->join('transactions', 'transaction_details.transaksi_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.produk_id', '=', 'products.id')
            ->where('transactions.status', 'completed')
            ->whereYear('transactions.created_at', $month->year)
            ->whereMonth('transactions.created_at', $month->month)
            ->groupBy('products.id', 'products.nama_produk')
            ->orderByDesc('total_terjual')
            ->get();

        $summary = [
            'total_transaksi' => $transactions->count(),
            'total_pendapatan' => $transactions->sum('total_harga'),
            'bulan' => $month->translatedFormat('F Y'),
        ];

        $pdf = Pdf::loadView('owner.exports.laporan_bulanan_pdf', [
            'transactions' => $transactions,
            'productSales' => $productSales,
            'summary' => $summary,
            'title' => 'Laporan Bulanan ' . $month->translatedFormat('F Y')
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-bulanan-' . $month->format('Y-m') . '.pdf');
    }
}