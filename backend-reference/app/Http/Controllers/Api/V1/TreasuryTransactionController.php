<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Http\Resources\TreasuryTransactionResource; use App\Models\TreasuryTransaction; use Illuminate\Http\{JsonResponse,Request};
class TreasuryTransactionController extends Controller { public function index(Request $r): JsonResponse{$this->authorize('viewAny',TreasuryTransaction::class);return response()->json(['data'=>TreasuryTransactionResource::collection(TreasuryTransaction::latest()->paginate($r->integer('per_page',15)))]);} public function show(TreasuryTransaction $transaction): JsonResponse{$this->authorize('view',$transaction);return response()->json(['data'=>new TreasuryTransactionResource($transaction)]);} }
