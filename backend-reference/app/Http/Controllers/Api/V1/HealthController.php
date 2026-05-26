<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use Illuminate\Http\JsonResponse; use Illuminate\Support\Facades\DB; use Throwable;
class HealthController extends Controller { public function __invoke(): JsonResponse { try{DB::select('select 1');$db='ok';}catch(Throwable $e){$db='failed';} return response()->json(['success'=>true,'data'=>['status'=>$db==='ok'?'ok':'degraded','database'=>$db,'timestamp'=>now()->toISOString()]],$db==='ok'?200:503); } }
