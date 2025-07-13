<?php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use App\Models\User;
use App\Models\Service; // Pastikan model Service diimpor
use App\Models\Admin;   // Pastikan model Admin diimpor (untuk barber/admin profile)
use App\Services\GoogleDistanceService; // Pastikan Service ini ada dan berfungsi
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Import Validator facade

class PemesananController extends Controller
{
    protected $googleDistanceService;

    public function __construct(GoogleDistanceService $googleDistanceService)
    {
        $this->googleDistanceService = $googleDistanceService;
    }

    /**
     * Menghitung ongkir menggunakan layanan GoogleDistanceService.
     */
    public function calculateOngkir($lat1, $lon1, $lat2, $lon2)
    {
        // Pastikan GoogleDistanceService Anda mengembalikan nilai ongkir yang benar (numeric)
        return $this->googleDistanceService->calculateOngkir($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * Menyimpan pemesanan baru ke database.
     */
    public function store(Request $request)
    {
        Log::info('PemesananController@store: Request received.', $request->all());
        Log::info('PemesananController@store: Auth::check() result: ' . Auth::check());
        if (Auth::check()) {
            Log::info('PemesananController@store: Authenticated User ID: ' . Auth::id() . ', Role: ' . Auth::user()->role_id);
        }

        if (!Auth::check()) {
            Log::warning('PemesananController@store: Unauthorized - User not logged in.');
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized: User not logged in.',
            ], 401);
        }

        $user = Auth::user();
        // --- PERBAIKAN PENTING DI SINI ---
        // Dapatkan objek pelanggan yang terkait dengan user yang login
        $pelanggan = $user->pelanggan; // Asumsi relasi 'pelanggan' sudah didefinisikan di model User

        if (!$pelanggan) {
            Log::warning('PemesananController@store: Authenticated user has no associated customer profile. User ID: ' . $user->id);
            return response()->json([
                'status_code' => 400,
                'message' => 'Profil pelanggan tidak ditemukan untuk pengguna ini. Mohon lengkapi profil Anda.',
            ], 400);
        }
        // $pelangganId sekarang akan berisi ID dari tabel 'pelanggan' (misal: 2), bukan 'users.id' (misal: 7)
        $pelangganId = $pelanggan->id; 
        Log::info('PemesananController@store: Pelanggan ID yang akan digunakan: ' . $pelangganId);
        // --- AKHIR PERBAIKAN PENTING ---

        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|exists:admin,id', // Validasi admin_id sebagai PK dari tabel 'admin'
            'service_id' => 'required|exists:services,id',
            'scheduled_time' => 'required|date',
            'alamat' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            Log::warning('PemesananController@store: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'status_code' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Temukan Admin Profile berdasarkan ID yang diterima dari request
            $adminProfile = Admin::find($request->admin_id);

            if (!$adminProfile) {
                Log::warning('PemesananController@store: Admin profile not found with ID: ' . $request->admin_id);
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Selected admin profile not found. Please ensure the admin ID is valid.',
                ], 404);
            }

            // Sekarang kita punya adminProfile, ambil user_id-nya untuk mencari User
            $barberUser = User::find($adminProfile->user_id);

            Log::info('PemesananController@store: Admin ID from request: ' . $request->admin_id);
            Log::info('PemesananController@store: Found Admin Profile User ID: ' . $adminProfile->user_id);
            Log::info('PemesananController@store: Barber User object found: ' . ($barberUser ? 'Yes' : 'No'));
            if ($barberUser) {
                Log::info('PemesananController@store: Found Barber User ID: ' . $barberUser->id . ', Role ID: ' . $barberUser->role_id);
            }

            // Pastikan user ditemukan DAN apakah role_id-nya adalah 1 (asumsi 1 = role 'admin'/'barber')
            if (!$barberUser || $barberUser->role_id !== 1) {
                Log::warning('PemesananController@store: Selected admin is not a valid barber or does not have the right role. Expected role_id 1.');
                return response()->json([
                    'status_code' => 400,
                    'message' => 'Selected admin is not a valid barber or does not have the right role. Expected role_id 1.',
                ], 400);
            }

