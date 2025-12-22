<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'status',
        'tanggal_pembelian'
    ];

    protected $casts = [
        'total' => 'integer',
        'tanggal_pembelian' => 'datetime'
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class, 'pembelian_id');
    }

    // SCOPES
    public function scopeNormal($query)
    {
        return $query->where('status', 'normal');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    // METHODS
    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    public function calculateTotal()
    {
        return $this->purchaseDetails->sum('subtotal');
    }

    public function addProduct($product, $quantity, $buyPrice)
    {
        $subtotal = $buyPrice * $quantity;

        $purchaseDetail = $this->purchaseDetails()->updateOrCreate(
            ['produk_id' => $product->id],
            [
                'kuantitas' => $quantity,
                'harga_beli' => $buyPrice,
                'subtotal' => $subtotal
            ]
        );

        // Update total purchase
        $this->update(['total' => $this->calculateTotal()]);

        return $purchaseDetail;
    }
}