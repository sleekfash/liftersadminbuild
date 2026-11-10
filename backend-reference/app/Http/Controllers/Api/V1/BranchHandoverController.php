<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DisputeBranchHandoverRequest;
use App\Http\Requests\SignBranchHandoverRequest;
use App\Http\Requests\StoreBranchHandoverRequest;
use App\Http\Resources\BranchHandoverResource;
use App\Models\Branch;
use App\Models\BranchHandover;
use App\Models\User;
use App\Services\BranchHandoverService;
use App\Support\ScopesQueryByBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BranchHandoverController extends Controller
{
    use ScopesQueryByBranch;

    public function __construct(private BranchHandoverService $service) {}

    /** Lists branch records plus cross-branch handovers where the caller is a named party. */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = BranchHandover::query()->with(['branch', 'outgoing', 'incoming', 'approver', 'auditLogs.actor']);
        if (!$this->userIsCrossBranch($user)) {
            $branchId = (int) ($user->branch_id ?? 0);
            $query->where(function ($builder) use ($branchId, $user) {
                if ($branchId) $builder->where('branch_id', $branchId);
                $builder->orWhere('outgoing_user_id', $user->id)->orWhere('incoming_user_id', $user->id);
            });
        }
        return response()->json(['data' => BranchHandoverResource::collection($query->latest()->paginate($request->integer('per_page', 100)))]);
    }

    /** Exposes only the minimal directory fields needed to select an active successor. */
    public function candidates(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole([
            \App\Enums\RoleCode::SUPER_ADMIN->value,
            \App\Enums\RoleCode::SUB_ADMIN->value,
            \App\Enums\RoleCode::BRANCH_MANAGER->value,
        ]), 403);
        $candidates = User::query()->where('is_active', true)->whereHas('roles', fn ($query) => $query->where('code', \App\Enums\RoleCode::BRANCH_MANAGER->value))
            ->with('branch:id,name,code')->orderBy('name')->get()
            ->map(fn (User $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'branch' => $candidate->branch ? ['id' => $candidate->branch->id, 'name' => $candidate->branch->name, 'code' => $candidate->branch->code] : null,
                'roles' => [\App\Enums\RoleCode::BRANCH_MANAGER->value],
            ]);
        return response()->json(['data' => $candidates]);
    }

    public function index(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('viewAny', BranchHandover::class);

        $user = $request->user();
        $query = $branch->handovers()->getQuery();
        if (!$this->userIsCrossBranch($user)) {
            $branchId = (int) ($user->branch_id ?? 0);
            $query->where(function ($builder) use ($branchId, $user) {
                $builder->where('branch_id', $branchId)
                    ->orWhere('outgoing_user_id', $user->id)
                    ->orWhere('incoming_user_id', $user->id);
            });
        }

        return response()->json([
            'data' => BranchHandoverResource::collection(
                $query->with(['branch', 'outgoing', 'incoming', 'approver', 'auditLogs.actor'])->latest()->paginate($request->integer('per_page', 100))
            ),
        ]);
    }

    public function store(StoreBranchHandoverRequest $request, Branch $branch): JsonResponse
    {
        $this->authorize('create', [BranchHandover::class, $branch]);

        $data = $request->validated();
        if (!$request->user()->hasAnyRole([
            \App\Enums\RoleCode::SUPER_ADMIN->value,
            \App\Enums\RoleCode::SUB_ADMIN->value,
        ])) {
            $data['outgoing_user_id'] = $request->user()->id;
        }

        return response()->json([
            'message' => 'Branch handover opened with a frozen portfolio snapshot.',
            'data' => new BranchHandoverResource($this->service->create($branch, $data, $request->user())),
        ], 201);
    }

    public function show(Branch $branch, BranchHandover $handover): JsonResponse
    {
        $this->assertBelongsToBranch($branch, $handover);
        $this->authorize('view', $handover);

        $handover->load(['branch', 'outgoing', 'incoming', 'approver', 'auditLogs.actor']);
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
