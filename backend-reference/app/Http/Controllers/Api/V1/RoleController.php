<?php
namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    private function assertAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole([RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value]), 403);
    }

    public function index(Request $request): JsonResponse
    {
        $this->assertAdmin($request);
        return response()->json(['data' => RoleResource::collection(Role::query()->orderBy('name')->get())]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertAdmin($request);
        $data = $request->validate(['code' => ['required', Rule::in(array_map(fn (RoleCode $role) => $role->value, RoleCode::cases()))], 'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:500']]);
        $role = Role::updateOrCreate(['code' => $data['code']], ['name' => $data['name'], 'description' => $data['description'] ?? null]);
        return response()->json(['message' => 'Role saved.', 'data' => new RoleResource($role)], 201);
    }
}