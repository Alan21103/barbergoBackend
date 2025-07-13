<?php
// app/Models/Jadwal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;

    // Pastikan nama tabel sesuai dengan yang ada di database
    protected $table = 'jadwal';  // Nama tabel di database adalah 'jadwal'

    protected $fillable = ['barber_id', 'hari', 'tersedia_dari', 'tersedia_hingga'];
    // Relasi dengan Barber
    public function barber()
    {
        return $this->belongsTo(User::class, 'barber_id');
    }
}
