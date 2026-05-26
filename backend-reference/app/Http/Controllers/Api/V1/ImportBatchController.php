<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\{PostTrialBalanceImportRequest,StoreImportBatchRequest};
use App\Http\Resources\ImportBatchResource;
use App\Models\ImportBatch;
use App\Services\WorkbookImportService;
use Illuminate\Http\{JsonResponse,Request};
class ImportBatchController extends Controller
{
    public function __construct(private WorkbookImportService $importService) {}
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ImportBatch::class);
        return response()->json(['data'=>ImportBatchResource::collection(ImportBatch::query()->withCount('rows')->latest()->paginate($request->integer('per_page',15)))]);
    }
    public function store(StoreImportBatchRequest $request): JsonResponse
    {
        $this->authorize('create', ImportBatch::class);
        return response()->json(['message'=>'Import batch created.','data'=>new ImportBatchResource($this->importService->createBatch($request->validated(),$request->user()))],201);
    }
    public function show(ImportBatch $batch): JsonResponse
    {
        $this->authorize('view',$batch);
        return response()->json(['data'=>new ImportBatchResource($batch->load('sheets','rows'))]);
    }
    public function map(ImportBatch $batch): JsonResponse
    {
        $this->authorize('update',$batch);
        return response()->json(['message'=>'Import rows mapped.','data'=>new ImportBatchResource($this->importService->mapRows($batch,request()->user()))]);
    }
    public function validateRows(ImportBatch $batch): JsonResponse
    {
        $this->authorize('update',$batch);
        return response()->json(['message'=>'Import rows validated.','data'=>new ImportBatchResource($this->importService->validateRows($batch,request()->user()))]);
    }
    public function postTrialBalance(PostTrialBalanceImportRequest $request, ImportBatch $batch): JsonResponse
    {
        $this->authorize('post',$batch);
        return response()->json(['message'=>'Trial balance rows posted.','data'=>new ImportBatchResource($this->importService->postTrialBalance($batch,$request->validated(),$request->user()))]);
    }
}
