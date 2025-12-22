<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Pastikan bagian ini ada untuk memproses input pencarian
        if ($request->has('search')) {
            $query->where('nama_produk', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(10);
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_produk' => 'required|string|max:255',
            'harga' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'kategori_id' => 'nullable|exists:categories,id'
        ]);

        $product = Product::create($request->all());

        // Log activity
        ActivityLog::logActivity(
            Auth::id(),
            'Tambah Produk',
            "Menambah produk {$product->nama_produk} dengan harga Rp " . number_format($product->harga, 0, ',', '.')
        );

        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $produk)
    {
        return view('products.show', compact('produk'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $produk)
    {
        $categories = Category::all();
        return view('products.edit', compact('produk', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $produk)
    {
        $request->validate([
            'nama_produk' => 'required|string|max:255',
            'harga' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'kategori_id' => 'nullable|exists:categories,id'
        ]);

        $produk->update($request->all());

        // Log activity
        ActivityLog::logActivity(
            Auth::id(),
            'Edit Produk',
            "Mengedit produk {$produk->nama_produk} - Harga: Rp " . number_format($produk->harga, 0, ',', '.') . " - Stok: {$produk->stok}"
        );

        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $produk)
    {
        $productName = $produk->nama_produk;
        $produk->delete();

        // Log activity
        ActivityLog::logActivity(
            Auth::id(),
            'Hapus Produk',
            "Menghapus produk {$productName}"
        );

        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil dihapus!');
    }

    /**
     * API untuk mencari produk (digunakan di transaksi)
     */
    public function search(Request $request)
    {
        $search = $request->get('search');
        
        $products = Product::where('nama_produk', 'like', "%{$search}%")
            ->where('stok', '>', 0)
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    /**
     * Update stok produk
     */
    public function updateStock(Request $request, Product $produk)
    {
        $request->validate([
            'stok' => 'required|integer|min:0'
        ]);

        $oldStock = $produk->stok;
        $produk->update(['stok' => $request->stok]);

        // Log activity
        ActivityLog::logActivity(
            Auth::id(),
            'Update Stok',
            "Mengupdate stok {$produk->nama_produk} dari {$oldStock} menjadi {$request->stok}"
        );

        return redirect()->back()
            ->with('success', 'Stok produk berhasil diupdate!');
    }
}