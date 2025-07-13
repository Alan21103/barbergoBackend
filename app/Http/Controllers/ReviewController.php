<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Pemesanan; // Import model Pemesanan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Validator; // Import Validator facade
use Illuminate\Database\Eloquent\ModelNotFoundException; // Import ModelNotFoundException

class ReviewController extends Controller
{
    // CREATE: Menambahkan Review Baru
    // pemesananId akan datang dari parameter rute, contoh: /api/pemesanan/{pemesananId}/reviews
    public function store(Request $request, $pemesananId)
    {
        Log::info('ReviewController@store: Request received for pemesanan_id: ' . $pemesananId, $request->all());

        // Pastikan pengguna terautentikasi
        $user = Auth::user();
        if (!$user) {
            Log::warning('ReviewController@store: Unauthorized - User not logged in.');
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized: User not logged in.',
            ], 401);
        }

        // Dapatkan objek pelanggan yang terkait dengan user yang login
        $pelanggan = $user->pelanggan;
        if (!$pelanggan) {
            Log::warning('ReviewController@store: Authenticated user has no associated customer profile. User ID: ' . $user->id);
            return response()->json([
                'status_code' => 400,
                'message' => 'Profil pelanggan tidak ditemukan untuk pengguna ini. Mohon lengkapi profil Anda.',
            ], 400);
        }

        // Validasi data request (pemesanan_id tidak lagi dari request body)
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::warning('ReviewController@store: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'status_code' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Cari pemesanan berdasarkan ID dan pastikan itu milik pelanggan yang login
            $pemesanan = Pemesanan::where('id', $pemesananId)
                                  ->where('pelanggan_id', $pelanggan->id) // Pastikan pemesanan milik pelanggan ini
                                  ->first();

            if (!$pemesanan) {
                Log::warning('ReviewController@store: Pemesanan not found or does not belong to the authenticated customer.', [
                    'pemesanan_id' => $pemesananId,
                    'pelanggan_id_auth' => $pelanggan->id
                ]);
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Pemesanan tidak ditemukan atau tidak dimiliki oleh Anda.',
                ], 404);
            }

            // Cek apakah review untuk pemesanan ini sudah ada
            $existingReview = Review::where('pemesanan_id', $pemesanan->id)->first();
            if ($existingReview) {
                Log::warning('ReviewController@store: Review already exists for this pemesanan_id.', ['pemesanan_id' => $pemesanan->id]);
                return response()->json([
                    'status_code' => 409, // Conflict
                    'message' => 'Anda sudah memberikan review untuk pemesanan ini.',
                ], 409);
            }

            // Buat review baru
            $review = Review::create([
                'pemesanan_id' => $pemesanan->id, // Gunakan pemesanan->id yang sudah divalidasi
                'rating' => $request->rating,
                'review' => $request->review,
                'deskripsi' => $request->deskripsi,
            ]);

            Log::info('Review created successfully.', ['review_id' => $review->id, 'pemesanan_id' => $pemesanan->id]);

            return response()->json([
                'status_code' => 201,
                'message' => 'Review created successfully',
                'data' => $review,
            ], 201);
        } catch (ModelNotFoundException $e) {
            Log::error('ReviewController@store: Pemesanan not found for ID: ' . $pemesananId . ' - ' . $e->getMessage());
            return response()->json([
                'status_code' => 404,
                'message' => 'Pemesanan tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error creating review: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error creating review: ' . $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE: Memperbarui Review
    public function update(Request $request, $id)
    {
        Log::info('ReviewController@update: Request received for review ID: ' . $id, $request->all());

        // Pastikan pengguna terautentikasi dan memiliki review ini
        $user = Auth::user();
        if (!$user) {
            Log::warning('ReviewController@update: Unauthorized - User not logged in.');
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized: User not logged in.',
            ], 401);
        }

        $pelanggan = $user->pelanggan;
        if (!$pelanggan) {
            Log::warning('ReviewController@update: Authenticated user has no associated customer profile. User ID: ' . $user->id);
            return response()->json([
                'status_code' => 400,
                'message' => 'Profil pelanggan tidak ditemukan.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'nullable|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::warning('ReviewController@update: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'status_code' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Cari review berdasarkan ID dan pastikan pemesanan_id terkait dimiliki oleh pelanggan yang login
            $review = Review::where('id', $id)
                            ->whereHas('pemesanan', function ($query) use ($pelanggan) {
                                $query->where('pelanggan_id', $pelanggan->id);
                            })
                            ->first();

            if (!$review) {
                Log::warning('ReviewController@update: Review not found or does not belong to the authenticated customer.', [
                    'review_id' => $id,
                    'pelanggan_id_auth' => $pelanggan->id
                ]);
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Review tidak ditemukan atau tidak dimiliki oleh Anda.',
                ], 404);
            }

            // Perbarui review
            $review->update($request->only(['rating', 'review', 'deskripsi']));

            Log::info('Review updated successfully.', ['review_id' => $review->id]);

            return response()->json([
                'status_code' => 200,
                'message' => 'Review updated successfully',
                'data' => $review,
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('ReviewController@update: Review not found for ID: ' . $id . ' - ' . $e->getMessage());
            return response()->json([
                'status_code' => 404,
                'message' => 'Review not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating review: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error updating review: ' . $e->getMessage(),
            ], 500);
        }
    }

    // DELETE: Menghapus Review
    public function destroy($id)
    {
        Log::info('ReviewController@destroy: Request received for review ID: ' . $id);

        // Pastikan pengguna terautentikasi dan memiliki review ini
        $user = Auth::user();
        if (!$user) {
            Log::warning('ReviewController@destroy: Unauthorized - User not logged in.');
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized: User not logged in.',
            ], 401);
        }

        $pelanggan = $user->pelanggan;
        if (!$pelanggan) {
            Log::warning('ReviewController@destroy: Authenticated user has no associated customer profile. User ID: ' . $user->id);
            return response()->json([
                'status_code' => 400,
                'message' => 'Profil pelanggan tidak ditemukan.',
            ], 400);
        }

        try {
            // Cari review berdasarkan ID dan pastikan pemesanan_id terkait dimiliki oleh pelanggan yang login
            $review = Review::where('id', $id)
                            ->whereHas('pemesanan', function ($query) use ($pelanggan) {
                                $query->where('pelanggan_id', $pelanggan->id);
                            })
                            ->first();

            if (!$review) {
                Log::warning('ReviewController@destroy: Review not found or does not belong to the authenticated customer.', [
                    'review_id' => $id,
                    'pelanggan_id_auth' => $pelanggan->id
                ]);
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Review tidak ditemukan atau tidak dimiliki oleh Anda.',
                ], 404);
            }

            $review->delete(); // Menghapus review

            Log::info('Review deleted successfully.', ['review_id' => $id]);

            return response()->json([
                'status_code' => 200,
                'message' => 'Review deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('ReviewController@destroy: Review not found for ID: ' . $id . ' - ' . $e->getMessage());
            return response()->json([
                'status_code' => 404,
                'message' => 'Review not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting review: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error deleting review: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAllReviews()
    {
        try {
            // Eager load pemesanan, dan dari pemesanan, eager load pelanggan, barber, dan service
            $reviews = Review::with([
                'pemesanan',
                'pemesanan.pelanggan',
                'pemesanan.barber',
                'pemesanan.service'
            ])->get();

            // Cek apakah ada review yang ditemukan
            if ($reviews->isEmpty()) {
                Log::info('ReviewController@getAllReviews: No reviews found.');
                return response()->json([
                    'message' => 'Tidak ada review ditemukan',
                    'status_code' => 200, // Mengubah status menjadi 200 karena ini bukan error
                    'data' => []
                ], 200);
            }

            Log::info('ReviewController@getAllReviews: Retrieved ' . $reviews->count() . ' reviews.');
            // Mengembalikan semua review dengan relasi yang sudah di-eager load
            return response()->json([
                'message' => 'Semua review ditemukan',
                'status_code' => 200,
                'data' => $reviews // Data sudah dalam format yang benar dengan relasi
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getAllReviews: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
