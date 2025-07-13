<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PemesananController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('admin/profile', [ProfileController::class, 'adminProfile']);
    Route::get('admin/profile', [ProfileController::class, 'getAdminProfile']);
    // Endpoint untuk memperbarui profil admin
    Route::put('admin/profile', [ProfileController::class, 'updateProfile']);
});

Route::middleware(['auth:api'])->get('admin/profiles', [ProfileController::class, 'getAllAdminProfiles']);

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Layanan (Service) - Admin dapat mengelola layanan barber manapun jika metode controller mendukung
    Route::post('admin/services', [ServiceController::class, 'store'])->name('admin.service.store');
    Route::get('admin/services/{id}', [ServiceController::class, 'show'])->name('admin.service.show');
    Route::put('admin/services/{id}', [ServiceController::class, 'update'])->name('admin.service.update');
    Route::delete('admin/services/{id}', [ServiceController::class, 'destroy'])->name('admin.service.destroy');
});

// Rute untuk pengguna terautentikasi (barber dapat mengelola layanan mereka sendiri)
Route::middleware(['auth:api'])->group(function () {
    // Layanan (Service) - Barber yang login mengelola layanan mereka sendiri
    Route::get('services', [ServiceController::class, 'index'])->name('services.index'); // Semua layanan (bisa diakses umum oleh authenticated user)
    Route::get('my-services', [ServiceController::class, 'getMyServices'])->name('my.services.index'); // Layanan milik barber yang login
    Route::post('my-services', [ServiceController::class, 'store'])->name('my.services.store'); // Membuat layanan baru (barber_id otomatis dari token)
    Route::put('my-services/{id}', [ServiceController::class, 'update'])->name('my.services.update'); // Memperbarui layanan (hanya milik sendiri)
    Route::delete('my-services/{id}', [ServiceController::class, 'destroy'])->name('my.services.destroy'); // Menghapus layanan (hanya milik sendiri)
});




Route::middleware(['auth:api'])->group(function () {
    Route::get('services', [ServiceController::class, 'index']);
});

Route::middleware(['auth:api', 'role:pelanggan'])->group(function () {
    // Menampilkan semua pemesanan
    Route::get('pelanggan/pemesanan', [PemesananController::class, 'index']);

    // Menambahkan pemesanan
    Route::post('pelanggan/pemesanan', [PemesananController::class, 'store']);

    // Menampilkan pemesanan berdasarkan ID
    Route::get('pelanggan/pemesanan/{id}', [PemesananController::class, 'show']);

    // Memperbarui pemesanan
    Route::put('pelanggan/pemesanan/{id}', [PemesananController::class, 'update']);

    // Menghapus pemesanan
    Route::delete('pelanggan/pemesanan/{id}', [PemesananController::class, 'destroy']);
    Route::get('pelanggan/pemesanan/my-orders', [PemesananController::class, 'getPemesananByPelanggan']);
});

Route::middleware(['auth:api', 'role:pelanggan'])->group(function () {
    Route::get('pelanggan/pemesanan', [PemesananController::class, 'index']);
});

Route::middleware(['auth:api'])->group(function () {
    Route::put('pemesanan/status/{id}', [PemesananController::class, 'updateStatus']);
});

// Route untuk pelanggan
Route::middleware(['auth:api', 'role:pelanggan'])->group(function () {
    // Membuat pembayaran baru
    Route::post('pelanggan/pembayaran', [PembayaranController::class, 'store']);

    // Menampilkan pembayaran berdasarkan ID
    Route::get('pelanggan/pembayaran/{id}', [PembayaranController::class, 'show']);
});

Route::middleware(['auth:api', 'role:pelanggan'])->group(function () {
    Route::get('pelanggan/pembayarans', [PembayaranController::class, 'getPembayaranByPelanggan']);
});

Route::middleware(['auth:api', 'role:admin'])->group(function () { // Anda mungkin punya middleware 'admin' sendiri
    Route::get('/admin/pembayaran', [PembayaranController::class, 'getAllPembayaran']);
});

// Route untuk admin (update dan delete pembayaran)
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Memperbarui pembayaran
    Route::put('admin/pembayaran/{id}', [PembayaranController::class, 'update']);

    // Menghapus pembayaran
    Route::delete('admin/pembayaran/{id}', [PembayaranController::class, 'destroy']);
    Route::put('admin/pembayaran/status/{id}', [PembayaranController::class, 'updateStatus']);
    
});

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Add new schedule (admin can specify barber_id)
    Route::post('admin/jadwal', [JadwalController::class, 'store'])->name('admin.jadwal.store');

    // Display schedule by ID (admin can view any schedule)
    Route::get('admin/jadwal/{id}', [JadwalController::class, 'show'])->name('admin.jadwal.show');

    // Update schedule (admin can update their own schedule via this route with current controller logic)
    Route::put('admin/jadwal/{id}', [JadwalController::class, 'update'])->name('admin.jadwal.update');

    // Delete schedule (admin can delete their own schedule via this route with current controller logic)
    Route::delete('admin/jadwal/{id}', [JadwalController::class, 'destroy'])->name('admin.jadwal.destroy');
});

