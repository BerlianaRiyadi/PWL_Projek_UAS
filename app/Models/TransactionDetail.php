<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaksi_id',
        'produk_id',
        'kuantitas',
        'harga_saat_transaksi',
        'subtotal'
    ];

    protected $casts = [
        'kuantitas' => 'integer',
        'harga_saat_transaksi' => 'integer',
        'subtotal' => 'integer'
    ];

    // RELATIONSHIPS
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaksi_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'produk_id');
    }

    // METHODS
    public function getFormattedHargaAttribute()
    {
        return 'Rp ' . number_format($this->harga_saat_transaksi, 0, ',', '.');
    }

    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }
}