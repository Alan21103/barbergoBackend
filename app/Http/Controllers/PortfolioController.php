<?php

// app/Http/Controllers/PortfolioController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\Portofolios; // Pastikan nama model sudah benar (Portofolios atau Portfolio)
use Illuminate\Http\Request; // Tetap gunakan untuk show/destroy/index jika tidak ada validasi spesifik
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use App\Http\Requests\StorePortfolioRequest; // Import StorePortfolioRequest
use Illuminate\Support\Str; // Import Str untuk string helper

class PortfolioController extends Controller
{
    // CREATE: Menambah Portofolio Baru
    public function store(StorePortfolioRequest $request) // Menggunakan StorePortfolioRequest untuk validasi
    {
        // Pastikan pengguna sudah login
        if (!Auth::check()) {
            Log::warning("Unauthorized attempt to store portfolio: User not logged in.");
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized: User not logged in',
            ], 401);
        }

        // Validasi sudah ditangani oleh StorePortfolioRequest::rules() dan otorisasi oleh authorize()
        $barberId = Auth::id(); // Ambil ID barber yang sedang login

        Log::info("--- STORE PORTFOLIO DEBUG ---");
        Log::info("Request all data: " . json_encode($request->all())); // Log semua data request
        Log::info("Request has 'image' field: " . ($request->has('image') ? 'true' : 'false')); // Log apakah ada field image
        Log::info("Barber ID: " . $barberId);

        try {
            $imagePath = null;
            if ($request->has('image') && !empty($request->image)) {
                $base64Image = $request->input('image');
                $imageData = null;
                $extension = 'png'; // Default extension jika tidak dapat dideteksi

                // Periksa apakah string Base64 memiliki header data URI
                if (Str::startsWith($base64Image, 'data:')) {
                    // Pisahkan header data URI (misal: "data:image/png;base64,") dari data Base64
                    @list($type, $base64Image) = explode(';', $base64Image);
                    @list(, $base64Image) = explode(',', $base64Image);
                    
                    // Coba deteksi ekstensi dari tipe MIME
                    if (isset($type)) {
                        $mimeParts = explode('/', explode(':', $type)[1]);
                        if (isset($mimeParts[1])) {
                            $extension = $mimeParts[1];
                            // Handle svg+xml khusus
                            if ($extension === 'svg+xml') {
                                $extension = 'svg';
                            }
                        }
                    }
                }
                
                $imageData = base64_decode($base64Image);

                // Pastikan decoding berhasil dan data tidak kosong
                if ($imageData === false || empty($imageData)) {
                    Log::error("Failed to decode Base64 image data or decoded data is empty.");
                    return response()->json([
                        'status_code' => 400,
                        'message' => 'Invalid Base64 image data.',
                    ], 400);
                }

                $fileName = Str::random(40) . '.' . $extension;
                $imagePath = 'portfolios/' . $fileName;

                // Simpan file ke disk 'public'
                Storage::disk('public')->put($imagePath, $imageData);
                Log::info("Image stored path (DB value): " . $imagePath);
            } else {
                Log::warning("No image data provided for new portfolio.");
            }

            $portfolio = Portofolios::create([
                'barber_id' => $barberId, // Menggunakan ID barber yang sedang login
                'image' => $imagePath,
                'description' => $request->description,
            ]);

            Log::info("Portfolio created successfully. ID: {$portfolio->id}");
            return response()->json([
                'status_code' => 201,
                'message' => 'Portfolio created successfully',
                'data' => $portfolio,
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error creating portfolio: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error creating portfolio: ' . $e->getMessage(),
            ], 500);
        } finally {
            Log::info("--- END STORE PORTFOLIO DEBUG ---");
        }
    }

    // READ: Menampilkan semua Portofolio
    public function index()
    {
        try {
            $portfolios = Portofolios::with('barber')->get(); // Asumsi ada relasi 'barber' di model Portofolios

            if ($portfolios->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No portfolios found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Portfolios retrieved successfully with barber data',
                'data' => $portfolios,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error retrieving portfolios: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving portfolios: ' . $e->getMessage(),
            'data' => [], // Tambahkan data kosong untuk konsistensi
            ], 500);
        }
    }

    // READ: Menampilkan Portofolio berdasarkan ID
    public function show($id)
    {
        try {
            $portfolio = Portofolios::with('barber')->findOrFail($id); // Load with barber data

            return response()->json([
                'status_code' => 200,
                'message' => 'Portfolio retrieved successfully',
                'data' => $portfolio,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Portfolio with ID " . $id . " not found.", ['exception' => $e]);
            return response()->json([
                'status_code' => 404,
                'message' => 'Portfolio not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error retrieving portfolio by ID " . $id . ": " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving portfolio: ' . $e->getMessage(),
            ], 500);
        }
    }

    // READ: Menampilkan Portofolio milik barber yang sedang login
    public function getMyPortfolios()
    {
        // Pastikan pengguna sudah login (ditangani di authorize() StorePortfolioRequest jika digunakan)
        if (!Auth::check()) {
            Log::warning("Unauthorized attempt to getMyPortofolios: User not logged in.");
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized: User not logged in',
                'data' => [],
            ], 401);
        }

        try {
            $barberId = Auth::id(); // Ambil ID barber yang sedang login
            Log::info("Attempting to retrieve portfolios for barber ID: $barberId");

            $portfolios = Portofolios::where('barber_id', $barberId)->get();

            if ($portfolios->isEmpty()) {
                Log::info("No portfolios found for logged-in barber ID: $barberId");
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No portfolios found for the logged-in barber',
                    'data' => [],
                ], 404);
            }

            Log::info("Portfolios for logged-in barber ID: $barberId retrieved successfully. Count: {$portfolios->count()}");
            return response()->json([
                'status_code' => 200,
                'message' => 'Portfolios for logged-in barber retrieved successfully',
                'data' => $portfolios,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error retrieving portfolios for logged-in barber: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving portfolios: ' . $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE: Memperbarui Portofolio
    public function update(StorePortfolioRequest $request, $id) // Menggunakan StorePortfolioRequest untuk validasi
    {
        // Pastikan pengguna sudah login
        if (!Auth::check()) {
            Log::warning("Unauthorized attempt to update portfolio ID: $id. User not logged in.");
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized: User not logged in',
            ], 401);
        }

        // Validasi sudah ditangani oleh StorePortfolioRequest::rules() dan otorisasi oleh authorize()
        $barberId = Auth::id(); // Ambil ID barber yang sedang login

        Log::info("--- STARTING PORTFOLIO UPDATE FOR ID: " . $id . " ---");
        Log::info("Request all data: " . json_encode($request->all())); // Log semua data request
        Log::info("Request has 'image' field: " . ($request->has('image') ? 'true' : 'false')); // Log apakah ada field image

        try {
            $portfolio = Portofolios::where('id', $id)
                                    ->where('barber_id', $barberId) // Pastikan portofolio milik barber yang login
                                    ->firstOrFail();
            
            Log::info("Portfolio found with ID: " . $portfolio->id . " for barber ID: " . $barberId);
            Log::info("Current image path in DB: " . $portfolio->getRawOriginal('image'));
            Log::info("Current description in DB: " . $portfolio->description);
            Log::info("Received description from request: " . ($request->description ?? 'null (not provided)'));

            $imagePath = $portfolio->getRawOriginal('image'); // Ambil nilai asli dari DB

            // Periksa jika ada gambar baru yang dikirim (berupa Base64 string)
            if ($request->has('image') && !empty($request->image)) {
                Log::info("New image data (Base64) detected in request.");

                // Hapus gambar lama jika ada
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                    Log::info("Old image deleted from public disk: " . $imagePath);
                } else {
                    Log::info("No old image to delete or path invalid/not found in public disk: " . $imagePath);
                }

                $base64Image = $request->input('image');
                $imageData = null;
                $extension = 'png'; // Default extension jika tidak dapat dideteksi

                // Periksa apakah string Base64 memiliki header data URI
                if (Str::startsWith($base64Image, 'data:')) {
                    // Pisahkan header data URI (misal: "data:image/png;base64,") dari data Base64
                    @list($type, $base64Image) = explode(';', $base64Image);
                    @list(, $base64Image) = explode(',', $base64Image);
                    
                    // Coba deteksi ekstensi dari tipe MIME
                    if (isset($type)) {
                        $mimeParts = explode('/', explode(':', $type)[1]);
                        if (isset($mimeParts[1])) {
                            $extension = $mimeParts[1];
                            // Handle svg+xml khusus
                            if ($extension === 'svg+xml') {
                                $extension = 'svg';
                            }
                        }
                    }
                }
                
                $imageData = base64_decode($base64Image);

                // Pastikan decoding berhasil dan data tidak kosong
                if ($imageData === false || empty($imageData)) {
                    Log::error("Failed to decode Base64 image data or decoded data is empty during update.");
                    return response()->json([
                        'status_code' => 400,
                        'message' => 'Invalid Base64 image data during update.',
                    ], 400);
                }

                $fileName = Str::random(40) . '.' . $extension;
                $newImagePath = 'portfolios/' . $fileName;

                // Simpan foto baru ke disk 'public'
                Storage::disk('public')->put($newImagePath, $imageData);
                Log::info("New image stored on public disk. Path: " . $newImagePath);
                $imagePath = $newImagePath; // Update path di DB dengan yang baru
            } else {
                Log::info("No new image data provided. Keeping existing image path.");
            }

            // Perbarui data deskripsi dan path gambar
            $updateData = [];
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }
            $updateData['image'] = $imagePath; // Selalu update image path

            $portfolio->update($updateData); // Gunakan array $updateData
            
            Log::info("Portfolio updated successfully in DB.");
            Log::info("Updated image path in DB: " . $portfolio->getRawOriginal('image'));
            Log::info("Updated description in DB: " . $portfolio->description);

            return response()->json([
                'status_code' => 200,
                'message' => 'Portfolio updated successfully',
                'data' => $portfolio,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Portfolio with ID " . $id . " not found or not owned by barber ID " . (Auth::id() ?? 'N/A') . ": " . $e->getMessage());
            return response()->json([
                'status_code' => 404,
                'message' => 'Portfolio not found or not owned by logged-in barber',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error in updating portfolio for ID " . $id . ": " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error updating portfolio: ' . $e->getMessage(),
            ], 500);
        } finally {
            Log::info("--- FINISHED PORTFOLIO UPDATE FOR ID: " . $id . " ---");
        }
    }

    // DELETE: Menghapus Portofolio
    public function destroy($id)
    {
        // Pastikan pengguna sudah login
        if (!Auth::check()) {
            Log::warning("Unauthorized attempt to delete portfolio ID: $id. User not logged in.");
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized: User not logged in',
            ], 401);
        }

        try {
            $barberId = Auth::id(); // Ambil ID barber yang sedang login
            $portfolio = Portofolios::where('id', $id)
                                    ->where('barber_id', $barberId) // Pastikan portofolio milik barber yang login
                                    ->firstOrFail();
            
            Log::info("Attempting to delete portfolio ID: " . $id . " for barber ID: " . $barberId);
            $imagePath = $portfolio->getRawOriginal('image'); // Ambil path asli dari DB

            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
                Log::info("Image " . $imagePath . " deleted from public disk.");
            } else {
                Log::warning("Attempted to delete image " . $imagePath . " but it was not found on public disk.");
            }
            $portfolio->delete();

            Log::info("Portfolio ID: " . $id . " deleted successfully by barber ID: " . $barberId);
            return response()->json([
                'status_code' => 200,
                'message' => 'Portfolio deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Portfolio with ID " . $id . " not found or not owned by barber ID " . (Auth::id() ?? 'N/A') . ": " . $e->getMessage());
            return response()->json([
                'status_code' => 404,
                'message' => 'Portfolio not found or not owned by logged-in barber',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting portfolio for ID " . $id . ": " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error deleting portfolio: ' . $e->getMessage(),
            ], 500);
        }
    }
}
