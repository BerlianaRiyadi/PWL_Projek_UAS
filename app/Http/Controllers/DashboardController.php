<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->role === 'owner') {
            return redirect()->route('owner.dashboard');
        } elseif (Auth::user()->role === 'kasir') {
            return redirect()->route('kasir.dashboard');
        }
        
        return view('dashboard');
    }

    public function ownerDashboard()
    {
        $today = Carbon::today();
        
        $data = [
            'total_transaksi_hari_ini' => Transaction::whereDate('created_at', $today)->count(),
            'total_penjualan_hari_ini' => Transaction::whereDate('created_at', $today)->sum('total_harga'),
            'total_produk' => Product::count(),
            'total_users' => User::where('role', 'kasir')->count(),
            'transaksi_terbaru' => Transaction::with('user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(),
            'produk_habis' => Product::where('stok', '<=', 5)->get()
        ];

        return view('owner.dashboard', compact('data'));
    }

    public function kasirDashboard()
    {
        $today = Carbon::today();
        $user = Auth::user();
        
        $data = [
            'transaksi_hari_ini' => Transaction::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->count(),
            'total_penjualan_hari_ini' => Transaction::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->sum('total_harga'),
            'produk_habis' => Product::where('stok', 0)->count(),
            'produk_menipis' => Product::where('stok', '<=', 5)->where('stok', '>', 0)->count(),
            'transaksi_terbaru' => Transaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
        ];

        return view('kasir.dashboard', compact('data'));
    }
}