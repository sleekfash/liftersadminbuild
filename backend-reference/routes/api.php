<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{HealthController,AuthController,MemberController,MemberLifecycleController,DisbursementController,DisbursementWorkflowController,ApprovalController,TreasuryPostingController,TreasuryTransactionController,MonthlyPeriodController,PeriodWorkflowController,ReconciliationRunController,ReconciliationItemController,ImportBatchController,ImportSheetSnapshotController,ImportRowController,AuditLogController,UserController,BranchController};

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::apiResource('users', UserController::class)->only(['index']);
        Route::apiResource('branches', BranchController::class)->only(['index']);
        Route::apiResource('members', MemberController::class);
        Route::patch('/members/{member}/verify', [MemberLifecycleController::class, 'verify']);
        Route::patch('/members/{member}/activate', [MemberLifecycleController::class, 'activate']);
        Route::patch('/members/{member}/suspend', [MemberLifecycleController::class, 'suspend']);
        Route::patch('/members/{member}/reactivate', [MemberLifecycleController::class, 'reactivate']);
        Route::patch('/members/{member}/terminate', [MemberLifecycleController::class, 'terminate']);
        Route::apiResource('disbursements', DisbursementController::class)->only(['index','store','show']);
        Route::post('/disbursements/{disbursement}/submit', [DisbursementWorkflowController::class, 'submit']);
        Route::post('/disbursements/{disbursement}/approve/branch', [ApprovalController::class, 'branchApprove']);
        Route::post('/disbursements/{disbursement}/approve/finance', [ApprovalController::class, 'financeReview']);
        Route::post('/disbursements/{disbursement}/authorize', [ApprovalController::class, 'authorizeDisbursement']);
        Route::post('/disbursements/{disbursement}/mark-paid', [TreasuryPostingController::class, 'markPaid']);
        Route::apiResource('treasury/transactions', TreasuryTransactionController::class)->only(['index','show']);
        Route::apiResource('periods', MonthlyPeriodController::class)->only(['index','store','show']);
        Route::post('/periods/{period}/review', [PeriodWorkflowController::class, 'review']);
        Route::post('/periods/{period}/close', [PeriodWorkflowController::class, 'close']);
        Route::post('/periods/{period}/lock', [PeriodWorkflowController::class, 'lock']);
        Route::post('/periods/{period}/reopen', [PeriodWorkflowController::class, 'reopen']);
        Route::apiResource('reconciliation/runs', ReconciliationRunController::class)->only(['index','store','show']);
        Route::get('/reconciliation/items', [ReconciliationItemController::class, 'index']);
        Route::post('/reconciliation/items/{item}/under-review', [ReconciliationItemController::class, 'underReview']);
        Route::post('/reconciliation/items/{item}/resolve', [ReconciliationItemController::class, 'resolve']);
        Route::post('/reconciliation/items/{item}/override', [ReconciliationItemController::class, 'override']);
        Route::get('/imports/batches', [ImportBatchController::class, 'index']);
        Route::post('/imports/batches', [ImportBatchController::class, 'store']);
        Route::get('/imports/batches/{batch}', [ImportBatchController::class, 'show']);
        Route::post('/imports/batches/{batch}/sheets', [ImportSheetSnapshotController::class, 'store']);
        Route::post('/imports/sheets/{sheet}/rows', [ImportRowController::class, 'store']);
        Route::post('/imports/batches/{batch}/map', [ImportBatchController::class, 'map']);
        Route::post('/imports/batches/{batch}/validate', [ImportBatchController::class, 'validateRows']);
        Route::post('/imports/batches/{batch}/post-trial-balance', [ImportBatchController::class, 'postTrialBalance']);
        Route::get('/imports/rows', [ImportRowController::class, 'index']);
        Route::post('/imports/rows/{row}/skip', [ImportRowController::class, 'skip']);
        Route::apiResource('audit-logs', AuditLogController::class)->only(['index']);
    });
});
