<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePortfolioRequest extends FormRequest
{
    public function authorize()
    {
        // Pastikan hanya barber yang dapat mengupload portofolio
        return auth()->user() && auth()->user()->role->name === 'barber';
    }

    public function rules()
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi gambar (maks 2MB)
            'description' => 'nullable|string|max:255', // Deskripsi opsional
        ];
    }

    public function messages()
    {
        return [
            'image.required' => 'Gambar portofolio wajib diunggah.',
            'image.image' => 'File yang diunggah harus berupa gambar.',
            'image.mimes' => 'Hanya gambar dengan format jpeg, png, jpg, gif yang diizinkan.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
        ];
    }
}
