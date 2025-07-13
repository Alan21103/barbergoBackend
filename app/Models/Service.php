<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'description'];

    // Relasi dengan Pemesanan
    public function pemesanans()
    {
        return $this->hasMany(Pemesanan::class, 'service_id');
    }
}
