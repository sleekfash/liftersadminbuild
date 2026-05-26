<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Http\Requests\{StoreMemberRequest,UpdateMemberRequest}; use App\Http\Resources\{MemberResource,MemberDetailResource}; use App\Models\Member; use App\Services\MemberService; use Illuminate\Http\{JsonResponse,Request};
class MemberController extends Controller
{
    public function __construct(private MemberService $memberService) {}
    public function index(Request $request): JsonResponse { $this->authorize('viewAny',Member::class); return response()->json(['data'=>MemberResource::collection(Member::query()->latest()->paginate($request->integer('per_page',15)))]); }
    public function store(StoreMemberRequest $request): JsonResponse { $this->authorize('create',Member::class); $m=$this->memberService->create($request->validated(),$request->user()); return response()->json(['message'=>'Member created successfully.','data'=>new MemberDetailResource($m)],201); }
    public function show(Member $member): JsonResponse { $this->authorize('view',$member); return response()->json(['data'=>new MemberDetailResource($member->load('statusHistories'))]); }
    public function update(UpdateMemberRequest $request, Member $member): JsonResponse { $this->authorize('update',$member); $m=$this->memberService->update($member,$request->validated(),$request->user()); return response()->json(['message'=>'Member updated successfully.','data'=>new MemberDetailResource($m)]); }
}