            // Pastikan latitude dan longitude admin tidak null sebelum perhitungan ongkir
            if (is_null($adminProfile->latitude) || is_null($adminProfile->longitude)) {
                Log::warning('PemesananController@store: Barber location (latitude/longitude) is not set in admin profile for Admin ID: ' . $adminProfile->id);
                return response()->json([
                    'status_code' => 400,
                    'message' => 'Barber location (latitude/longitude) is not set in admin profile.',
                ], 400);
            }

            // Hitung Ongkir
            $ongkir = $this->calculateOngkir(
                $adminProfile->latitude,
                $adminProfile->longitude,
                $request->latitude,
                $request->longitude
            );
            Log::info('PemesananController@store: Calculated ongkir: ' . $ongkir);

            // Ambil harga layanan
            $service = Service::find($request->service_id);
            if (!$service) {
                Log::warning('PemesananController@store: Service not found with ID: ' . $request->service_id);
                return response()->json([
                    'message' => 'Layanan tidak ditemukan.',
                    'status_code' => 404
                ], 404);
            }
            $servicePrice = $service->price;
            Log::info('PemesananController@store: Service price: ' . $servicePrice);

            // Hitung Total Price
            $totalPrice = $servicePrice + $ongkir;
            Log::info('PemesananController@store: Total price (service + ongkir): ' . $totalPrice);

            $pemesanan = Pemesanan::create([
                'pelanggan_id' => $pelangganId, // *** SUDAH DIPERBAIKI: Gunakan ID dari objek pelanggan ***
                'barber_id' => $barberUser->id, // barber_id di tabel pemesanan akan menyimpan users.id dari barber
                'service_id' => $request->service_id,
                'scheduled_time' => $request->scheduled_time,
                'status' => 'pending',
                'alamat' => $request->alamat,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'ongkir' => $ongkir,
                'admin_id' => $adminProfile->user_id, // admin_id di tabel pemesanan akan menyimpan users.id dari profil admin
                'total_price' => $totalPrice, // Simpan total harga
            ]);

            Log::info('Pemesanan created successfully.', ['pemesanan_id' => $pemesanan->id, 'total_price' => $totalPrice, 'ongkir' => $ongkir]);

            return response()->json([
                'status_code' => 201,
                'message' => 'Pemesanan created successfully',
                'data' => $pemesanan,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating pemesanan', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan daftar pemesanan untuk pelanggan yang sedang login.
     */
    public function index()
    {
        try {
            if (!Auth::check()) {
                Log::info('index (getPemesananByPelanggan): Unauthorized - Auth::check() returned false');
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in.',
                ], 401);
            }

            $user = Auth::user();

            if (!$user) {
                Log::info('index (getPemesananByPelanggan): User object is null after Auth::user()');
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Internal Server Error: Could not retrieve authenticated user.',
                ], 500);
            }

            // --- PERBAIKAN PENTING DI SINI ---
            // Dapatkan objek pelanggan yang terkait dengan user yang login
            $pelanggan = $user->pelanggan; // Asumsi relasi 'pelanggan' sudah didefinisikan di model User

            if (!$pelanggan) {
                Log::warning('index (getPemesananByPelanggan): Authenticated user has no associated customer profile. User ID: ' . $user->id);
                return response()->json([
                    'status_code' => 400,
                    'message' => 'Profil pelanggan tidak ditemukan untuk pengguna ini.',
                ], 400);
            }
            $pelangganIdFilter = $pelanggan->id;
            Log::info('index (getPemesananByPelanggan): Pelanggan ID yang akan digunakan untuk filter: ' . $pelangganIdFilter);
            // --- AKHIR PERBAIKAN PENTING ---

            Log::info('index (getPemesananByPelanggan): Authenticated User ID: ' . $user->id);

