<?php
namespace App\Http\Middleware;
use App\Models\IdempotencyKey; use Closure; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Symfony\Component\HttpFoundation\Response;
class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if(!in_array($request->method(),['POST','PATCH','PUT'],true)) return $next($request);
        $key=$request->header('Idempotency-Key'); if(!$key) return $next($request);
        $userId=$request->user()?->id; $hash=hash('sha256',json_encode($request->except(['password']),JSON_UNESCAPED_SLASHES));
        return DB::transaction(function() use($request,$next,$key,$userId,$hash){
            $record=IdempotencyKey::where('user_id',$userId)->where('key',$key)->lockForUpdate()->first();
            if($record && $record->completed_at){ if($record->request_hash!==$hash) return response()->json(['success'=>false,'message'=>'Idempotency key was reused with a different request payload.','code'=>'IDEMPOTENCY_HASH_MISMATCH','errors'=>[]],409); return response()->json($record->response_body??[],$record->response_status??200); }
            if(!$record){$record=IdempotencyKey::create(['user_id'=>$userId,'key'=>$key,'method'=>$request->method(),'path'=>$request->path(),'request_hash'=>$hash,'locked_at'=>now()]);}
            $response=$next($request); $body=json_decode($response->getContent(),true);
            if(is_array($body) && $response->getStatusCode()<500){$record->update(['response_status'=>$response->getStatusCode(),'response_body'=>$body,'completed_at'=>now()]);}
            return $response;
        });
    }
}
