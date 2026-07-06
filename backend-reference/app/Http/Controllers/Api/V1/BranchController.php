<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Models\Branch; use Illuminate\Http\{JsonResponse,Request};
class BranchController extends Controller { public function index(Request $request): JsonResponse { $this->authorize('viewAny', Branch::class); return response()->json(['data'=>[]]); } }
