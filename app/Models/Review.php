<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';

    // Menambahkan kolom yang bisa diisi
    protected $fillable = [
        'pemesanan_id',  // Mengganti order_id dengan pemesanan_id
        'rating',
        'review',
        'deskripsi',     // Menambahkan deskripsi
    ];
    // Relasi dengan Pemesanan
    public function pemesanan() // Nama relasi sekarang 'pemesanan'
    {
        return $this->belongsTo(Pemesanan::class, 'pemesanan_id');
    }
}
