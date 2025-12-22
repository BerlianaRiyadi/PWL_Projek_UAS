<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('total_harga')->default(0);
            $table->integer('jumlah_bayar')->default(0);
            $table->integer('kembalian')->default(0);
            $table->enum('metode_pembayaran', ['cash'])->default('cash'); // HANYA CASH
            $table->enum('status', ['completed', 'canceled'])->default('completed');
            $table->dateTime('tanggal_transaksi')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};