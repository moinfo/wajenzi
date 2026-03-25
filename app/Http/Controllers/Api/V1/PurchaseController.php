<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Purchase::with(['supplier', 'project', 'purchaseItems'])
            ->orderBy('created_at', 'desc');

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $purchases = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => PurchaseResource::collection($purchases),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $purchase = Purchase::with(['supplier', 'project', 'purchaseItems.material', 'delivery'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new PurchaseResource($purchase),
        ]);
    }
}
