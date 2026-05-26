<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use Illuminate\Http\{JsonResponse,Request};
class AuditLogController extends Controller { public function index(Request $request): JsonResponse { return response()->json(['data'=>[]]); } }
