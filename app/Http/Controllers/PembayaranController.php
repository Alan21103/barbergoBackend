<?php

// app/Http/Controllers/PembayaranController.php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use App\Models\Pembayaran;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PembayaranController extends Controller
{
    // CREATE: Menambahkan Pembayaran Baru
    public function store(Request $request)
    {
        // --- DEBUGGING: Log request masuk ---
        Log::info('PembayaranController@store: Request received.', $request->all());

        // Validasi data request
        $validator = Validator::make($request->all(), [
            'pemesanan_id' => 'required|exists:pemesanan,id', // Pastikan pemesanan_id valid
            'amount' => 'required|numeric|min:0', // Pastikan jumlah pembayaran valid
            'status' => 'required|in:pending,paid,failed', // Status pembayaran yang valid
            'via' => 'required|in:cash,transfer,qris', // Validasi metode pembayaran
        ]);

        if ($validator->fails()) {
            Log::warning('PembayaranController@store: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'status_code' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Mencari pemesanan yang terkait
            $pemesanan = Pemesanan::findOrFail($request->pemesanan_id);
            Log::info('PembayaranController@store: Found Pemesanan.', ['pemesanan_id' => $pemesanan->id, 'total_price' => $pemesanan->total_price]);

            // --- NEW: Validasi jika jumlah pembayaran sesuai dengan total harga pesanan ---
            // Ini penting untuk integritas data.
            // Anda bisa menyesuaikan toleransi jika ada pembulatan kecil.
            if ($request->status === 'paid') {
                if ($pemesanan->total_price === null) {
                    Log::error('PembayaranController@store: Pemesanan total_price is NULL.', ['pemesanan_id' => $pemesanan->id]);
                    return response()->json([
                        'status_code' => 500,
                        'message' => 'Total harga pesanan tidak ditemukan. Tidak dapat memproses pembayaran.'
                    ], 500);
                }

                // Membandingkan jumlah pembayaran dengan total harga pesanan
                // Gunakan perbandingan floating point dengan toleransi jika perlu (misal: abs($request->amount - $pemesanan->total_price) > 0.01)
                if ($request->amount < $pemesanan->total_price) {
                    Log::warning('PembayaranController@store: Payment amount less than total_price.', [
                        'pemesanan_id' => $pemesanan->id,
                        'requested_amount' => $request->amount,
                        'total_price' => $pemesanan->total_price
                    ]);
                    return response()->json([
                        'status_code' => 400,
                        'message' => 'Jumlah pembayaran tidak sesuai dengan total harga pesanan. Jumlah yang dibayarkan kurang.'
                    ], 400);
                }
                // Anda juga bisa menambahkan logika untuk overpayment jika diperlukan
                // if ($request->amount > $pemesanan->total_price) { ... }
            }

            // Membuat data pembayaran baru
            $pembayaran = new Pembayaran();
            $pembayaran->pemesanan_id = $pemesanan->id;
            $pembayaran->amount = $request->amount;
            $pembayaran->status = $request->status;
            $pembayaran->via = $request->via;
            $pembayaran->paid_at = $request->status === 'paid' ? now() : null; // Set waktu pembayaran saat status 'paid'
            $pembayaran->save();
            Log::info('Pembayaran record created.', ['pembayaran_id' => $pembayaran->id, 'status' => $pembayaran->status]);

            // --- NEW: Update status Pemesanan jika pembayaran berhasil ('paid') ---
            if ($request->status === 'paid') {
                // Perbarui status pemesanan
                $pemesanan->status = 'paid'; // Atau 'confirmed_payment', 'completed_payment', dll.
                $pemesanan->save();
                Log::info('PembayaranController@store: Pemesanan status updated to "paid".', ['pemesanan_id' => $pemesanan->id, 'new_status' => $pemesanan->status]);
            }

            return response()->json([
                'status_code' => 201,
                'message' => 'Pembayaran created successfully',
                'data' => $pembayaran,
            ], 201);
        } catch (ModelNotFoundException $e) {
            Log::error('PembayaranController@store: Pemesanan not found.', ['pemesanan_id' => $request->pemesanan_id, 'error' => $e->getMessage()]);
            return response()->json([
                'status_code' => 404,
                'message' => 'Pemesanan tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error creating pembayaran', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error creating pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }
    // READ: Menampilkan Pembayaran Berdasarkan ID
    public function show($id)
    {
        try {
            $pembayaran = Pembayaran::findOrFail($id); // Menampilkan pembayaran berdasarkan ID
            return response()->json([
                'status_code' => 200,
                'message' => 'Pembayaran retrieved successfully',
                'data' => $pembayaran,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Pembayaran not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE: Memperbarui Pembayaran
    public function update(Request $request, $id)
    {
        // Validasi data request
        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,paid,failed',
            'via' => 'nullable|in:cash,transfer,qris', // Memperbolehkan pembaruan metode pembayaran
        ]);

        try {
            $pembayaran = Pembayaran::findOrFail($id); // Mencari pembayaran berdasarkan ID

            // Memperbarui data pembayaran
            $pembayaran->update($request->only(['amount', 'status', 'via']));
            if ($pembayaran->status === 'paid') {
                $pembayaran->paid_at = now(); // Set waktu pembayaran saat status 'paid'
            }
            $pembayaran->save();

            return response()->json([
                'status_code' => 200,
                'message' => 'Pembayaran updated successfully',
                'data' => $pembayaran,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Pembayaran not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error updating pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    // DELETE: Menghapus Pembayaran
    public function destroy($id)
    {
        try {
            $pembayaran = Pembayaran::findOrFail($id); // Mencari pembayaran berdasarkan ID
            $pembayaran->delete(); // Menghapus pembayaran

            return response()->json([
                'status_code' => 200,
                'message' => 'Pembayaran deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Pembayaran not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error deleting pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAllPembayaran() // Mengubah nama fungsi dari index() menjadi getAllPembayaran()
    {
        try {
            $pembayaran = Pembayaran::with('pemesanan')->get();
            
            if ($pembayaran->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'message' => 'Tidak ada pembayaran ditemukan.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Daftar pembayaran berhasil diambil.',
                'data' => $pembayaran,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving payments: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving payments: ' . $e->getMessage(),
            ], 500);
        }
    }

     public function getPembayaranByPelanggan(Request $request)
    {
        try {
            $user = Auth::user(); // Mendapatkan instance user yang sedang login

            // Memastikan user adalah pelanggan dan memiliki entitas pelanggan yang terkait
            // Jika Anda menggunakan 'role_id' untuk membedakan peran, Anda bisa menambah pengecekan:
            // if ($user->role_id != 2) { // Asumsi role_id 2 adalah pelanggan
            //     return response()->json([
            //         'status_code' => 403,
            //         'message' => 'Akses ditolak. Hanya pelanggan yang dapat melihat pembayaran mereka.',
            //     ], 403);
            // }

            // Mendapatkan objek Pelanggan yang terkait dengan User yang login
            $pelanggan = $user->pelanggan;

            if (!$pelanggan) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Data pelanggan tidak ditemukan untuk pengguna ini.',
                    'data' => []
                ], 404);
            }

            // Mengambil semua pembayaran yang terhubung melalui pemesanan
            // di mana pemesanan tersebut dimiliki oleh pelanggan yang sedang login.
            $pembayaran = Pembayaran::with(['pemesanan' => function ($query) use ($pelanggan) {
                $query->where('pelanggan_id', $pelanggan->id)
                      ->with(['pelanggan', 'barber', 'service']); // Opsional: eager load relasi di pemesanan
            }])
            ->whereHas('pemesanan', function ($query) use ($pelanggan) {
                $query->where('pelanggan_id', $pelanggan->id);
            })
            ->get();

            // Filter pembayaran yang punya relasi pemesanan yang valid
            // (karena with() hanya eager load, whereHas() memastikan filter)
            // $pembayaran = $pembayaran->filter(function($payment) {
            //     return $payment->pemesanan != null;
            // });

            if ($pembayaran->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'message' => 'Tidak ada pembayaran ditemukan untuk pelanggan ini.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Daftar pembayaran berhasil diambil.',
                'data' => $pembayaran,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving payments for customer: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat mengambil pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }
}
