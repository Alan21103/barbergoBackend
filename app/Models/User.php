<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role->name
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

     // Relasi dengan Pemesanan (Customer)
    public function pemesanans()
    {
        return $this->hasMany(Pemesanan::class, 'pelanggan_id');
    }

    // Relasi dengan Jadwal (Barber)
    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'barber_id');
    }

    // Relasi dengan Alamat
    public function addresses()
    {
        return $this->hasMany(Address::class, 'pelanggan_id');
    }
    
     public function pelanggan()
    {
        // Asumsi: tabel 'pelanggan' memiliki foreign key 'user_id' yang merujuk ke 'id' di tabel 'users'
        return $this->hasOne(Pelanggan::class, 'user_id');
    }
}
