<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule; // Import Rule

class StorePortfolioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        Log::info("--- AUTHORIZE PORTFOLIO REQUEST DEBUG ---");

        if (!Auth::check()) {
            Log::warning("Authorization failed: User not logged in.");
            return false;
        }

        $user = Auth::user();
        Log::info("User is authenticated. User ID: {$user->id}, User Email: {$user->email}");

        if (!$user->role) {
            Log::warning("Authorization failed: User has no role assigned. User ID: {$user->id}");
            return false;
        }

        $userRoleName = $user->role->name;
        Log::info("User role name: {$userRoleName}");

        // Mengizinkan 'barber' atau 'admin' untuk mengelola portofolio
        if ($userRoleName === 'barber' || $userRoleName === 'admin') {
            Log::info("Authorization successful: User is a barber or admin.");
            return true;
        } else {
            Log::warning("Authorization failed: User role is '{$userRoleName}', expected 'barber' or 'admin'. User ID: {$user->id}");
            return false;
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'description' => 'nullable|string|max:255', // Deskripsi opsional
        ];

        // Untuk operasi store (POST), gambar wajib diisi dan harus berupa string Base64
        if ($this->isMethod('POST')) {
            $rules['image'] = [
                'required',
                'string',
                // Anda bisa menambahkan aturan regex untuk validasi Base64 yang lebih ketat jika perlu,
                // atau validasi ukuran setelah didecode di controller.
                // Contoh regex sederhana untuk Base64:
                // 'regex:/^data:image\/(jpeg|png|jpg|gif|svg\+xml);base64,([A-Za-z0-9+\/]{4})*([A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/'
            ];
        }

        // Untuk operasi update (PUT/PATCH), gambar bersifat opsional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['image'] = [
                'nullable', // Gambar bisa null jika tidak diubah
                'string',
                // 'regex:/^data:image\/(jpeg|png|jpg|gif|svg\+xml);base64,([A-Za-z0-9+\/]{4})*([A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/'
            ];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'image.required' => 'Gambar portofolio wajib diunggah.',
            'image.string' => 'Format gambar tidak valid (diharapkan Base64 string).',
            // 'image.regex' => 'Format Base64 gambar tidak valid.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
        ];
    }
}
