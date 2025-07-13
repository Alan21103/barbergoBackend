<?php

// app/Http/Requests/StoreServiceRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize()
    {
        // Pastikan hanya admin atau pengguna yang memiliki hak akses yang dapat menambah layanan
        return auth()->user() && auth()->user()->role->name === 'admin';
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255', // Nama layanan
            'price' => 'required|numeric|min:0', // Harga layanan
            'description' => 'nullable|string|max:500', // Deskripsi layanan opsional
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama layanan wajib diisi.',
            'price.required' => 'Harga layanan wajib diisi.',
            'price.numeric' => 'Harga layanan harus berupa angka.',
            'price.min' => 'Harga layanan tidak boleh kurang dari 0.',
            'description.string' => 'Deskripsi layanan harus berupa teks.',
            'description.max' => 'Deskripsi layanan tidak boleh lebih dari 500 karakter.',
        ];
    }
}
