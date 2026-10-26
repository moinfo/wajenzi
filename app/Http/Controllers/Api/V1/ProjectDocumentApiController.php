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

    /**
     * POST /project-documents (multipart). Same rules as the web's
     * ProjectDocumentController::upload; gated like the web's "New Document" button.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->can('Add Document')) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'project_id' => 'required|exists:projects,id',
            'document_type' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('project_documents', $fileName, 'public');

        $document = ProjectDocument::create([
            'project_id' => $validated['project_id'],
            'uploaded_by' => $request->user()->id,
            'document_type' => $validated['document_type'],
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $validated['description'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transformDocument($document->load(['project:id,project_name', 'uploader:id,name'])),
        ], 201);
    }

    /**
     * POST /project-documents/{id} (multipart, file optional). Gated like the
     * web's "Edit Document" button.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Edit Document')) {
            return $this->forbidden();
        }

        $document = ProjectDocument::findOrFail($id);

        $validated = $request->validate([
            'file' => 'nullable|file|max:10240',
            'project_id' => 'sometimes|required|exists:projects,id',
            'document_type' => 'sometimes|required|string',
            'description' => 'nullable|string',
        ]);

        $changes = array_intersect_key($validated, array_flip(['project_id', 'document_type', 'description']));

        if ($file = $request->file('file')) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('project_documents', $fileName, 'public');
            Storage::disk('public')->delete($document->file_path);
            $changes += [
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];
        }

        $document->update($changes);

        return response()->json([
            'success' => true,
            'data' => $this->transformDocument($document->fresh(['project:id,project_name', 'uploader:id,name'])),
        ]);
    }

    /**
     * DELETE /project-documents/{id}. Gated like the web's "Delete Document" button.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Delete Document')) {
            return $this->forbidden();
        }

        $document = ProjectDocument::findOrFail($id);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['success' => true, 'message' => 'Document deleted.']);
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
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
