<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Http\Requests\MarkDisbursementPaidRequest; use App\Http\Resources\TreasuryTransactionResource; use App\Models\DisbursementRequest; use App\Services\TreasuryService; use Illuminate\Http\JsonResponse;
class TreasuryPostingController extends Controller { public function __construct(private TreasuryService $service){} public function markPaid(MarkDisbursementPaidRequest $r,DisbursementRequest $disbursement): JsonResponse{$this->authorize('markPaid',$disbursement);$tx=$this->service->postDisbursementPaid($disbursement,$r->validated(),$r->user());return response()->json(['message'=>'Disbursement marked as paid and treasury transaction posted.','data'=>new TreasuryTransactionResource($tx)],201);} }
