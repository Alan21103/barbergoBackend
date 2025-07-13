<?php

// app/Models/Pembayaran.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;
    protected $table = 'pembayaran';
    protected $fillable = ['pemesanan_id', 'amount', 'status', 'paid_at', 'via',];

    // Relasi dengan Pemesanan
    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class);
    }
}
