<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectDocumentController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectDocument')) {
            return back();
        }

        $documents = ProjectDocument::with(['project', 'uploader'])
            ->when($request->project_id, function($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->when($request->document_type, function($query) use ($request) {
                return $query->where('document_type', $request->document_type);
            })
            ->get();

        $projects = Project::all();

        $data = [
            'documents' => $documents,
            'projects' => $projects
        ];
        return view('pages.projects.project_documents')->with($data);
    }

    public function upload(Request $request) {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'project_id' => 'required|exists:projects,id',
            'document_type' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('project_documents', $fileName, 'public');

        $document = ProjectDocument::create([
            'project_id' => $request->project_id,
            'uploaded_by' => auth()->id(),
            'document_type' => $request->document_type,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $request->description,
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'document' => $document
        ]);
    }

    public function download($id) {
        $document = ProjectDocument::findOrFail($id);

        if(!Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'File not found');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name
        );
    }

    public function archive($id) {
        $document = ProjectDocument::findOrFail($id);

        $document->update(['status' => 'archived']);

        return response()->json([
            'success' => true,
            'document' => $document
        ]);
    }

    public function getProjectDocuments($projectId, $type = null) {
        $documents = ProjectDocument::where('project_id', $projectId)
            ->when($type, function($query) use ($type) {
                return $query->where('document_type', $type);
            })
            ->where('status', 'active')
            ->get();

        return response()->json($documents);
    }

    public function bulkUpload(Request $request) {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'project_id' => 'required|exists:projects,id',
            'document_type' => 'required|string'
        ]);

        $uploadedDocuments = [];

        foreach($request->file('files') as $file) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('project_documents', $fileName, 'public');

            $document = ProjectDocument::create([
                'project_id' => $request->project_id,
                'uploaded_by' => auth()->id(),
                'document_type' => $request->document_type,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'status' => 'active'
            ]);

            $uploadedDocuments[] = $document;
        }

        return response()->json([
            'success' => true,
            'documents' => $uploadedDocuments
        ]);
    }
}
