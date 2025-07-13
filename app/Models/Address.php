<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    // Relasi dengan Customer (Pelanggan)
    public function user()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Relasi dengan Pemesanan
    public function pemesanans()
    {
        return $this->hasMany(Pemesanan::class, 'address_id');
    }
}
