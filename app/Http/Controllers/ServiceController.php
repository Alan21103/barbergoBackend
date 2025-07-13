<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class ServiceController extends Controller
{
    // Create: Menambahkan layanan baru (untuk barber yang sedang login)
    public function store(StoreServiceRequest $request) // Menggunakan StoreServiceRequest untuk validasi
    {
        try {
            // Pastikan pengguna sudah login
            if (!Auth::check()) {
                Log::warning("Unauthorized attempt to store service: User not logged in.");
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in',
                ], 401);
            }

            $barberId = Auth::id(); // Ambil ID barber yang sedang login
            Log::info('Service request received from barber ID:', ['barber_id' => $barberId, 'request_data' => $request->all()]);

            // Simpan layanan baru ke database dengan barber_id dari pengguna yang login
            $service = new Service();
            $service->barber_id = $barberId; // Tetapkan barber_id
            $service->name = $request->name;
            $service->price = $request->price;
            $service->description = $request->description;
            $service->save();

            Log::info('Service created successfully', ['service' => $service]);

            return response()->json([
                'status_code' => 201,
                'message' => 'Service created successfully',
                'data' => $service,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Service creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'user_id' => Auth::id(), // Log user ID if available
                'exception' => $e,
            ]);

            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Read: Menampilkan daftar layanan (semua layanan, bisa diakses publik atau admin)
    public function index()
    {
        try {
            $services = Service::all();
            if ($services->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No services found',
                    'data' => [],
                ], 404);
            }
            return response()->json([
                'status_code' => 200,
                'message' => 'Services retrieved successfully',
                'data' => $services,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving all services: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Read: Menampilkan detail layanan berdasarkan ID
    public function show($id)
    {
        try {
            $service = Service::findOrFail($id);

            return response()->json([
                'status_code' => 200,
                'message' => 'Service retrieved successfully',
                'data' => $service,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Service not found for ID: $id", ['exception' => $e]);
            return response()->json([
                'status_code' => 404,
                'message' => 'Service not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving service by ID (' . $id . '): ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Read: Menampilkan layanan milik barber yang sedang login
    public function getMyServices()
    {
        try {
            if (!Auth::check()) {
                Log::warning("Unauthorized attempt to getMyServices: User not logged in.");
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in',
                ], 401);
            }

            $barberId = Auth::id();
            Log::info("Attempting to retrieve services for barber ID: $barberId");

            $services = Service::where('barber_id', $barberId)->get();

            if ($services->isEmpty()) {
                Log::info("No services found for logged-in barber ID: $barberId");
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No services found for the logged-in barber',
                    'data' => [],
                ], 404);
            }

            Log::info("Services for logged-in barber ID: $barberId retrieved successfully. Count: {$services->count()}");
            return response()->json([
                'status_code' => 200,
                'message' => 'Services for logged-in barber retrieved successfully',
                'data' => $services,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving services for logged-in barber: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error retrieving services: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Update: Memperbarui layanan yang sudah ada (hanya milik barber yang login)
    public function update(StoreServiceRequest $request, $id)
    {
        try {
            if (!Auth::check()) {
                Log::warning("Unauthorized attempt to update service ID: $id. User not logged in.");
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in',
                ], 401);
            }

            $barberId = Auth::id();
            Log::info("Attempting to update service ID: $id for barber ID: $barberId");

            // Mencari layanan berdasarkan ID dan memastikan itu milik barber yang login
            $service = Service::where('id', $id)
                              ->where('barber_id', $barberId)
                              ->firstOrFail();

            $service->update([
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
            ]);

            Log::info("Service ID: {$service->id} updated successfully by barber ID: $barberId");
            return response()->json([
                'status_code' => 200,
                'message' => 'Service updated successfully',
                'data' => $service,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Service ID: $id not found or not owned by barber ID: " . (Auth::id() ?? 'N/A'), ['exception' => $e]);
            return response()->json([
                'status_code' => 404,
                'message' => 'Service not found or not owned by logged-in barber',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating service ID: ' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Delete: Menghapus layanan berdasarkan ID (hanya milik barber yang login)
    public function destroy($id)
    {
        try {
            if (!Auth::check()) {
                Log::warning("Unauthorized attempt to delete service ID: $id. User not logged in.");
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthorized: User not logged in',
                ], 401);
            }

            $barberId = Auth::id();
            Log::info("Attempting to delete service ID: $id for barber ID: $barberId");

            // Mencari layanan berdasarkan ID dan memastikan itu milik barber yang login
            $service = Service::where('id', $id)
                              ->where('barber_id', $barberId)
                              ->firstOrFail();

            $service->delete();

            Log::info("Service ID: {$id} deleted successfully by barber ID: $barberId");
            return response()->json([
                'status_code' => 200,
                'message' => 'Service deleted successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Service ID: $id not found or not owned by barber ID: " . (Auth::id() ?? 'N/A'), ['exception' => $e]);
            return response()->json([
                'status_code' => 404,
                'message' => 'Service not found or not owned by logged-in barber',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting service ID: ' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
