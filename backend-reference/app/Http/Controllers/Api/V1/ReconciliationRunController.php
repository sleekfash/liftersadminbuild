<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReconciliationRunRequest;
use App\Http\Resources\ReconciliationRunResource;
use App\Models\{MonthlyPeriod,ReconciliationRun};
use App\Services\ReconciliationService;
use App\Support\ScopesQueryByBranch;
use Illuminate\Http\{JsonResponse,Request};
class ReconciliationRunController extends Controller
{
    use ScopesQueryByBranch;
    public function __construct(private ReconciliationService $reconciliationService) {}
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReconciliationRun::class);
        $q=$this->scopeToBranchVia(ReconciliationRun::query()->with('items'),$request->user(),'items.relatedDisbursementRequest');
        return response()->json(['data'=>ReconciliationRunResource::collection($q->latest()->paginate($request->integer('per_page',15)))]);
    }
    public function store(ReconciliationRunRequest $request): JsonResponse
    {
        $this->authorize('create', ReconciliationRun::class);
        $run=$this->reconciliationService->run(MonthlyPeriod::findOrFail($request->integer('monthly_period_id')),$request->user());
        return response()->json(['message'=>'Reconciliation run completed.','data'=>new ReconciliationRunResource($run)],201);
    }
    public function show(ReconciliationRun $run): JsonResponse
    {
        $this->authorize('view',$run);
        return response()->json(['data'=>new ReconciliationRunResource($run->load('items'))]);
    }
}
