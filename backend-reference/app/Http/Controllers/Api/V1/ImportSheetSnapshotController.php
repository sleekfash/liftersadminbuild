<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportSheetRequest;
use App\Http\Resources\ImportSheetSnapshotResource;
use App\Models\ImportBatch;
use App\Models\ImportSheetSnapshot;
use App\Services\WorkbookImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImportSheetSnapshotController extends Controller
{
    public function __construct(private WorkbookImportService $importService) {}

    public function store(StoreImportSheetRequest $request, ImportBatch $batch): JsonResponse
    {
        $this->authorize('update', $batch);

        return response()->json([
            'message' => 'Import sheet snapshot captured.',
            'data' => new ImportSheetSnapshotResource($this->importService->addSheet($batch, $request->validated(), $request->user())),
        ], 201);
    }

    public function show(ImportBatch $batch, ImportSheetSnapshot $sheet): JsonResponse
    {
        // Nested ids must belong together, otherwise we would authorize against
        // the wrong parent batch.
        if ((int) $sheet->import_batch_id !== (int) $batch->id) {
            throw new NotFoundHttpException('Sheet does not belong to this import batch.');
        }

        $this->authorize('view', $sheet);

        return response()->json(['data' => new ImportSheetSnapshotResource($sheet)]);
    }
}
