<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePemesananRequest extends FormRequest
{
    public function authorize()
    {
        // Pastikan hanya pelanggan yang bisa membuat pemesanan
        return auth()->user() && auth()->user()->role->name === 'pelanggan';
    }

    public function rules()
    {
        return [
            'service_id' => 'required|exists:services,id', // Service yang valid
            'admin_id' => 'required|exists:admin,id', // Admin yang valid
            // 'barber_id' => 'required|exists:barber,id', // Barber yang valid
            'scheduled_time' => 'required|date|after:now', // Waktu yang dijadwalkan harus setelah waktu saat ini
            'alamat' => 'required|string|max:255', // Validasi alamat
            'latitude' => 'required|numeric', // Validasi latitude
            'longitude' => 'required|numeric', // Validasi longitude
        ];
    }

    public function messages()
    {
        return [
            'service_id.required' => 'Layanan harus dipilih.',
            'service_id.exists' => 'Layanan yang dipilih tidak valid.',
            'barber_id.required' => 'Barber harus dipilih.',
            'barber_id.exists' => 'Barber yang dipilih tidak valid.',
            'alamat.required' => 'Alamat harus dipilih.',
            'alamat.string' => 'Alamat harus berupa teks.',
            'latitude.required' => 'Latitude harus diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'longitude.required' => 'Longitude harus diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'scheduled_time.required' => 'Waktu pemesanan harus diisi.',
            'scheduled_time.date' => 'Waktu pemesanan harus dalam format yang valid.',
            'scheduled_time.after' => 'Waktu pemesanan harus setelah waktu sekarang.',
        ];
    }
}
