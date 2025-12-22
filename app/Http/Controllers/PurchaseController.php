<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchases = Purchase::with(['user', 'purchaseDetails.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('purchases.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::all();
        return view('purchases.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.buy_price' => 'required|integer|min:1'
            ]);

            // Create purchase
            $purchase = Purchase::create([
                'user_id' => Auth::id(),
                'total' => 0,
                'status' => 'normal',
                'tanggal_pembelian' => now()
            ]);

            $total = 0;

            // Process each item
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $subtotal = $item['buy_price'] * $item['quantity'];
                $total += $subtotal;

                // Create purchase detail
                PurchaseDetail::create([
                    'pembelian_id' => $purchase->id,
                    'produk_id' => $product->id,
                    'kuantitas' => $item['quantity'],
                    'harga_beli' => $item['buy_price'],
                    'subtotal' => $subtotal
                ]);

                // Update product stock
                $product->increaseStock($item['quantity']);
            }

            // Update purchase total
            $purchase->update(['total' => $total]);

            // Log activity
            ActivityLog::logActivity(
                Auth::id(),
                'Input Pembelian',
                "Input pembelian #{$purchase->id} dengan total Rp " . number_format($total, 0, ',', '.')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'purchase_id' => $purchase->id,
                'message' => 'Pembelian berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $pembelian)
    {
        $pembelian->load(['user', 'purchaseDetails.product']);
        return view('purchases.show', compact('pembelian'));
    }

    /**
     * Cancel purchase
     */
    public function cancel(Purchase $pembelian)
    {
        DB::beginTransaction();

        try {
            if ($pembelian->status === 'canceled') {
                return redirect()->back()->with('error', 'Pembelian sudah dibatalkan.');
            }

            // Restore product stocks
            foreach ($pembelian->purchaseDetails as $detail) {
                $product = $detail->product;
                $product->decreaseStock($detail->kuantitas);
            }

            // Update purchase status
            $pembelian->update(['status' => 'canceled']);

            // Log activity
            ActivityLog::logActivity(
                Auth::id(),
                'Batalkan Pembelian',
                "Membatalkan pembelian #{$pembelian->id}"
            );

            DB::commit();

            return redirect()->route('pembelian.index')
                ->with('success', 'Pembelian berhasil dibatalkan!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with('error', 'Gagal membatalkan pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Get product details for AJAX
     */
    public function getProduct($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json(['error' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'nama_produk' => $product->nama_produk,
            'harga_jual' => $product->harga,
            'stok' => $product->stok,
            'formatted_harga' => 'Rp ' . number_format($product->harga, 0, ',', '.')
        ]);
    }
}