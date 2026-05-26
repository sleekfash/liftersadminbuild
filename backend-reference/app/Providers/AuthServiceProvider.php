<?php
namespace App\Providers;
use App\Models\{AuditLog,Branch,DisbursementRequest,ImportBatch,ImportRow,ImportSheetSnapshot,Member,MonthlyPeriod,ReconciliationItem,ReconciliationRun,TreasuryTransaction,User};
use App\Policies\{AuditLogPolicy,BranchPolicy,DisbursementRequestPolicy,ImportPolicy,MemberPolicy,MonthlyPeriodPolicy,ReconciliationPolicy,TreasuryTransactionPolicy,UserPolicy};
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
class AuthServiceProvider extends ServiceProvider
{
    protected $policies=[User::class=>UserPolicy::class,Branch::class=>BranchPolicy::class,Member::class=>MemberPolicy::class,DisbursementRequest::class=>DisbursementRequestPolicy::class,TreasuryTransaction::class=>TreasuryTransactionPolicy::class,MonthlyPeriod::class=>MonthlyPeriodPolicy::class,ReconciliationRun::class=>ReconciliationPolicy::class,ReconciliationItem::class=>ReconciliationPolicy::class,ImportBatch::class=>ImportPolicy::class,ImportSheetSnapshot::class=>ImportPolicy::class,ImportRow::class=>ImportPolicy::class,AuditLog::class=>AuditLogPolicy::class];
    public function boot(): void { $this->registerPolicies(); }
}