            // Eager load service, barber, dan admin (jika admin_id berbeda dari barber_id)
            $pemesanan = Pemesanan::with('service', 'barber', 'admin') // Menambahkan 'admin' jika diperlukan
                ->where('pelanggan_id', $pelangganIdFilter) // *** SUDAH DIPERBAIKI: Gunakan ID dari objek pelanggan ***
                ->get();

            Log::info('index (getPemesananByPelanggan): Retrieved Pemesanan Count: ' . $pemesanan->count());
            Log::info('index (getPemesananByPelanggan): Retrieved Pemesanan Data: ' . $pemesanan->toJson());

            $message = 'Pemesanan retrieved successfully for the current user.';
            if ($pemesanan->isEmpty()) {
                $message = 'Pemesanan tidak ditemukan.';
            }

            return response()->json([
                'status_code' => 200,
                'message' => $message,
                'data' => $pemesanan,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving user-specific pemesanan', ['error' => $e->getMessage()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving user-specific pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan daftar pemesanan untuk pelanggan yang sedang login (alias untuk index).
     * Metode ini kemungkinan tidak diperlukan jika 'index' sudah melayani tujuan ini.
     * Dibiarkan untuk kompatibilitas jika ada rute yang masih menggunakannya.
     */
    public function getPemesananByPelanggan(Request $request)
    {
        // Logika sama dengan index(), sebaiknya gunakan salah satu saja.
        return $this->index();
    }

    /**
     * Menampilkan detail Pemesanan Berdasarkan ID.
     */
    public function show($id)
    {
        try {
            // Eager load service, pelanggan, barber, dan admin
            $pemesanan = Pemesanan::with(['service', 'pelanggan', 'barber', 'admin'])->findOrFail($id);

            return response()->json([
                'status_code' => 200,
                'message' => 'Pemesanan retrieved successfully',
                'data' => $pemesanan,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Pemesanan not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Memperbarui Pemesanan yang sudah ada berdasarkan ID.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'nullable|exists:services,id',
            'scheduled_time' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled,paid', // Tambahkan 'paid'
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'admin_id' => 'nullable|exists:admin,id', // Tambahkan validasi jika admin_id bisa diubah
        ]);

        if ($validator->fails()) {
            Log::warning('PemesananController@update: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'status_code' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pemesanan = Pemesanan::findOrFail($id);

            // Ambil data yang akan diupdate
            $dataToUpdate = $request->only([
                'service_id',
                'scheduled_time',
                'status',
                'alamat',
                'latitude',
                'longitude',
                'admin_id'
            ]);

            // Cek apakah ada perubahan yang memerlukan perhitungan ulang ongkir/total_price
            $recalculatePrices = false;
            if (isset($dataToUpdate['service_id']) && $dataToUpdate['service_id'] != $pemesanan->service_id) {
                $recalculatePrices = true;
            }
            if (isset($dataToUpdate['latitude']) && $dataToUpdate['latitude'] != $pemesanan->latitude) {
                $recalculatePrices = true;
            }
            if (isset($dataToUpdate['longitude']) && $dataToUpdate['longitude'] != $pemesanan->longitude) {
                $recalculatePrices = true;
            }
            if (isset($dataToUpdate['admin_id']) && $dataToUpdate['admin_id'] != $pemesanan->admin_id) {
                $recalculatePrices = true;
            }

            if ($recalculatePrices) {
                // Ambil harga layanan yang baru (jika service_id diubah)
                $currentServiceId = $dataToUpdate['service_id'] ?? $pemesanan->service_id;
                $service = Service::find($currentServiceId);
                if (!$service) {
                    return response()->json([
                        'message' => 'Layanan tidak ditemukan untuk perhitungan ulang.',
                        'status_code' => 404
                    ], 404);
                }
                $servicePrice = $service->price;

                // Ambil lokasi admin yang baru (jika admin_id diubah)
                $currentAdminId = $dataToUpdate['admin_id'] ?? $pemesanan->admin_id;
                $adminProfile = Admin::find($currentAdminId);
                if (!$adminProfile || is_null($adminProfile->latitude) || is_null($adminProfile->longitude)) {
                    return response()->json([
                        'status_code' => 400,
                        'message' => 'Lokasi barber tidak valid untuk perhitungan ulang.',
                        'status_code' => 400
                    ], 400);
                }

                // Hitung ulang ongkir
                $ongkir = $this->calculateOngkir(
                    $adminProfile->latitude,
                    $adminProfile->longitude,
                    $dataToUpdate['latitude'] ?? $pemesanan->latitude,
                    $dataToUpdate['longitude'] ?? $pemesanan->longitude
                );

                // Hitung ulang total harga
                $totalPrice = $servicePrice + $ongkir;

                $dataToUpdate['ongkir'] = $ongkir;
                $dataToUpdate['total_price'] = $totalPrice;
                Log::info('PemesananController@update: Recalculated prices.', ['ongkir' => $ongkir, 'total_price' => $totalPrice]);
            }

            $pemesanan->update($dataToUpdate);
            // $pemesanan->save(); // save() tidak diperlukan setelah update() jika tidak ada perubahan lain

            return response()->json([
                'status_code' => 200,
                'message' => 'Pemesanan updated successfully',
                'data' => $pemesanan,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('PemesananController@update: Pemesanan not found with ID: ' . $id);
            return response()->json([
                'status_code' => 404,
                'message' => 'Pemesanan not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating pemesanan', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error updating pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menghapus Pemesanan.
     */
    public function destroy($id)
    {
        try {
            $pemesanan = Pemesanan::findOrFail($id);
            $pemesanan->delete();

            return response()->json([
                'status_code' => 200,
                'message' => 'Pemesanan deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Pemesanan not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error deleting pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengubah status pemesanan menjadi 'cancelled'.
     */
    public function cancelPemesanan(Request $request, $id)
    {
        try {
            $pemesanan = Pemesanan::findOrFail($id);

            if ($pemesanan->status === 'completed' || $pemesanan->status === 'cancelled') {
                return response()->json([
                    'status_code' => 400,
                    'message' => 'Pemesanan tidak dapat dibatalkan karena status sudah ' . $pemesanan->status . '.',
                ], 400);
            }

            $pemesanan->status = 'cancelled';
            $pemesanan->save();

            return response()->json([
                'status_code' => 200,
                'message' => 'Pemesanan berhasil dibatalkan.',
                'data' => $pemesanan,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Pemesanan tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat membatalkan pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        // Validasi data request
        $request->validate([
            // Menambahkan 'paid' ke dalam daftar status yang valid
            'status' => 'required|in:pending,confirmed,completed,cancelled,paid',
        ]);
        
        try {
            // Mencari pemesanan berdasarkan ID
            $pemesanan = Pemesanan::findOrFail($id);

            // Memperbarui status pemesanan
            $pemesanan->status = $request->status;
            $pemesanan->save();

            return response()->json([
                'status_code' => 200,
                'message' => 'Status pemesanan updated successfully',
                'data' => $pemesanan,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Pemesanan not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAllPemesanan(Request $request)
    {
        // Pastikan hanya admin yang bisa mengakses ini
        // Anda bisa menggunakan Gate/Policy Laravel atau middleware khusus 'admin'
        // Contoh sederhana (sesuaikan dengan logika role Anda):
        if (Auth::user()->role_id !== 1) { // Asumsi role_id 1 adalah admin
            return response()->json([
                'message' => 'Unauthorized: Admin access required.'
            ], 403);
        }

        try {
            $pemesanans = Pemesanan::with(['pelanggan', 'barber', 'service'])->get(); // Eager load relasi jika ada
            // Anda bisa menyesuaikan data yang dikirim dengan resource collection jika dibutuhkan
            return response()->json([
                'message' => 'All bookings retrieved successfully',
                'data' => $pemesanans
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve all bookings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
