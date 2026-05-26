<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Http\Requests\StatusReasonRequest; use App\Http\Resources\MemberDetailResource; use App\Models\Member; use App\Services\MemberService; use Illuminate\Http\JsonResponse;
class MemberLifecycleController extends Controller
{
    public function __construct(private MemberService $memberService) {}
    public function verify(StatusReasonRequest $r,Member $m): JsonResponse{$this->authorize('verify',$m);return response()->json(['message'=>'Member verified.','data'=>new MemberDetailResource($this->memberService->verify($m,$r->validated(),$r->user()))]);}
    public function activate(StatusReasonRequest $r,Member $m): JsonResponse{$this->authorize('activate',$m);return response()->json(['message'=>'Member activated.','data'=>new MemberDetailResource($this->memberService->activate($m,$r->validated(),$r->user()))]);}
    public function suspend(StatusReasonRequest $r,Member $m): JsonResponse{$this->authorize('suspend',$m);return response()->json(['message'=>'Member suspended.','data'=>new MemberDetailResource($this->memberService->suspend($m,$r->validated(),$r->user()))]);}
    public function reactivate(StatusReasonRequest $r,Member $m): JsonResponse{$this->authorize('reactivate',$m);return response()->json(['message'=>'Member reactivated.','data'=>new MemberDetailResource($this->memberService->reactivate($m,$r->validated(),$r->user()))]);}
    public function terminate(StatusReasonRequest $r,Member $m): JsonResponse{$this->authorize('terminate',$m);return response()->json(['message'=>'Member terminated.','data'=>new MemberDetailResource($this->memberService->terminate($m,$r->validated(),$r->user()))]);}
}
