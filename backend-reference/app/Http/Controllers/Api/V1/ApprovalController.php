<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Http\Requests\ApprovalRequest; use App\Http\Resources\DisbursementRequestResource; use App\Models\DisbursementRequest; use App\Services\ApprovalService; use Illuminate\Http\JsonResponse;
class ApprovalController extends Controller
{
    public function __construct(private ApprovalService $service) {}
    public function branchApprove(ApprovalRequest $r,DisbursementRequest $disbursement): JsonResponse{$this->authorize('branchApprove',$disbursement);return response()->json(['message'=>'Branch approval recorded.','data'=>new DisbursementRequestResource($this->service->branchApprove($disbursement,$r->validated(),$r->user()))]);}
    public function financeReview(ApprovalRequest $r,DisbursementRequest $disbursement): JsonResponse{$this->authorize('financeReview',$disbursement);return response()->json(['message'=>'Finance review recorded.','data'=>new DisbursementRequestResource($this->service->financeReview($disbursement,$r->validated(),$r->user()))]);}
    public function authorizeDisbursement(ApprovalRequest $r,DisbursementRequest $disbursement): JsonResponse{$this->authorize('authorize',$disbursement);return response()->json(['message'=>'Disbursement authorized.','data'=>new DisbursementRequestResource($this->service->authorize($disbursement,$r->validated(),$r->user()))]);}
}
