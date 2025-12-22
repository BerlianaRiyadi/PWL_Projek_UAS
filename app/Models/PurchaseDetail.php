<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'pembelian_id',
        'produk_id',
        'kuantitas',
        'harga_beli',
        'subtotal'
    ];

    protected $casts = [
        'kuantitas' => 'integer',
        'harga_beli' => 'integer',
        'subtotal' => 'integer'
    ];

    // RELATIONSHIPS
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'pembelian_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'produk_id');
    }

    // METHODS
    public function getFormattedHargaBeliAttribute()
    {
        return 'Rp ' . number_format($this->harga_beli, 0, ',', '.');
    }

    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }
}