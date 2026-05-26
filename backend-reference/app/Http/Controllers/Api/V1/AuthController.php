<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Http\Requests\LoginRequest; use App\Http\Resources\UserResource; use App\Services\AuthService; use Illuminate\Http\{JsonResponse,Request};
class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}
    public function login(LoginRequest $request): JsonResponse { $r=$this->authService->login($request->validated()); return response()->json(['message'=>'Login successful.','token'=>$r['token'],'data'=>new UserResource($r['user'])]); }
    public function me(Request $request): JsonResponse { return response()->json(['data'=>new UserResource($request->user()->load('roles'))]); }
    public function logout(Request $request): JsonResponse { $this->authService->logout($request->user()); return response()->json(['message'=>'Logout successful.']); }
}
