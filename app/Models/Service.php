<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'description',
        'barber_id', // Tambahkan ini
    ];

    // Relasi dengan Pemesanan
    public function pemesanans()
    {
        return $this->hasMany(Pemesanan::class, 'service_id');
    }

    public function barber()
    {
        return $this->belongsTo(User::class, 'barber_id'); // Asumsi barber adalah User
    }
}
