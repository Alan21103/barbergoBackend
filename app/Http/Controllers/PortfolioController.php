<?php

// app/Http/Controllers/PortfolioController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\Portofolios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Pastikan ini ada

class PortfolioController extends Controller
{
    // Hapus atau komen fungsi getFullImageUrl karena sekarang ditangani oleh Accessor di Model Portofolios

    // CREATE: Menambah Portofolio Baru
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string',
        ]);

        try {
            $imageFile = $request->file('image');
            // Simpan file ke disk 'public', di dalam folder 'portfolios'
            // Ini akan menyimpan 'portfolios/namafile.jpg' di DB
            $imagePath = $imageFile->store('portfolios', 'public');

            Log::info("--- STORE PORTFOLIO DEBUG ---");
            Log::info("Image stored path (DB value): " . $imagePath); // Ini akan menjadi 'portfolios/...'
            Log::info("--- END STORE PORTFOLIO DEBUG ---");

            $portfolio = Portofolios::create([
                'barber_id' => auth()->user()->id,
                'image' => $imagePath, // Path tanpa 'public/'
                'description' => $request->description,
            ]);

            // Karena Accessor sudah ada, saat $portfolio dikembalikan sebagai JSON,
            // atribut 'image' akan otomatis diubah menjadi URL absolut.
            return response()->json([
                'status_code' => 201,
                'message' => 'Portfolio created successfully',
                'data' => $portfolio, // Laravel akan otomatis mengonversi $portfolio menjadi array/JSON
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error creating portfolio: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Error creating portfolio: ' . $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE: Memperbarui Portofolio
    public function update(Request $request, $id)
    {
        Log::info("--- STARTING PORTFOLIO UPDATE FOR ID: " . $id . " ---");

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string',
        ]);

        try {
            $portfolio = Portofolios::findOrFail($id);
            Log::info("Portfolio found with ID: " . $portfolio->id);
            Log::info("Current image path in DB: " . $portfolio->image); // Ini akan memanggil accessor
            Log::info("Current description in DB: " . $portfolio->description);

            $imagePath = $portfolio->getRawOriginal('image'); // Ambil nilai asli dari DB sebelum accessor memprosesnya

            Log::info("Received description from request: " . ($request->description ?? 'null (not provided)'));

            if ($request->hasFile('image')) {
                Log::info("New image file detected in request.");

                // Pastikan path yang dihapus sudah bersih dari 'public/' di awal
                $oldImagePathCleaned = str_replace('public/', '', $imagePath); // Menggunakan path asli dari DB
                if ($oldImagePathCleaned && Storage::disk('public')->exists($oldImagePathCleaned)) {
                    Storage::disk('public')->delete($oldImagePathCleaned);
                    Log::info("Old image deleted from public disk: " . $oldImagePathCleaned);
                } else {
                    Log::info("No old image to delete or path invalid/not found in public disk: " . $oldImagePathCleaned);
                }

                // Simpan foto baru ke disk 'public', di folder 'portfolios'
                $newImagePath = $request->file('image')->store('portfolios', 'public'); // Menyimpan tanpa 'public/' di awal
                Log::info("New image stored on public disk. Path: " . $newImagePath);

                $imagePath = $newImagePath; // Update path di DB dengan yang baru dan bersih
            } else {
                Log::info("No new image file provided. Keeping existing image path.");
            }

            $portfolio->update([
                'image' => $imagePath,
                'description' => $request->description ?? $portfolio->description,
            ]);
            Log::info("Portfolio updated successfully in DB.");
            Log::info("Updated image path in DB: " . $portfolio->getRawOriginal('image')); // Log nilai asli DB
            Log::info("Updated description in DB: " . $portfolio->description);

            return response()->json([
                'status_code' => 200,
                'message' => 'Portfolio updated successfully',
                'data' => $portfolio, // Laravel akan otomatis mengonversi $portfolio menjadi array
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Portfolio with ID " . $id . " not found: " . $e->getMessage());
            return response()->json([
                'status_code' => 404,
                'message' => 'Portfolio not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error in updating portfolio for ID " . $id . ": " . $e->getMessage());
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
        try {
            $portfolio = Portofolios::findOrFail($id);
            // Ambil path asli dari DB sebelum accessor memprosesnya
            $imagePathCleaned = str_replace('public/', '', $portfolio->getRawOriginal('image'));

            if ($imagePathCleaned && Storage::disk('public')->exists($imagePathCleaned)) {
                Storage::disk('public')->delete($imagePathCleaned);
                Log::info("Image " . $imagePathCleaned . " deleted from public disk.");
            } else {
                Log::warning("Attempted to delete image " . $imagePathCleaned . " but it was not found on public disk.");
            }
            $portfolio->delete();

            return response()->json([
                'status_code' => 200,
                'message' => 'Portfolio deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error deleting portfolio: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Error deleting portfolio: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        // --- DIAGNOSA KRUSIAL INI ---
        $envAppUrl = env('APP_URL');
        $configAppUrl = config('app.url');
        $requestHost = request()->getHttpHost();

        Log::info("--- DEBUG APP_URL ---");
        Log::info("Value from .env (via env()): " . $envAppUrl);
        Log::info("Value from config('app.url'): " . $configAppUrl);
        Log::info("Request Host Header: " . $requestHost);
        Log::info("--- END DEBUG APP_URL ---");

        try {
            // Karena Accessor ada di model, saat Portofolios diambil,
            // atribut 'image' akan otomatis diubah menjadi URL absolut.
            $portfolios = Portofolios::with('barber')->get();

            Log::info('Successfully retrieved all portfolios with barber (User) data and generated public image URLs.');

            return response()->json([
                'status_code' => 200,
                'message' => 'Portfolios retrieved successfully with barber (User) data',
                'data' => $portfolios, // Laravel akan otomatis mengonversi Collection menjadi array JSON
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error retrieving portfolios with barber (User) data: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving portfolios with barber (User) data: ' . $e->getMessage(),
            ], 500);
        }
    }
}