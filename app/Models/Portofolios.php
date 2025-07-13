<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL; // <<< Tambahkan ini

class Portofolios extends Model
{
    use HasFactory;

    protected $fillable = ['barber_id', 'image', 'description'];
    protected $table = 'portfolios';

    public function getImageAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $cleanedPath = str_replace('public/', '', $value);
        $url = Storage::url($cleanedPath); // Ini yang seharusnya menggunakan APP_URL

        // --- Perubahan Kritis Tambahan untuk memaksa 10.0.2.2 ---
        // Jika URL yang dihasilkan masih 127.0.0.1 atau localhost, ganti
        if (str_contains($url, '127.0.0.1') || str_contains($url, 'localhost')) {
            $url = str_replace(['http://127.0.0.1:8000', 'http://localhost:8000'], 'http://10.0.2.2:8000', $url);
        }

        // Jika masih relatif (misalnya '/storage/...'), tambahkan base URL secara eksplisit
        if (!preg_match('/^https?:\/\//i', $url)) {
            $baseUrl = config('app.url');
            if (empty($baseUrl) || !preg_match('/^https?:\/\//i', $baseUrl) || $baseUrl === 'http://localhost') {
                $baseUrl = 'http://10.0.2.2:8000'; // Selalu gunakan 10.0.2.2 sebagai fallback utama
            }
            $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/'); // Pastikan slash tunggal
        }
        // --- Akhir Perubahan Kritis Tambahan ---

        Log::info("DEBUG - Final image URL generated for path '" . $value . "': " . $url);
        return $url;
    }

    /**
     * Definisi relasi "barber" (pemilik portofolio).
     * Portofolio ini milik satu User (barber).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function barber()
    {
        return $this->belongsTo(User::class, 'barber_id');
    }
}