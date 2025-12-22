<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_produk',
        'harga',
        'stok',
        'kategori_id'
    ];

    protected $casts = [
        'harga' => 'integer',
        'stok' => 'integer'
    ];

    // RELATIONSHIPS
    public function category()
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class, 'produk_id');
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class, 'produk_id');
    }

    // SCOPES
    public function scopeStockLow($query)
    {
        return $query->where('stok', '<=', 5);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stok', 0);
    }

    public function scopeInStock($query)
    {
        return $query->where('stok', '>', 0);
    }

    // METHODS
    public function decreaseStock($quantity)
    {
        $this->stok -= $quantity;
        $this->save();
    }

    public function increaseStock($quantity)
    {
        $this->stok += $quantity;
        $this->save();
    }

    public function getStockStatusAttribute()
    {
        if ($this->stok == 0) {
            return 'out_of_stock';
        } elseif ($this->stok <= 5) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    public function getStockStatusColorAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'danger',
            'low_stock' => 'warning',
            'in_stock' => 'success',
        };
    }

    public function getFormattedHargaAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }
}