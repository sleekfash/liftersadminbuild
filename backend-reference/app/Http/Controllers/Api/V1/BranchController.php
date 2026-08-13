<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Support\ScopesQueryByBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use ScopesQueryByBranch;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        // Branch staff only ever see their own branch record.
        $query = $this->scopeToBranch(Branch::query()->with('currentManager:id,name,email'), $request->user(), 'id');

        return response()->json([
            'data' => BranchResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 15))),
        ]);
    }

    public function show(Branch $branch): JsonResponse
    {
        $this->authorize('view', $branch);

        return response()->json(['data' => new BranchResource($branch->load('currentManager:id,name,email'))]);
    }
}
