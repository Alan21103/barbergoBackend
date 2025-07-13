<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    // CREATE: Menambahkan Review Baru
    public function store(Request $request)
    {
        try {
            $review = Review::create([
                'pemesanan_id' => $request->pemesanan_id,  // Menggunakan pemesanan_id
                'rating' => $request->rating,
                'review' => $request->review,
                'deskripsi' => $request->deskripsi, // Menyimpan deskripsi
            ]);

            return response()->json([
                'status_code' => 201,
                'message' => 'Review created successfully',
                'data' => $review,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error creating review: ' . $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE: Memperbarui Review
    public function update(Request $request, $id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->update([
                'pemesanan_id' => $request->pemesanan_id,
                'rating' => $request->rating,
                'review' => $request->review,
                'deskripsi' => $request->deskripsi,
            ]);

            return response()->json([
                'status_code' => 200,
                'message' => 'Review updated successfully',
                'data' => $review,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error updating review: ' . $e->getMessage(),
            ], 500);
        }
    }

     public function getAllReviews()
    {
        try {
            // Ambil semua data review
            $reviews = Review::all();

            // Cek apakah ada review yang ditemukan
            if ($reviews->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada review ditemukan',
                    'status_code' => 404,
                    'data' => [] // Mengembalikan array kosong jika tidak ada data
                ], 404);
            }

            // Mengembalikan semua review
            return response()->json([
                'message' => 'Semua review ditemukan',
                'status_code' => 200,
                'data' => $reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'pemesanan_id' => $review->pemesanan_id,
                        'rating' => $review->rating,
                        'review' => $review->review,
                        'deskripsi' => $review->deskripsi,
                        'created_at' => $review->created_at,
                        'updated_at' => $review->updated_at,
                        // Tambahkan kolom lain yang relevan jika ada
                    ];
                })->toArray() // Pastikan untuk mengonversi koleksi menjadi array
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getAllReviews: ' . $e->getMessage()); // Log error
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}