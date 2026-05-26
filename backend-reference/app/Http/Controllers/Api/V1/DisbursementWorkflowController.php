<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Http\Resources\DisbursementRequestResource; use App\Models\DisbursementRequest; use App\Services\DisbursementService; use Illuminate\Http\{JsonResponse,Request};
class DisbursementWorkflowController extends Controller { public function __construct(private DisbursementService $service){} public function submit(Request $r,DisbursementRequest $disbursement): JsonResponse{$this->authorize('submit',$disbursement);$d=$this->service->submit($disbursement,$r->user());return response()->json(['message'=>'Disbursement submitted.','data'=>new DisbursementRequestResource($d)]);} }
