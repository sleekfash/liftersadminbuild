<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Http\Requests\StoreDisbursementRequest; use App\Http\Resources\DisbursementRequestResource; use App\Models\DisbursementRequest; use App\Services\DisbursementService; use Illuminate\Http\{JsonResponse,Request};
class DisbursementController extends Controller
{
    public function __construct(private DisbursementService $service) {}
    public function index(Request $r): JsonResponse{$this->authorize('viewAny',DisbursementRequest::class);return response()->json(['data'=>DisbursementRequestResource::collection(DisbursementRequest::with(['member','approvals'])->latest()->paginate($r->integer('per_page',15)))]);}
    public function store(StoreDisbursementRequest $r): JsonResponse{$this->authorize('create',DisbursementRequest::class);$d=$this->service->create($r->validated(),$r->user());return response()->json(['message'=>'Disbursement draft created successfully.','data'=>new DisbursementRequestResource($d)],201);}
    public function show(DisbursementRequest $disbursement): JsonResponse{$this->authorize('view',$disbursement);return response()->json(['data'=>new DisbursementRequestResource($disbursement->load(['member','approvals','statusHistories','treasuryTransactions']))]);}
}
