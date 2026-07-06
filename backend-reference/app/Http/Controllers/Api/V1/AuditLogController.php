<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Models\AuditLog; use Illuminate\Http\{JsonResponse,Request};
class AuditLogController extends Controller { public function index(Request $request): JsonResponse { $this->authorize('viewAny', AuditLog::class); return response()->json(['data'=>[]]); } }
