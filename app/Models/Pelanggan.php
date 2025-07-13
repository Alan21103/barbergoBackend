<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pelanggan extends Model
{
    protected $fillable = ['user_id', 'name', 'address', 'phone', 'photo'];
    protected $table = 'pelanggan';
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pemesanan()
{
    return $this->belongsTo(Pemesanan::class, 'pemesanan_id');
}
}
