<?php
namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreUserRequest,UpdateUserRequest};
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\{JsonResponse,Request};
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $query = User::query()->with(['branch', 'roles'])->latest();
        if ($request->filled('search')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%'));
        return response()->json(['data' => UserResource::collection($query->paginate($request->integer('per_page', 30)))]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $user = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $roles = $data['roles'];
            unset($data['roles']);
            $user = User::create($data);
            $user->roles()->sync(Role::query()->whereIn('code', $roles)->pluck('id'));
            return $user->load(['branch', 'roles']);
        });
        return response()->json(['message' => 'User created.', 'data' => new UserResource($user)], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $updated = DB::transaction(function () use ($request, $user) {
            $data = $request->validated();
            $isAdmin = $request->user()?->hasAnyRole([RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value]) ?? false;
            if (! $isAdmin) unset($data['roles'], $data['branch_id'], $data['is_active']);
            $roles = $isAdmin ? ($data['roles'] ?? null) : null;
            unset($data['roles']);
            if (array_key_exists('password', $data) && $data['password'] === null) unset($data['password']);
            $user->update($data);
            if ($roles !== null) $user->roles()->sync(Role::query()->whereIn('code', $roles)->pluck('id'));
            return $user->refresh()->load(['branch', 'roles']);
        });
        return response()->json(['message' => 'User updated.', 'data' => new UserResource($updated)]);
    }
}