// Routes for authenticated users (barbers can manage their own schedules)
Route::middleware(['auth:api'])->group(function () {
    // Display all schedules (accessible to any authenticated user, e.g., for general viewing)
    Route::get('jadwals', [JadwalController::class, 'index'])->name('jadwals.index');

    // Display schedules for the currently logged-in barber
    Route::get('my-jadwal', [JadwalController::class, 'getMyJadwal'])->name('my.jadwal.index');

    // Add new schedule for the currently logged-in barber
    // This calls storeMyJadwal which uses Auth::id() for barber_id
    Route::post('my-jadwal', [JadwalController::class, 'storeMyJadwal'])->name('my.jadwal.store');

    // Update a specific schedule for the currently logged-in barber
    // This calls the update method which uses Auth::id() for barber_id ownership check
    Route::put('my-jadwal/{id}', [JadwalController::class, 'update'])->name('my.jadwal.update');

    // Delete a specific schedule for the currently logged-in barber
    // This calls the destroy method which uses Auth::id() for barber_id ownership check
    Route::delete('my-jadwal/{id}', [JadwalController::class, 'destroy'])->name('my.jadwal.destroy');
});


Route::middleware(['auth:api', 'role:pelanggan'])->group(function () {
    // Membuat review baru untuk pemesanan tertentu
    // pemesananId akan datang dari parameter URL
    Route::post('pelanggan/pemesanan/{pemesananId}/reviews', [ReviewController::class, 'store'])->name('pelanggan.reviews.store');

    // Menampilkan review berdasarkan ID (review ID)
    Route::get('pelanggan/reviews/{id}', [ReviewController::class, 'show'])->name('pelanggan.reviews.show');

    // Memperbarui review berdasarkan ID (review ID)
    Route::put('pelanggan/reviews/{id}', [ReviewController::class, 'update'])->name('pelanggan.reviews.update');

    // Menghapus review berdasarkan ID (review ID)
    Route::delete('pelanggan/reviews/{id}', [ReviewController::class, 'destroy'])->name('pelanggan.reviews.destroy');
});

Route::get('/reviews', [ReviewController::class, 'getAllReviews']);

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Menampilkan portofolio berdasarkan ID (admin dapat melihat portofolio apapun)
    Route::get('admin/portofolios/{id}', [PortfolioController::class, 'show'])->name('admin.portofolios.show');

    // Memperbarui portofolio (admin dapat memperbarui portofolionya sendiri dengan logika controller saat ini)
    Route::put('admin/portofolios/{id}', [PortfolioController::class, 'update'])->name('admin.portofolios.update');

    // Menghapus portofolio (admin dapat menghapus portofolionya sendiri dengan logika controller saat ini)
    Route::delete('admin/portofolios/{id}', [PortfolioController::class, 'destroy'])->name('admin.portofolios.destroy');
});

// Rute untuk pengguna terautentikasi (barber dapat mengelola portofolio mereka sendiri)
Route::middleware(['auth:api'])->group(function () {
    // Menampilkan semua portofolio (dapat diakses umum oleh authenticated user)
    Route::get('portofolios', [PortfolioController::class, 'index'])->name('portofolios.index');

    // Menampilkan portofolio milik barber yang sedang login
    Route::get('my-portofolios', [PortfolioController::class, 'getMyPortfolios'])->name('my.portofolios.index');

    // Menambahkan portofolio baru untuk barber yang sedang login
    // Ini memanggil store yang menggunakan Auth::id() untuk barber_id
    Route::post('my-portofolios', [PortfolioController::class, 'store'])->name('my.portofolios.store');

    // Memperbarui portofolio tertentu untuk barber yang sedang login
    // Ini memanggil metode update yang menggunakan Auth::id() untuk pemeriksaan kepemilikan barber_id
    Route::put('my-portofolios/{id}', [PortfolioController::class, 'update'])->name('my.portofolios.update');

    // Menghapus portofolio tertentu untuk barber yang sedang login
    // Ini memanggil metode destroy yang menggunakan Auth::id() untuk pemeriksaan kepemilikan barber_id
    Route::delete('my-portofolios/{id}', [PortfolioController::class, 'destroy'])->name('my.portofolios.destroy');
});



Route::middleware(['auth:api', 'role:pelanggan'])->group(function () {
    Route::post('pelanggan/profile', [ProfileController::class, 'addPelangganProfile']);
    Route::get('pelanggan/profile', [ProfileController::class, 'getPelangganProfile']); // Ini jika Anda ingin mendapatkan profil pelanggan secara umum
    Route::get('pelanggan/profile/{id}', [ProfileController::class, 'getPelangganProfileById']); // Dapatkan profil pelanggan berdasarkan ID // Update profil pelanggan berdasarkan ID
    Route::put('pelanggan/profile/update', [ProfileController::class, 'updatePelangganProfile']);
});

Route::middleware('auth:api')->group(function () {
    // Rute untuk Admin (memerlukan middleware admin, atau pengecekan role di controller)
    Route::get('/admin/pemesanan', [PemesananController::class, 'getAllPemesanan'])
        ->middleware('role:admin'); // Contoh middleware 'role' custom
});




