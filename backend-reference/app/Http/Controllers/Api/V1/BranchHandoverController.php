<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DisputeBranchHandoverRequest;
use App\Http\Requests\SignBranchHandoverRequest;
use App\Http\Requests\StoreBranchHandoverRequest;
use App\Http\Resources\BranchHandoverResource;
use App\Models\Branch;
use App\Models\BranchHandover;
use App\Services\BranchHandoverService;
use App\Support\ScopesQueryByBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BranchHandoverController extends Controller
{
    use ScopesQueryByBranch;

    public function __construct(private BranchHandoverService $service) {}

    public function index(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('viewAny', BranchHandover::class);

        $query = $this->scopeToBranch($branch->handovers()->getQuery(), $request->user());

        return response()->json([
            'data' => BranchHandoverResource::collection(
                $query->latest()->paginate($request->integer('per_page', 15))
            ),
        ]);
    }

    public function store(StoreBranchHandoverRequest $request, Branch $branch): JsonResponse
    {
        $this->authorize('create', BranchHandover::class);

        return response()->json([
            'message' => 'Branch handover opened with a frozen portfolio snapshot.',
            'data' => new BranchHandoverResource($this->service->create($branch, $request->validated(), $request->user())),
        ], 201);
    }

    public function show(Branch $branch, BranchHandover $handover): JsonResponse
    {
        $this->assertBelongsToBranch($branch, $handover);
        $this->authorize('view', $handover);

        return response()->json(['data' => new BranchHandoverResource($handover)]);
    }

    public function signOutgoing(SignBranchHandoverRequest $request, Branch $branch, BranchHandover $handover): JsonResponse
    {
        $this->assertBelongsToBranch($branch, $handover);
        $this->authorize('signOutgoing', $handover);

        return response()->json([
            'message' => 'Outgoing officer sign-off recorded.',
            'data' => new BranchHandoverResource($this->service->signOutgoing($handover, $request->input('notes'), $request->user())),
        ]);
    }

    public function acknowledge(SignBranchHandoverRequest $request, Branch $branch, BranchHandover $handover): JsonResponse
    {
        $this->assertBelongsToBranch($branch, $handover);
        $this->authorize('acknowledge', $handover);

        return response()->json([
            'message' => 'Incoming officer consent recorded.',
            'data' => new BranchHandoverResource($this->service->acknowledgeIncoming($handover, $request->input('notes'), $request->user())),
        ]);
    }

    public function dispute(DisputeBranchHandoverRequest $request, Branch $branch, BranchHandover $handover): JsonResponse
    {
        $this->assertBelongsToBranch($branch, $handover);
        $this->authorize('dispute', $handover);

        return response()->json([
            'message' => 'Handover marked as disputed.',
            'data' => new BranchHandoverResource($this->service->dispute($handover, $request->string('reason'), $request->user())),
        ]);
    }

    public function approve(Request $request, Branch $branch, BranchHandover $handover): JsonResponse
    {
        $this->assertBelongsToBranch($branch, $handover);
        $this->authorize('approve', $handover);

        return response()->json([
            'message' => 'Handover approved and completed.',
            'data' => new BranchHandoverResource($this->service->approve($handover, $request->user())),
        ]);
    }

    public function report(Branch $branch, BranchHandover $handover): JsonResponse
    {
        $this->assertBelongsToBranch($branch, $handover);
        $this->authorize('view', $handover);

        return response()->json(['data' => $this->service->report($handover)]);
    }

    /** Nested ids must actually belong together — never authorize against the wrong parent. */
    private function assertBelongsToBranch(Branch $branch, BranchHandover $handover): void
    {
        if ((int) $handover->branch_id !== (int) $branch->id) {
            throw new NotFoundHttpException('Handover does not belong to this branch.');
        }
    }
}
