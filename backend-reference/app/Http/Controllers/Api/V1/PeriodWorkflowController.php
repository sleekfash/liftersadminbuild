<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\PeriodActionRequest;
use App\Http\Resources\MonthlyPeriodResource;
use App\Models\MonthlyPeriod;
use App\Services\PeriodService;
use Illuminate\Http\JsonResponse;
class PeriodWorkflowController extends Controller
{
    public function __construct(private PeriodService $periodService) {}
    public function review(PeriodActionRequest $request, MonthlyPeriod $period): JsonResponse
    {
        $this->authorize('review',$period);
        return response()->json(['message'=>'Period moved to review.','data'=>new MonthlyPeriodResource($this->periodService->review($period,$request->validated(),$request->user()))]);
    }
    public function close(PeriodActionRequest $request, MonthlyPeriod $period): JsonResponse
    {
        $this->authorize('close',$period);
        return response()->json(['message'=>'Period closed.','data'=>new MonthlyPeriodResource($this->periodService->close($period,$request->validated(),$request->user()))]);
    }
    public function lock(PeriodActionRequest $request, MonthlyPeriod $period): JsonResponse
    {
        $this->authorize('lock',$period);
        return response()->json(['message'=>'Period locked.','data'=>new MonthlyPeriodResource($this->periodService->lock($period,$request->validated(),$request->user()))]);
    }
    public function reopen(PeriodActionRequest $request, MonthlyPeriod $period): JsonResponse
    {
        $this->authorize('reopen',$period);
        return response()->json(['message'=>'Period reopened.','data'=>new MonthlyPeriodResource($this->periodService->reopen($period,$request->validated(),$request->user()))]);
    }
}
