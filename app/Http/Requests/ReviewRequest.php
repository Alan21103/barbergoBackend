<?php

// app/Http/Requests/ReviewRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize()
    {
        return true;  // Mengizinkan akses ke request
    }

    public function rules()
    {
        return [
            'pemesanan_id' => 'required|exists:pemesanans,id', // Pastikan pemesanan_id valid
            'rating' => 'required|integer|min:1|max:5',  // Rating antara 1 sampai 5
            'review' => 'required|string',  // Review harus berupa string
            'deskripsi' => 'nullable|string', // Deskripsi bersifat opsional
        ];
    }

    public function messages()
    {
        return [
            'pemesanan_id.required' => 'Pemesanan ID wajib diisi.',
            'pemesanan_id.exists' => 'Pemesanan ID tidak ditemukan.',
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating harus berupa angka.',
            'review.required' => 'Review wajib diisi.',
            'deskripsi.string' => 'Deskripsi harus berupa string.',
        ];
    }
}
