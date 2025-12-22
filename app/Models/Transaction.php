<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_harga',
        'jumlah_bayar',
        'kembalian',
        'metode_pembayaran',
        'status',
        'tanggal_transaksi'
    ];

    protected $casts = [
        'total_harga' => 'integer',
        'jumlah_bayar' => 'integer',
        'kembalian' => 'integer',
        'tanggal_transaksi' => 'datetime'
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class, 'transaksi_id');
    }

    // SCOPES
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // METHODS
    public function getFormattedTotalHargaAttribute()
    {
        return 'Rp ' . number_format($this->total_harga, 0, ',', '.');
    }

    public function getFormattedJumlahBayarAttribute()
    {
        return 'Rp ' . number_format($this->jumlah_bayar, 0, ',', '.');
    }

    public function getFormattedKembalianAttribute()
    {
        return 'Rp ' . number_format($this->kembalian, 0, ',', '.');
    }

    public function getFormattedTanggalAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }
}