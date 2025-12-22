<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('products')->cascadeOnDelete();
            $table->integer('kuantitas');
            $table->integer('harga_beli');
            $table->integer('subtotal');
            $table->timestamps();
        });
    }
};
