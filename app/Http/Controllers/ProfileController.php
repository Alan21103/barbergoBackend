<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddAdminRequest;
use Illuminate\Http\Request;
use App\Http\Requests\AddPelangganRequest;
use App\Models\Admin;
use App\Models\pelanggan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function AdminProfile(AddAdminRequest $request)
    {
        try {
            $user = Auth::guard('api')->user();

            // Cek apakah profil admin sudah ada untuk user ini
            if (Admin::where('user_id', $user->id)->exists()) {
                return response()->json([
                    'message' => 'Profil admin sudah ada',
                    'status_code' => 409,
                    'data' => null
                ], 409);
            }

            // Membuat profil admin baru
            $admin = new Admin();
            $admin->user_id = $user->id;
            $admin->name = $request->name;
            $admin->alamat = $request->alamat;  // Menambahkan alamat
            $admin->latitude = $request->latitude;  // Menambahkan latitude
            $admin->longitude = $request->longitude;  // Menambahkan longitude
            $admin->save();

            return response()->json([
                'message' => 'Profil admin berhasil dibuat',
                'status_code' => 201,
                'data' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'alamat' => $admin->alamat,
                    'latitude' => $admin->latitude,
                    'longitude' => $admin->longitude
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getAllAdminProfiles()
    {
        try {
            // Ambil semua data admin
            // Pastikan model Admin memiliki kolom 'user_id' yang dapat diakses
            $admins = Admin::all();

            // Cek apakah ada admin yang ditemukan
            if ($admins->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada profil admin ditemukan',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            // Menampilkan semua profil admin, sekarang termasuk 'user_id'
            return response()->json([
                'message' => 'Semua profil admin ditemukan',
                'status_code' => 200,
                'data' => $admins->map(function ($admin) {
                    return [
                        'id' => $admin->id,
                        'user_id' => $admin->user_id, // <--- TAMBAHAN PENTING INI
                        'name' => $admin->name,
                        'alamat' => $admin->alamat,
                        'latitude' => $admin->latitude,
                        'longitude' => $admin->longitude,
                    ];
                })
            ], 200);
        } catch (\Exception $e) {
            // Log error untuk debugging di sisi server
            Log::error('Error in getAllAdminProfiles: ' . $e->getMessage());

            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    // READ: Menampilkan Profil Admin
    public function getAdminProfile()
    {
        try {
            $user = Auth::guard('api')->user();
            $admin = Admin::where('user_id', $user->id)->first();

            // Cek jika profil admin tidak ditemukan
            if (!$admin) {
                return response()->json([
                    'message' => 'Profil admin tidak ditemukan',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            // Menampilkan profil admin
            return response()->json([
                'message' => 'Profil admin ditemukan',
                'status_code' => 200,
                'data' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'alamat' => $admin->alamat,
                    'latitude' => $admin->latitude,
                    'longitude' => $admin->longitude
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    // Method untuk update profil admin
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();  // Ambil user yang sedang login
            $admin = Admin::where('user_id', $user->id)->first();  // Ambil data admin berdasarkan user_id

            // Jika profil admin tidak ada
            if (!$admin) {
                return response()->json([
                    'message' => 'Profil admin tidak ditemukan',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            // Validasi data request
            $request->validate([
                'name' => 'nullable|string|max:255',
                'alamat' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            // Update profil admin dengan data yang baru
            $admin->update([
                'name' => $request->name ?? $admin->name,
                'alamat' => $request->alamat ?? $admin->alamat,
                'latitude' => $request->latitude ?? $admin->latitude,
                'longitude' => $request->longitude ?? $admin->longitude,
            ]);

            return response()->json([
                'message' => 'Profil admin berhasil diperbarui',
                'status_code' => 200,
                'data' => $admin
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error updating profile: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Tambah profil pelanggan
     *
     * Endpoint untuk membuat profil pelanggan. Hanya bisa dilakukan satu kali per user.
     *
     * @authenticated
     * @bodyParam name string required Nama pelanggan. Contoh: Budi
     * @bodyParam address string required Alamat pelanggan. Contoh: Jl. Merdeka No. 10
     * @bodyParam phone string required Nomor telepon pelanggan. Contoh: 08123456789
     * @bodyParam photo file Foto pengguna (JPEG/PNG/JPG/GIF). Tidak wajib.
     *
     * @response 201 {
     *   "message": "Profil pelanggan berhasil dibuat",
     *   "status_code": 201,
     *   "data": {
     *     "id": 2,
     *     "name": "Budi",
     *     "address": "Jl. Merdeka No. 10",
     *     "phone": "08123456789",
     *     "photo": "/storage/photos/abc123.jpg"
     *   }
     * }
     * @response 400 {
     *   "status_code": 400,
     *   "message": "The phone field is required.",
     *   "data": null
     * }
     * @response 409 {
     *   "message": "Profil pelanggan sudah ada",
     *   "status_code": 409,
     *   "data": null
     * }
     * @response 500 {
     *   "status_code": 500,
     *   "message": "Internal Server Error: ...",
     *   "data": null
     * }
     */

    public function addPelangganProfile(AddPelangganRequest $request)
    {

        try {
            $user = Auth::guard('api')->user();
            if (Pelanggan::where('user_id', $user->id)->exists()) {
                return response()->json([
                    'message' => 'Profil Pelanggan sudah ada',
                    'status_code' => 409,
                    'data' => null
                ], 409);
            }

            $pelanggan = new pelanggan();
            $pelanggan->user_id = $user->id;
            $pelanggan->name = $request->name;
            $pelanggan->address = $request->address;
            $pelanggan->phone = $request->phone;

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('photos', 'public');
                $pelanggan->photo = $path; // simpan hanya photos/abc.jpg

            }

            $pelanggan->save();

            return response()->json([
                'message' => 'Profil pelanggan berhasil dibuat',
                'status_code' => 201,
                'data' => [
                    'id' => $pelanggan->id,
                    'name' => $pelanggan->name,
                    'address' => $pelanggan->address,
                    'phone' => $pelanggan->phone,
                    'photo' => $pelanggan->photo,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getPelangganProfile()
    {
        try {
            $user = Auth::guard('api')->user();
            $pelanggan = Pelanggan::where('user_id', $user->id)->first();

            if (!$pelanggan) {
                return response()->json([
                    'message' => 'Profil pelanggan tidak ditemukan',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            $image = $pelanggan->photo ? asset('storage/' . $pelanggan->photo) : null;

            return response()->json([
                'message' => 'Profil pelanggan ditemukan',
                'status_code' => 200,
                'data' => [
                    'id' => $pelanggan->id,
                    'name' => $pelanggan->name,
                    'address' => $pelanggan->address,
                    'phone' => $pelanggan->phone,
                    'photo' => $image,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getPelangganProfileById($id)
    {
        try {
            // Mencari profil pelanggan berdasarkan ID
            $pelanggan = Pelanggan::find($id);

            if (!$pelanggan) {
                return response()->json([
                    'message' => 'Profil pelanggan tidak ditemukan',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            // Mengembalikan data profil pelanggan
            return response()->json([
                'message' => 'Profil pelanggan ditemukan',
                'status_code' => 200,
                'data' => [
                    'id' => $pelanggan->id,
                    'name' => $pelanggan->name,
                    'address' => $pelanggan->address,
                    'phone' => $pelanggan->phone,
                    'photo' => $pelanggan->photo ? asset('storage/' . $pelanggan->photo) : null,  // URL foto yang benar
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function updatePelangganProfile(Request $request)
    {
        try {
            // Ambil user yang login
            $user = Auth::guard('api')->user();

            // Ambil data pelanggan berdasarkan user_id
            $pelanggan = Pelanggan::where('user_id', $user->id)->first();

            // Jika profil pelanggan tidak ditemukan
            if (!$pelanggan) {
                return response()->json([
                    'message' => 'Profil pelanggan tidak ditemukan',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            // Log data request untuk debug
            Log::info('Received update data:', $request->all());

            // Validasi input
            $request->validate([
                'name' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:15',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Siapkan data untuk update
            $updateData = [
                'name' => $request->name ?? $pelanggan->name,
                'address' => $request->address ?? $pelanggan->address,
                'phone' => $request->phone ?? $pelanggan->phone,
            ];

            // Jika ada foto baru
            if ($request->hasFile('photo')) {
                // Log untuk melihat nama file foto baru
                Log::info('New photo uploaded:', ['file_name' => $request->file('photo')->getClientOriginalName()]);

                // Hapus foto lama jika ada
                if ($pelanggan->photo && Storage::disk('public')->exists($pelanggan->photo)) {
                    Storage::disk('public')->delete($pelanggan->photo);
                }

                // Simpan foto baru
                $path = $request->file('photo')->store('photos', 'public');
                $updateData['photo'] = $path;
            }

            // Update data pelanggan
            $pelanggan->update($updateData);

            // Log untuk memastikan data diperbarui
            Log::info('Pelanggan updated:', $pelanggan->toArray());

            return response()->json([
                'message' => 'Profil pelanggan berhasil diperbarui',
                'status_code' => 200,
                'data' => $pelanggan
            ], 200);

        } catch (\Exception $e) {
            // Log error jika terjadi kesalahan
            Log::error('Error updating profile for customer: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat memperbarui profil.',
                'data' => null
            ], 500);
        }
    }
}


