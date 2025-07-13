<?php
// app/Http/Requests/JadwalRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JadwalRequest extends FormRequest
{
    public function authorize()
    {
        // Mengizinkan akses ke request
        return true;
    }

    public function rules()
    {
        $rules = [
            'hari' => 'required|string',  // Validasi hari bebas, string yang diisi user
            'tersedia_dari' => 'required|date_format:H:i', // Format waktu mulai
            'tersedia_hingga' => 'required|date_format:H:i|after:tersedia_dari', // Waktu selesai harus setelah waktu mulai
        ];

        // Aturan 'barber_id' hanya akan diterapkan jika rute yang diakses adalah rute admin
        // yang membutuhkan barber_id secara eksplisit dari request.
        // Kita berasumsi rute admin untuk 'store' diberi nama 'admin.jadwal.store'.
        if ($this->route()->getName() == 'admin.jadwal.store') {
            $rules['barber_id'] = 'required|exists:users,id'; // Memastikan barber_id ada di tabel users
        }
        // Untuk rute 'my-jadwal' (storeMyJadwal), barber_id akan diambil dari Auth::id() di controller,
        // sehingga tidak perlu divalidasi dari request.

        return $rules;
    }

    public function messages()
    {
        return [
            'barber_id.required' => 'Barber ID wajib diisi.',
            'barber_id.exists' => 'Barber tidak ditemukan.',
            'hari.required' => 'Hari wajib diisi.',
            'tersedia_dari.required' => 'Waktu mulai wajib diisi.',
            'tersedia_dari.date_format' => 'Waktu mulai harus menggunakan format HH:mm.',
            'tersedia_hingga.required' => 'Waktu selesai wajib diisi.',
            'tersedia_hingga.date_format' => 'Waktu selesai harus menggunakan format HH:mm.',
            'tersedia_hingga.after' => 'Waktu selesai harus setelah waktu mulai.',
        ];
    }
}
