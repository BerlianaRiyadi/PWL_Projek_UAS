<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Models\Product;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $kasir = User::where('role', 'kasir')->first();
        $products = Product::all();

        // Buat 10 transaksi sample
        for ($i = 0; $i < 10; $i++) {
            $transaction = Transaction::create([
                'user_id' => $kasir->id,
                'total_harga' => 0,
                'metode_pembayaran' => ['cash', 'qris', 'transfer'][rand(0, 2)],
                'status' => 'completed',
                'tanggal_transaksi' => now()->subDays(rand(0, 30))
            ]);

            // Tambah 1-3 produk ke transaksi
            $selectedProducts = $products->random(rand(1, 3));
            $total = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 5);
                $subtotal = $product->harga * $quantity;
                $total += $subtotal;

                TransactionDetail::create([
                    'transaksi_id' => $transaction->id,
                    'produk_id' => $product->id,
                    'kuantitas' => $quantity,
                    'harga_saat_transaksi' => $product->harga,
                    'subtotal' => $subtotal
                ]);
            }

            // Update total transaksi
            $transaction->update(['total_harga' => $total]);
        }
    }
}