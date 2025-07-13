<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'alamat',
        'latitude',
        'longitude',
    ];
    protected $table = 'admin';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
