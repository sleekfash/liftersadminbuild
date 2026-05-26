<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportSheetRequest;
use App\Http\Resources\ImportSheetSnapshotResource;
use App\Models\ImportBatch;
use App\Services\WorkbookImportService;
use Illuminate\Http\JsonResponse;
class ImportSheetSnapshotController extends Controller
{
    public function __construct(private WorkbookImportService $importService) {}
    public function store(StoreImportSheetRequest $request, ImportBatch $batch): JsonResponse
    {
        $this->authorize('update',$batch);
        return response()->json(['message'=>'Import sheet snapshot captured.','data'=>new ImportSheetSnapshotResource($this->importService->addSheet($batch,$request->validated(),$request->user()))],201);
    }
}
