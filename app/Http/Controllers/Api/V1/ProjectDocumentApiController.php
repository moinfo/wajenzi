<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectDocumentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectDocument::with(['project:id,project_name', 'uploader:id,name'])
                ->when($request->project_id, function ($query) use ($request) {
                    return $query->where('project_id', $request->project_id);
                })
                ->when($request->document_type, function ($query) use ($request) {
                    return $query->where('document_type', $request->document_type);
                })
                ->when($request->status, function ($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->latest();

            $documents = $query->get();

            return response()->json([
                'success' => true,
                'data' => $documents->map(fn ($document) => $this->transformDocument($document)),
                'meta' => [
                    'total' => $documents->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectDocument index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project documents: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $document = ProjectDocument::with(['project:id,project_name', 'uploader:id,name'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->transformDocument($document),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectDocument show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project document: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function transformDocument(ProjectDocument $document): array
    {
        $publicUrl = Storage::disk('public')->url($document->file_path);

        return [
            'id' => $document->id,
            'project_id' => $document->project_id,
            'project_name' => $document->project?->project_name,
            'uploaded_by' => $document->uploaded_by,
            'uploaded_by_name' => $document->uploader?->name,
            'document_type' => $document->document_type,
            'file_name' => $document->file_name,
            'file_path' => $document->file_path,
            'file_url' => url($publicUrl),
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'description' => $document->description,
            'status' => $document->status,
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
        ];
    }
}
