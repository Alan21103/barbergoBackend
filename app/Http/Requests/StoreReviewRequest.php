<?php

// app/Http/Requests/StoreReviewRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize()
    {
        // Pastikan hanya pelanggan yang dapat memberikan ulasan
        return auth()->user() && auth()->user()->role->name === 'pelanggan';
    }

    public function rules()
    {
        return [
            'rating' => 'required|integer|min:1|max:5', // Rating antara 1 dan 5
            'review' => 'nullable|string|max:500', // Ulasan opsional, maksimal 500 karakter
            'order_id' => 'required|exists:pemesanan,id', // Pastikan pemesanan ada di database
        ];
    }

    public function messages()
    {
        return [
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating harus berupa angka.',
            'rating.min' => 'Rating minimal 1.',
            'rating.max' => 'Rating maksimal 5.',
            'review.string' => 'Ulasan harus berupa teks.',
            'review.max' => 'Ulasan tidak boleh lebih dari 500 karakter.',
            'order_id.required' => 'ID pemesanan wajib diisi.',
            'order_id.exists' => 'Pemesanannya tidak ditemukan.',
        ];
    }
}
