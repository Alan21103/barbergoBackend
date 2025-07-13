<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use Illuminate\Http\Request;
use App\Http\Requests\JadwalRequest;
use Illuminate\Support\Facades\Auth; // Import the Auth facade

class JadwalController extends Controller
{
    // CREATE: Menambahkan Jadwal Baru (digunakan oleh admin, membutuhkan barber_id dari request)
    public function store(JadwalRequest $request)
    {
        try {
            // Membuat data jadwal baru
            $jadwal = Jadwal::create([
                'barber_id' => $request->barber_id, // Mengambil barber_id dari request (untuk admin)
                'hari' => $request->hari,
                'tersedia_dari' => $request->tersedia_dari,
                'tersedia_hingga' => $request->tersedia_hingga,
            ]);

            return response()->json([
                'status_code' => 201,
                'message' => 'Jadwal created successfully',
                'data' => $jadwal,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error creating jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    // CREATE: Menambahkan Jadwal Baru untuk barber yang sedang login
    public function storeMyJadwal(JadwalRequest $request)
    {
        try {
            // Pastikan pengguna sudah login
            if (!Auth::check()) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in',
                    'data' => [],
                ], 401);
            }

            // Ambil ID pengguna yang sedang login sebagai barber_id
            $barberId = Auth::id();

            // Membuat data jadwal baru dengan barber_id dari pengguna yang login
            $jadwal = Jadwal::create([
                'barber_id' => $barberId, // Mengambil barber_id dari pengguna yang login
                'hari' => $request->hari,
                'tersedia_dari' => $request->tersedia_dari,
                'tersedia_hingga' => $request->tersedia_hingga,
            ]);

            return response()->json([
                'status_code' => 201,
                'message' => 'Jadwal created successfully for logged-in barber',
                'data' => $jadwal,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error creating jadwal for logged-in barber: ' . $e->getMessage(),
            ], 500);
        }
    }


    // READ: Menampilkan Semua Jadwal
    public function index()
    {
        try {
            $jadwal = Jadwal::all();

            if ($jadwal->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No jadwal found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Jadwal retrieved successfully',
                'data' => $jadwal,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    // READ: Menampilkan Jadwal Berdasarkan ID
    public function show($id)
    {
        try {
            $jadwal = Jadwal::findOrFail($id);  // Menampilkan jadwal berdasarkan ID

            return response()->json([
                'status_code' => 200,
                'message' => 'Jadwal retrieved successfully',
                'data' => $jadwal,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Jadwal not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE: Memperbarui Jadwal
    // Metode ini sekarang akan memeriksa kepemilikan jadwal berdasarkan pengguna yang login
    public function update(JadwalRequest $request, $id)
    {
        try {
            // Pastikan pengguna sudah login
            if (!Auth::check()) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in',
                ], 401);
            }

            $barberId = Auth::id();

            // Mencari jadwal berdasarkan ID dan memastikan itu milik barber yang login
            $jadwal = Jadwal::where('id', $id)
                            ->where('barber_id', $barberId)
                            ->firstOrFail(); // Gunakan firstOrFail untuk 404 jika tidak ditemukan/tidak dimiliki

            // Memperbarui data jadwal
            $jadwal->update([
                // 'barber_id' tidak perlu diperbarui karena sudah diambil dari Auth::id()
                'hari' => $request->hari,
                'tersedia_dari' => $request->tersedia_dari,
                'tersedia_hingga' => $request->tersedia_hingga,
            ]);
            
            return response()->json([
                'status_code' => 200,
                'message' => 'Jadwal updated successfully',
                'data' => $jadwal,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Jadwal not found or not owned by logged-in barber',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error updating jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    // DELETE: Menghapus Jadwal
    // Metode ini sekarang akan memeriksa kepemilikan jadwal berdasarkan pengguna yang login
    public function destroy($id)
    {
        try {
            // Pastikan pengguna sudah login
            if (!Auth::check()) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in',
                ], 401);
            }

            $barberId = Auth::id();

            // Mencari jadwal berdasarkan ID dan memastikan itu milik barber yang login
            $jadwal = Jadwal::where('id', $id)
                            ->where('barber_id', $barberId)
                            ->firstOrFail(); // Gunakan firstOrFail untuk 404 jika tidak ditemukan/tidak dimiliki

            // Menghapus jadwal
            $jadwal->delete();

            return response()->json([
                'status_code' => 200,
                'message' => 'Jadwal deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Jadwal not found or not owned by logged-in barber',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error deleting jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    // READ: Menampilkan Jadwal Berdasarkan Token yang Login (barber_id)
    public function getMyJadwal()
    {
        try {
            // Pastikan pengguna sudah login
            if (!Auth::check()) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in',
                    'data' => [],
                ], 401);
            }

            // Ambil ID pengguna yang sedang login
            $barberId = Auth::id(); // Assuming the authenticated user's ID is the barber_id

            // Ambil semua jadwal yang terkait dengan barber_id ini
            $jadwal = Jadwal::where('barber_id', $barberId)->get();

            if ($jadwal->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No jadwal found for the logged-in barber',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Jadwal for logged-in barber retrieved successfully',
                'data' => $jadwal,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving jadwal for logged-in barber: ' . $e->getMessage(),
            ], 500);
        }
    }
}
