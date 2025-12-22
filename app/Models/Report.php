<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis_laporan',
        'periode_mulai',
        'periode_selesai',
        'total_transaksi',
        'total_penjualan',
        'total_produk_terjual'
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
        'total_transaksi' => 'integer',
        'total_penjualan' => 'integer',
        'total_produk_terjual' => 'integer'
    ];

    // SCOPES
    public function scopeMingguan($query)
    {
        return $query->where('jenis_laporan', 'mingguan');
    }

    public function scopeBulanan($query)
    {
        return $query->where('jenis_laporan', 'bulanan');
    }

    // METHODS
    public function getFormattedTotalPenjualanAttribute()
    {
        return 'Rp ' . number_format($this->total_penjualan, 0, ',', '.');
    }

    public function getPeriodeAttribute()
    {
        return $this->periode_mulai->format('d/m/Y') . ' - ' . $this->periode_selesai->format('d/m/Y');
    }
}