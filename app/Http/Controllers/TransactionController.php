<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Transaction::with('user')->latest();

        // Filter tanggal
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', today()->subDay());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date')) {
                        $query->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $query->whereDate('created_at', '<=', $request->end_date);
                    }
                    break;
            }
        }

        // Filter bulan
        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $query->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year);
        }

        // Filter tahun
        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        // Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Statistik
        $totalTransactions = Transaction::count();
        $totalRevenue = Transaction::where('status', 'completed')->sum('total_harga');
        $averageTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;
        $todayTransactions = Transaction::whereDate('created_at', today())->count();

        $transactions = $query->paginate(10)->withQueryString();

        return view('transactions.index', compact(
            'transactions',
            'totalTransactions',
            'totalRevenue',
            'averageTransaction',
            'todayTransactions'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::where('stok', '>', 0)->get();

        // Calculate kembalian if form was submitted
        $total = array_sum(array_map(function ($item) {
            return $item['harga'] * $item['quantity'];
        }, session('cart', [])));

        $jumlah_bayar = old('jumlah_bayar', 0);

        return view('transactions.create', compact('products', 'total', 'jumlah_bayar'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $action = $request->input('action');

        switch ($action) {
            case 'add_to_cart':
                return $this->addToCart($request);

            case 'process_transaction':
                return $this->processTransaction($request);

            case 'reset_cart':
                return $this->resetCart();

            default:
                if ($request->has('remove_item')) {
                    return $this->removeFromCart($request);
                }
                return redirect()->route('transaksi.create')->with('error', 'Aksi tidak valid.');
        }
    }

    /**
     * Add product to cart
     */
    private function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity;

        // Check stock
        if ($product->stok < $quantity) {
            return redirect()->route('transaksi.create')
                ->with('error', "Stok {$product->nama_produk} tidak mencukupi. Stok tersedia: {$product->stok}");
        }

        $cart = session()->get('cart', []);

        // Check if product already in cart
        if (isset($cart[$product->id])) {
            $newQuantity = $cart[$product->id]['quantity'] + $quantity;

            if ($newQuantity > $product->stok) {
                return redirect()->route('transaksi.create')
                    ->with('error', "Stok {$product->nama_produk} tidak mencukupi. Stok tersedia: {$product->stok}");
            }

            $cart[$product->id]['quantity'] = $newQuantity;
        } else {
            $cart[$product->id] = [
                'id' => $product->id,
                'nama' => $product->nama_produk,
                'harga' => $product->harga,
                'quantity' => $quantity
            ];
        }

        session()->put('cart', $cart);

        return redirect()->route('transaksi.create')
            ->with('success', "{$product->nama_produk} berhasil ditambahkan ke keranjang");
    }

    /**
     * Remove product from cart
     */
    private function removeFromCart(Request $request)
    {
        $productId = $request->input('remove_item');
        $cart = session()->get('cart', []);

        if (isset($cart[$productId])) {
            $productName = $cart[$productId]['nama'];
            unset($cart[$productId]);
            session()->put('cart', $cart);

            return redirect()->route('transaksi.create')
                ->with('success', "{$productName} dihapus dari keranjang");
        }

        return redirect()->route('transaksi.create')
            ->with('error', 'Produk tidak ditemukan di keranjang');
    }

    /**
     * Reset cart
     */
    private function resetCart()
    {
        session()->forget('cart');

        return redirect()->route('transaksi.create')
            ->with('success', 'Keranjang berhasil direset');
    }

    /**
     * Process transaction
     */
    /**
     * Process transaction
     */
    private function processTransaction(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'jumlah_bayar' => 'required|integer|min:1'
            ]);

            $cart = session()->get('cart', []);

            if (empty($cart)) {
                return redirect()->route('transaksi.create')
                    ->with('error', 'Keranjang belanja kosong.');
            }

            // Calculate total
            $total = 0;
            foreach ($cart as $item) {
                $product = Product::find($item['id']);
                if (!$product) {
                    throw new \Exception("Produk tidak ditemukan.");
                }
                $subtotal = $product->harga * $item['quantity'];
                $total += $subtotal;
            }

            // Validate payment
            $jumlah_bayar = $request->jumlah_bayar;
            if ($jumlah_bayar < $total) {
                return redirect()->route('transaksi.create')
                    ->with('error', "Jumlah bayar kurang dari total harga. Total: Rp " . number_format($total, 0, ',', '.'));
            }

            $kembalian = $jumlah_bayar - $total;

            // Create transaction
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'total_harga' => $total,
                'jumlah_bayar' => $jumlah_bayar,
                'kembalian' => $kembalian,
                'metode_pembayaran' => 'cash',
                'status' => 'completed',
                'tanggal_transaksi' => now()
            ]);

            // Process each item
            foreach ($cart as $item) {
                $product = Product::find($item['id']);

                if (!$product) {
                    throw new \Exception("Produk tidak ditemukan.");
                }

                if ($product->stok < $item['quantity']) {
                    throw new \Exception("Stok {$product->nama_produk} tidak mencukupi. Stok tersedia: {$product->stok}");
                }

                $subtotal = $product->harga * $item['quantity'];

                // Create transaction detail
                TransactionDetail::create([
                    'transaksi_id' => $transaction->id,
                    'produk_id' => $product->id,
                    'kuantitas' => $item['quantity'],
                    'harga_saat_transaksi' => $product->harga,
                    'subtotal' => $subtotal
                ]);

                // Update product stock
                $product->decrement('stok', $item['quantity']);
            }

            // Log activity
            ActivityLog::logActivity(
                Auth::id(),
                'Buat Transaksi',
                "Membuat transaksi #{$transaction->id} - Total: Rp " . number_format($total, 0, ',', '.') . " - Bayar: Rp " . number_format($jumlah_bayar, 0, ',', '.') . " - Kembali: Rp " . number_format($kembalian, 0, ',', '.')
            );

            DB::commit();

            // Store transaction data in session for success modal
            $successData = [
                'success' => 'Transaksi berhasil dibuat!',
                'transaction_id' => $transaction->id,
                'total' => $total,
                'jumlah_bayar' => $jumlah_bayar,
                'kembalian' => $kembalian
            ];

            // Clear cart
            session()->forget('cart');

            return redirect()->route('transaksi.create')
                ->with($successData);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('transaksi.create')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $transaksi = Transaction::with([
            'user',
            'transactionDetails.product'
        ])->findOrFail($id);

        return view('transactions.show', compact('transaksi'));
    }


    /**
     * Cancel transaction
     */
    public function cancel(Transaction $transaksi)
    {
        DB::beginTransaction();

        try {
            if ($transaksi->status === 'canceled') {
                return redirect()->back()->with('error', 'Transaksi sudah dibatalkan.');
            }

            // Restore product stocks
            foreach ($transaksi->transactionDetails as $detail) {
                $product = $detail->product;
                $product->increment('stok', $detail->kuantitas);
            }

            // Update transaction status
            $transaksi->update(['status' => 'canceled']);

            // Log activity
            ActivityLog::logActivity(
                Auth::id(),
                'Batalkan Transaksi',
                "Membatalkan transaksi #{$transaksi->id}"
            );

            DB::commit();

            return redirect()->route('transaksi.index')
                ->with('success', 'Transaksi berhasil dibatalkan!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Print receipt
     */
    public function printReceipt(Transaction $transaksi)
    {
        $transaksi->load(['user', 'transactionDetails.product']);

        $filename = 'Struk_' . $transaksi->id . '_' . $transaksi->created_at->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('transactions.receipt', compact('transaksi'));
        return $pdf->download($filename);
    }



    /**
     * Search products for AJAX
     */
    public function searchProducts(Request $request)
    {
        $search = $request->get('search');

        $products = Product::where('nama_produk', 'like', "%{$search}%")
            ->where('stok', '>', 0)
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function export(Request $request)
    {
        $query = Transaction::with('user')->latest();

        // Terapkan filter yang sama seperti di index
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', Carbon::yesterday());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                        ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date')) {
                        $query->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $query->whereDate('created_at', '<=', $request->end_date);
                    }
                    break;
            }
        }

        if ($request->filled('month')) {
            $month = Carbon::createFromFormat('Y-m', $request->month);
            $query->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year);
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->get();

        // Export berdasarkan format
        $format = $request->get('format', 'excel');

        if ($format === 'pdf') {
            return $this->exportPdf($transactions);
        } else {
            return $this->exportExcel($transactions);
        }
    }

    private function exportPdf($transactions)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('transactions.export_pdf', [
            'transactions' => $transactions,
            'title' => 'Laporan Transaksi'
        ]);

        return $pdf->download('laporan-transaksi-' . date('Y-m-d') . '.pdf');
    }
}
