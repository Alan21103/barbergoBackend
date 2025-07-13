<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemesanan extends Model
{
    use HasFactory;

    protected $table = 'pemesanan';

    // Menambahkan kolom yang boleh diisi melalui mass assignment
    protected $fillable = [
        'pelanggan_id',
        'barber_id',
        'service_id',
        'scheduled_time',
        'status',
        'alamat',
        'latitude',
        'longitude',
        'ongkir',
        'total_price',
        'admin_id',  // Menyimpan admin_id
    ];

    // Relasi ke admin (barber)
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
    // Relasi dengan Service (tabel services)
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    // Relasi dengan Pembayaran
    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class);
    }

    // Relasi dengan Barber (Barber/Admin)
    public function barber()
    {
        return $this->belongsTo(User::class, 'barber_id');
    }

    // Relasi dengan Alamat
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
        // 'pelanggan_id' adalah foreign key di tabel 'pemesanans' yang merujuk ke 'id' di tabel 'pelanggans'
    }

}
