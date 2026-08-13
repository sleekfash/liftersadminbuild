<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostBranchManagerRequest;
use App\Http\Resources\BranchManagerAssignmentResource;
use App\Models\Branch;
use App\Models\BranchHandover;
use App\Models\BranchManagerAssignment;
use App\Models\User;
use App\Services\BranchAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchManagerController extends Controller
{
    public function __construct(private BranchAssignmentService $service) {}

    /** Posting history for a branch (officers only — never the branch portfolio). */
    public function index(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('viewAny', BranchManagerAssignment::class);

        $assignments = $branch->managerAssignments()
            ->orderByDesc('started_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json(['data' => BranchManagerAssignmentResource::collection($assignments)]);
    }

    /** Post (or replace) the branch manager. */
    public function store(PostBranchManagerRequest $request, Branch $branch): JsonResponse
    {
        $this->authorize('post', BranchManagerAssignment::class);

        $manager = User::findOrFail($request->integer('user_id'));
        $handover = $request->filled('branch_handover_id')
            ? BranchHandover::findOrFail($request->integer('branch_handover_id'))
            : null;

        $assignment = $this->service->postManager(
            $branch,
            $manager,
            $request->user(),
            $handover,
            $request->boolean('override_handover'),
            $request->input('reason')
        );

        return response()->json([
            'message' => 'Branch manager posted. Branch records were not moved.',
            'data' => new BranchManagerAssignmentResource($assignment),
        ], 201);
    }

    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('unassign', BranchManagerAssignment::class);

        $ended = $this->service->unassignManager($branch, $request->user(), $request->input('reason'));

        return response()->json([
            'message' => $ended ? 'Branch manager posting ended.' : 'Branch had no active manager posting.',
            'data' => $ended ? new BranchManagerAssignmentResource($ended) : null,
        ]);
    }
}
