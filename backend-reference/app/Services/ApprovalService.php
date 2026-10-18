<?php
namespace App\Services;
use App\Enums\{AuditEventType,DisbursementStage,RoleCode}; use App\Models\{DisbursementApproval,DisbursementRequest,DisbursementStatusHistory,User}; use App\Http\Requests\BranchRejectionRequest; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
class ApprovalService
{
    public function __construct(private AuditService $auditService) {}
    public function branchApprove(DisbursementRequest $r,array $d,User $a): DisbursementRequest { return $this->approve($r,DisbursementStage::SUBMITTED,DisbursementStage::BRANCH_APPROVED,RoleCode::BRANCH_MANAGER,$d,$a); }
    public function branchReject(DisbursementRequest $r,array $d,User $a): DisbursementRequest
    {
        return DB::transaction(function() use($r,$d,$a) {
            $r=DisbursementRequest::whereKey($r->id)->lockForUpdate()->firstOrFail();
            $this->assertBranchReviewer($r,$a);
            $d=validator($d, BranchRejectionRequest::validationRules())->validate();
            $r->update(['stage'=>DisbursementStage::REJECTED->value,'rejection_category'=>$d['category'],'rejected_reason'=>$d['reason']]);
            DisbursementStatusHistory::create(['disbursement_request_id'=>$r->id,'from_stage'=>DisbursementStage::SUBMITTED->value,'to_stage'=>DisbursementStage::REJECTED->value,'reason'=>$d['reason'],'changed_by'=>$a->id]);
            $this->auditService->record($a,AuditEventType::STATUS_CHANGE,'Disbursement rejected at branch review',$r,['category'=>$d['category'],'reason'=>$d['reason']]);
            return $r->refresh()->load(['member','requester','approvals','statusHistories.actor']);
        });
    }
    private function assertBranchReviewer(DisbursementRequest $r,User $a): void
    {
        if($r->stage!==DisbursementStage::SUBMITTED->value) throw ValidationException::withMessages(['stage'=>'Only SUBMITTED requests can be reviewed.']);
        if(!$r->requested_by || (int)$r->requested_by === (int)$a->id) throw ValidationException::withMessages(['approver'=>'Requester cannot approve or reject their own disbursement.']);
        if(!$a->hasRole(RoleCode::SUPER_ADMIN->value) && (!$a->hasRole(RoleCode::BRANCH_MANAGER->value) || !(int)$a->branch_id || (int)$a->branch_id !== (int)$r->branch_id)) throw ValidationException::withMessages(['approver'=>'Only a same-branch Branch Manager can review this request.']);
    }
    public function financeReview(DisbursementRequest $r,array $d,User $a): DisbursementRequest { return $this->approve($r,DisbursementStage::BRANCH_APPROVED,DisbursementStage::FINANCE_REVIEWED,RoleCode::FINANCE_OFFICER,$d,$a); }
    public function authorize(DisbursementRequest $r,array $d,User $a): DisbursementRequest { return $this->approve($r,DisbursementStage::FINANCE_REVIEWED,DisbursementStage::AUTHORIZED,RoleCode::SUPER_ADMIN,$d,$a,true); }
    private function approve(DisbursementRequest $r,DisbursementStage $expected,DisbursementStage $next,RoleCode $role,array $d,User $a,bool $auth=false): DisbursementRequest { return DB::transaction(function() use($r,$expected,$next,$role,$d,$a,$auth){$r=DisbursementRequest::whereKey($r->id)->lockForUpdate()->firstOrFail(); if($r->stage!==$expected->value) throw ValidationException::withMessages(['stage'=>"Expected {$expected->value}, got {$r->stage}."]); if($next===DisbursementStage::BRANCH_APPROVED) { $this->assertBranchReviewer($r,$a); $d=validator($d,['remarks'=>['nullable','string','max:1000']])->validate(); } if($next===DisbursementStage::BRANCH_APPROVED && (int)$r->requested_by === (int)$a->id) throw ValidationException::withMessages(['approver'=>'Requester cannot approve their own disbursement.']); if(DisbursementApproval::where('disbursement_request_id',$r->id)->where('stage',$next->value)->exists()) throw ValidationException::withMessages(['approval'=>'This approval stage has already been recorded.']); DisbursementApproval::create(['disbursement_request_id'=>$r->id,'user_id'=>$a->id,'role_code'=>$role->value,'stage'=>$next->value,'remarks'=>$d['remarks']??null,'approved_at'=>now()]); $update=['stage'=>$next->value]; if($auth){$update['authorized_by']=$a->id;$update['authorized_at']=now();} $r->update($update); DisbursementStatusHistory::create(['disbursement_request_id'=>$r->id,'from_stage'=>$expected->value,'to_stage'=>$next->value,'reason'=>$d['remarks']??"Approved by {$role->value}",'changed_by'=>$a->id]); $this->auditService->record($a,AuditEventType::APPROVAL,"Disbursement approval: {$next->value}",$r,['role'=>$role->value]); return $r->refresh()->load(['member','requester','approvals','statusHistories.actor']);}); }
}
