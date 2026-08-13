<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRowsRequest;
use App\Http\Resources\ImportRowResource;
use App\Models\{ImportRow,ImportSheetSnapshot};
use App\Services\WorkbookImportService;
use App\Support\ScopesQueryByBranch;
use Illuminate\Http\{JsonResponse,Request};
class ImportRowController extends Controller
{
    use ScopesQueryByBranch;
    public function __construct(private WorkbookImportService $importService) {}
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ImportRow::class);
        $query=$this->scopeToBranchVia(ImportRow::query()->latest(),$request->user(),'batch.uploader');
        if($request->filled('import_batch_id')) $query->where('import_batch_id',$request->integer('import_batch_id'));
        if($request->filled('status')) $query->where('status',$request->string('status'));
        return response()->json(['data'=>ImportRowResource::collection($query->paginate($request->integer('per_page',15)))]);
    }
    public function store(StoreImportRowsRequest $request, ImportSheetSnapshot $sheet): JsonResponse
    {
        $this->authorize('update',$sheet->batch);
        return response()->json(['message'=>'Import rows staged.','data'=>ImportRowResource::collection(collect($this->importService->addRows($sheet,$request->validated('rows'),$request->user())))],201);
    }
    public function skip(ImportRow $row): JsonResponse
    {
        $this->authorize('update',$row->batch);
        return response()->json(['message'=>'Import row skipped.','data'=>new ImportRowResource($this->importService->skipRow($row,request()->user()))]);
    }
}
