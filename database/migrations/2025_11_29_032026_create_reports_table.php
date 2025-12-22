<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis_laporan', ['mingguan', 'bulanan']);
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->integer('total_transaksi')->default(0);
            $table->integer('total_penjualan')->default(0);
            $table->integer('total_produk_terjual')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};