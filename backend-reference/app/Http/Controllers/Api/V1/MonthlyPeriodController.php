<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMonthlyPeriodRequest;
use App\Http\Resources\MonthlyPeriodResource;
use App\Models\MonthlyPeriod;
use App\Services\PeriodService;
use Illuminate\Http\{JsonResponse,Request};
class MonthlyPeriodController extends Controller
{
    public function __construct(private PeriodService $periodService) {}
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonthlyPeriod::class);
        return response()->json(['data'=>MonthlyPeriodResource::collection(MonthlyPeriod::query()->latest()->paginate($request->integer('per_page',15)))]);
    }
    public function store(StoreMonthlyPeriodRequest $request): JsonResponse
    {
        $this->authorize('create', MonthlyPeriod::class);
        $period=$this->periodService->create($request->validated(),$request->user());
        return response()->json(['message'=>'Monthly period created.','data'=>new MonthlyPeriodResource($period)],201);
    }
    public function show(MonthlyPeriod $period): JsonResponse
    {
        $this->authorize('view',$period);
        return response()->json(['data'=>new MonthlyPeriodResource($period)]);
    }
}
