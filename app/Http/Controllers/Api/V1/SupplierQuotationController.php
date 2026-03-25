<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierQuotationResource;
use App\Models\SupplierQuotation;
use App\Models\ProjectMaterialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierQuotationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SupplierQuotation::with(['supplier', 'materialRequest.project'])
            ->orderBy('created_at', 'desc');

        if ($request->material_request_id) {
            $query->where('material_request_id', $request->material_request_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $quotations = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => SupplierQuotationResource::collection($quotations),
            'meta' => [
                'current_page' => $quotations->currentPage(),
                'last_page' => $quotations->lastPage(),
                'per_page' => $quotations->perPage(),
                'total' => $quotations->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $quotation = SupplierQuotation::with(['supplier', 'materialRequest.project', 'items'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new SupplierQuotationResource($quotation),
        ]);
    }
}
