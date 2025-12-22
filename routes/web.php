<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\OwnerController;

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes();

// DASHBOARD ROUTES
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Route khusus Owner - GUNAKAN MIDDLEWARE 'owner'
Route::middleware(['auth', 'owner'])->prefix('owner')->name('owner.')->group(function () {

    // Dashboard Owner
    Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('dashboard');

    // Kelola User
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [OwnerController::class, 'users'])->name('index');
        Route::get('/create', [OwnerController::class, 'createUser'])->name('create');
        Route::post('/', [OwnerController::class, 'storeUser'])->name('store');
        Route::get('/{user}/edit', [OwnerController::class, 'editUser'])->name('edit');
        Route::put('/{user}', [OwnerController::class, 'updateUser'])->name('update');
        Route::delete('/{user}', [OwnerController::class, 'destroyUser'])->name('destroy');
    });

    // Laporan Mingguan
    Route::get('/laporan/mingguan', [OwnerController::class, 'laporanMingguan'])->name('laporan.mingguan');

    // Laporan Bulanan
    Route::get('/laporan/bulanan', [OwnerController::class, 'laporanBulanan'])->name('laporan.bulanan');

    // Activity Log
    Route::get('/activity-log', [OwnerController::class, 'activityLog'])->name('activity.log');

    // Export Laporan
    Route::get('/export/laporan-mingguan', [OwnerController::class, 'exportLaporanMingguan'])
        ->name('export.laporan.mingguan');

    Route::get('/export/laporan-bulanan', [OwnerController::class, 'exportLaporanBulanan'])
        ->name('export.laporan.bulanan');

    // TRANSAKSI ROUTES
    Route::prefix('transaksi')->name('transaksi.')->group(function () {
        Route::get('/{transaksi}', [TransactionController::class, 'show'])->name('show');
        Route::get('/{transaksi}/receipt', [TransactionController::class, 'printReceipt'])->name('receipt');
    });
});

// KASIR ONLY ROUTES - PRODUK MANAGEMENT
Route::middleware(['auth', 'kasir'])->group(function () {
    Route::get('/kasir/dashboard', [DashboardController::class, 'kasirDashboard'])->name('kasir.dashboard');

    // PRODUK ROUTES
    Route::prefix('produk')->name('produk.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{produk}', [ProductController::class, 'show'])->name('show');
        Route::get('/{produk}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{produk}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{produk}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/{produk}/stock', [ProductController::class, 'updateStock'])->name('update-stock');
        Route::get('/search/products', [ProductController::class, 'search'])->name('search');
    });

    // TRANSAKSI ROUTES
    Route::prefix('transaksi')->name('transaksi.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/create', [TransactionController::class, 'create'])->name('create');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::get('/{transaksi}', [TransactionController::class, 'show'])->name('show');
        Route::post('/{transaksi}/cancel', [TransactionController::class, 'cancel'])->name('cancel');
        Route::get('/{transaksi}/receipt', [TransactionController::class, 'printReceipt'])->name('receipt');
        // PERBAIKI ROUTE INI - HAPUS DUPLIKAT 'transaksi/'
        Route::get('/search/products', [TransactionController::class, 'searchProducts'])->name('search-products');
        // Route untuk export transaksi
        Route::get('/export', [TransactionController::class, 'export'])->name('export');
    });

    // PEMBELIAN ROUTES
    Route::prefix('pembelian')->name('pembelian.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseController::class, 'create'])->name('create');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{pembelian}', [PurchaseController::class, 'show'])->name('show');
        Route::post('/{pembelian}/cancel', [PurchaseController::class, 'cancel'])->name('cancel');
        Route::get('/product/{id}', [PurchaseController::class, 'getProduct'])->name('get-product');
    });
});

// Home route
Route::get('/home', [HomeController::class, 'index'])->name('home');
