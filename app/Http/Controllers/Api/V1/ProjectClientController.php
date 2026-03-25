<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectClientResource;
use App\Models\ProjectClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectClient::with(['client_source', 'user', 'projects'])
                ->orderBy('created_at', 'desc');

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $clients = $query->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => ProjectClientResource::collection($clients),
                    'meta' => [
                        'current_page' => $clients->currentPage(),
                        'last_page' => $clients->lastPage(),
                        'per_page' => $clients->perPage(),
                        'total' => $clients->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient index error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch clients: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $client = ProjectClient::with(['client_source', 'user', 'projects', 'documents'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new ProjectClientResource($client),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient show error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'nullable|email|max:255',
                'phone_number' => 'required|string|max:20',
                'address' => 'nullable|string|max:500',
                'identification_number' => 'nullable|string|max:50',
                'client_source_id' => 'nullable|exists:client_sources,id',
            ]);

            $validated['create_by_id'] = $request->user()->id;
            $validated['status'] = 'PENDING';

            $client = ProjectClient::create($validated);
            $client->load(['client_source', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully.',
                'data' => new ProjectClientResource($client),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ProjectClient store error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:100',
                'last_name' => 'sometimes|string|max:100',
                'email' => 'nullable|email|max:255',
                'phone_number' => 'sometimes|string|max:20',
                'address' => 'nullable|string|max:500',
                'identification_number' => 'nullable|string|max:50',
                'client_source_id' => 'nullable|exists:client_sources,id',
            ]);

            $client->update($validated);
            $client->load(['client_source', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully.',
                'data' => new ProjectClientResource($client),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ProjectClient update error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient destroy error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client: ' . $e->getMessage(),
            ], 500);
        }
    }
}
