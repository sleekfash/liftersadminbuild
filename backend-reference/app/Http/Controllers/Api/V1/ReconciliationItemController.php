<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveReconciliationItemRequest;
use App\Http\Resources\ReconciliationItemResource;
use App\Models\ReconciliationItem;
use App\Services\ReconciliationService;
use Illuminate\Http\{JsonResponse,Request};
class ReconciliationItemController extends Controller
{
    public function __construct(private ReconciliationService $reconciliationService) {}
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReconciliationItem::class);
        return response()->json(['data'=>ReconciliationItemResource::collection(ReconciliationItem::query()->latest()->paginate($request->integer('per_page',15)))]);
    }
    public function underReview(Request $request, ReconciliationItem $item): JsonResponse
    {
        $this->authorize('update',$item);
        return response()->json(['message'=>'Item marked under review.','data'=>new ReconciliationItemResource($this->reconciliationService->markUnderReview($item,$request->user()))]);
    }
    public function resolve(ResolveReconciliationItemRequest $request, ReconciliationItem $item): JsonResponse
    {
        $this->authorize('update',$item);
        return response()->json(['message'=>'Item resolved.','data'=>new ReconciliationItemResource($this->reconciliationService->resolve($item,$request->validated(),$request->user()))]);
    }
    public function override(ResolveReconciliationItemRequest $request, ReconciliationItem $item): JsonResponse
    {
        $this->authorize('override',$item);
        return response()->json(['message'=>'Item overridden.','data'=>new ReconciliationItemResource($this->reconciliationService->override($item,$request->validated(),$request->user()))]);
    }
}
